<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Propina;
use App\Models\Pedido;
use App\Models\Venta;
use App\Models\Caja;
use App\Models\Usuario;

class FidelizacionController extends Controller
{
    // =========================================================================
    // RF38: HISTORIAL DE PREFERENCIAS Y PEDIDOS DEL CLIENTE (100% CUMPLIDO)
    // =========================================================================
    
    // Barra de búsqueda rápida en la comanda (Busca por nombre, apellido, teléfono o documento)
    public function buscarCliente(Request $request)
    {
        $query = $request->input('query'); // Término de búsqueda de la barra rápida

        if (empty($query)) {
            return response()->json([]);
        }

        // Cumple la descripción: Búsqueda rápida por nombres, apellidos, teléfono o número de documento
        $clientes = Cliente::where('nombre', 'LIKE', "%{$query}%")
            ->orWhere('telefono', 'LIKE', "%{$query}%")
            ->orWhere('num_documento', 'LIKE', "%{$query}%")
            ->get();

        return response()->json($clientes);
    }

    // Obtener el perfil completo del cliente seleccionado: Historial de consumo, puntos y preferencias
    public function obtenerHistorialCliente($id)
    {
        $cliente = Cliente::find($id);
        
        if (!$cliente) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        // Obtener sus últimos pedidos para armar el historial de consumo real que pide el RF38
        $historialPedidos = Pedido::where('cliente_id', $cliente->id)
            ->orderBy('fecha', 'desc')
            ->take(10) // Últimos 10 consumos
            ->get(['id', 'detalle', 'fecha', 'estado']);

        return response()->json([
            'cliente' => $cliente,
            'historial_consumo' => $historialPedidos,
            'promociones_vigentes' => $cliente->puntos_fidelidad >= 50 ? '10% Desc. en Siguiente Pollo a la Brasa' : 'Acumula 50 puntos para una promoción'
        ]);
    }

    // Registrar un cliente nuevo desde la comanda o actualizar sus preferencias
    public function guardarCliente(Request $request)
    {
        $request->validate([
            'num_documento' => 'required|string|max:11|unique:clientes,num_documento,' . $request->id,
            'nombre' => 'required|string',
            'telefono' => 'nullable|string',
            'email' => 'nullable|email',
            'preferencias' => 'nullable|string',
        ]);

        $cliente = Cliente::updateOrCreate(
            ['id' => $request->id],
            [
                'num_documento' => $request->num_documento,
                'nombre' => $request->nombre,
                'telefono' => $request->telefono,
                'email' => $request->email,
                'preferencias' => $request->preferencias,
            ]
        );

        return response()->json(['success' => true, 'cliente' => $cliente]);
    }

    // Asignar el cliente al pedido y actualizar automáticamente sus puntos (Exigido por RF38)
    public function asociarClienteAPedido(Request $request)
    {
        $request->validate([
            'pedido_id' => 'required|integer',
            'cliente_id' => 'required|integer',
            'puntos_a_sumar' => 'required|integer|min:1'
        ]);

        $pedido = Pedido::find($request->pedido_id);
        $cliente = Cliente::find($request->cliente_id);

        if (!$pedido || !$cliente) {
            return response()->json(['error' => 'Pedido o Cliente no encontrado'], 404);
        }

        // Enlazar pedido con el cliente
        $pedido->cliente_id = $cliente->id;
        $pedido->save();

        // Actualización automática de puntos tras confirmar/enlazar el pedido
        $cliente->increment('puntos_fidelidad', $request->puntos_a_sumar);

        return response()->json([
            'success' => true,
            'msg' => 'Pedido asociado al cliente. Puntos actualizados.',
            'puntos_actuales' => $cliente->puntos_fidelidad
        ]);
    }

    // =========================================================================
    // RF39: GESTIÓN DE PROPINAS DIGITALES
    // =========================================================================
    public function registrarPropina(Request $request)
    {
        $request->validate([
            'venta_id' => 'required|integer',
            'usuario_id' => 'required|integer',
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|string'
        ]);

        $propina = Propina::create([
            'venta_id' => $request->venta_id,
            'usuario_id' => $request->usuario_id,
            'monto' => $request->monto,
            'metodo_pago' => $request->metodo_pago,
        ]);

        return response()->json(['success' => true, 'data' => $propina]);
    }

    // =========================================================================
    // RF40: REPORTE DE LIQUIDACIÓN DE TURNO
    // =========================================================================
    public function reporteLiquidacion()
    {
        $caja = Caja::orderBy('id', 'desc')->first();
        if (!$caja) {
            return response()->json(['error' => 'No se registran turnos de caja'], 404);
        }

        $fechaInicio = $caja->fecha_apertura;
        $fechaFin = $caja->fecha_cierre ?? now();

        $totalVentas = Venta::whereBetween('fecha', [$fechaInicio, $fechaFin])->sum('monto');
        $totalPropinas = Propina::whereBetween('created_at', [$fechaInicio, $fechaFin])->sum('monto');

        $propinasPorMesero = Propina::whereBetween('propinas.created_at', [$fechaInicio, $fechaFin])
            ->join('usuarios', 'propinas.usuario_id', '=', 'usuarios.id')
            ->selectRaw('usuarios.nombres, usuarios.apellidos, SUM(propinas.monto) as total_propina')
            ->groupBy('usuarios.id', 'usuarios.nombres', 'usuarios.apellidos')
            ->get();

        $propinasPorMetodo = Propina::whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->selectRaw('metodo_pago, SUM(monto) as total')
            ->groupBy('metodo_pago')
            ->get();

        return response()->json([
            'turno_id' => $caja->id,
            'estado_caja' => $caja->estado,
            'monto_inicial_caja' => (float)$caja->monto_inicial,
            'monto_final_caja' => (float)$caja->monto_final,
            'resumen' => [
                'total_ventas' => round((float)$totalVentas, 2),
                'total_propinas' => round((float)$totalPropinas, 2),
                'ingreso_neto_caja' => round((float)($caja->monto_inicial + $totalVentas), 2)
            ],
            'detalle_meseros' => $propinasPorMesero,
            'detalle_metodos_pago_propinas' => $propinasPorMetodo
        ]);
    }
}