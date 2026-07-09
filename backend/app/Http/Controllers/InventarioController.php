<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Insumo;
use App\Models\EntradaMercaderia;
use App\Models\MenuInsumo;
use App\Models\Menu;
use App\Models\Pedido;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

/**
 * InventarioController - Módulo de Gestión de Inventarios
 *
 * Implementa los requerimientos RF31 a RF34:
 * - RF31: CRUD de insumos (catálogo base)
 * - RF32: Registro de entradas de mercadería
 * - RF33: Descuento automático de stock por venta (recetas)
 * - RF34: Alertas de stock mínimo
 */
class InventarioController extends Controller
{
    // ================================================================
    // RF31: CRUD DE INSUMOS
    // ================================================================

    /**
     * RF31: Listar todos los insumos con indicador de stock bajo (RF34).
     */
    public function indexInsumos(Request $request)
    {
        $query = Insumo::query();

        // Filtro opcional por categoría
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        // Filtro opcional por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $insumos = $query->orderBy('nombre', 'asc')->get()->map(function ($insumo) {
            return [
                'id' => $insumo->id,
                'nombre' => $insumo->nombre,
                'categoria' => $insumo->categoria,
                'unidad_medida' => $insumo->unidad_medida,
                'stock_actual' => (float) $insumo->stock_actual,
                'stock_minimo' => (float) $insumo->stock_minimo,
                'estado' => $insumo->estado,
                'stock_bajo' => $insumo->stock_bajo, // RF34: indicador visual
                'created_at' => $insumo->created_at,
                'updated_at' => $insumo->updated_at,
            ];
        });

        return response()->json($insumos);
    }

    /**
     * RF31: Crear un nuevo insumo.
     */
    public function storeInsumo(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'categoria' => 'required|string|max:50',
            'unidad_medida' => 'required|string|max:20',
            'stock_actual' => 'nullable|numeric|min:0',
            'stock_minimo' => 'nullable|numeric|min:0',
        ]);

        $insumo = Insumo::create([
            'nombre' => $request->nombre,
            'categoria' => $request->categoria,
            'unidad_medida' => $request->unidad_medida,
            'stock_actual' => $request->input('stock_actual', 0),
            'stock_minimo' => $request->input('stock_minimo', 0),
            'estado' => 'activo',
        ]);

        return response()->json(['success' => true, 'id' => $insumo->id, 'msg' => 'Insumo creado correctamente']);
    }

    /**
     * RF31: Actualizar un insumo existente.
     */
    public function updateInsumo(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'categoria' => 'required|string|max:50',
            'unidad_medida' => 'required|string|max:20',
            'stock_actual' => 'nullable|numeric|min:0',
            'stock_minimo' => 'nullable|numeric|min:0',
            'estado' => 'nullable|string|in:activo,inactivo',
        ]);

        $insumo = Insumo::find($id);
        if (!$insumo) {
            return response()->json(['success' => false, 'error' => 'Insumo no encontrado'], 404);
        }

        $insumo->update([
            'nombre' => $request->nombre,
            'categoria' => $request->categoria,
            'unidad_medida' => $request->unidad_medida,
            'stock_actual' => $request->input('stock_actual', $insumo->stock_actual),
            'stock_minimo' => $request->input('stock_minimo', $insumo->stock_minimo),
            'estado' => $request->input('estado', $insumo->estado),
        ]);

        return response()->json(['success' => true, 'msg' => 'Insumo actualizado correctamente']);
    }

    /**
     * RF31: Desactivar un insumo (soft delete).
     */
    public function destroyInsumo($id)
    {
        $insumo = Insumo::find($id);
        if (!$insumo) {
            return response()->json(['success' => false, 'error' => 'Insumo no encontrado'], 404);
        }

        // Cambiar estado en vez de eliminar para mantener integridad referencial
        $insumo->estado = ($insumo->estado === 'activo') ? 'inactivo' : 'activo';
        $insumo->save();

        $msg = $insumo->estado === 'activo' ? 'Insumo activado' : 'Insumo desactivado';
        return response()->json(['success' => true, 'msg' => $msg, 'estado' => $insumo->estado]);
    }

    /**
     * RF31: Obtener las categorías únicas existentes (para filtros del frontend).
     */
    public function categorias()
    {
        $categorias = Insumo::select('categoria')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        return response()->json($categorias);
    }

    // ================================================================
    // RF32: REGISTRO DE ENTRADAS DE MERCADERÍA
    // ================================================================

    /**
     * RF32: Listar historial de entradas de mercadería.
     */
    public function indexEntradas(Request $request)
    {
        $query = EntradaMercaderia::with('insumo:id,nombre,unidad_medida');

        // Filtro por insumo
        if ($request->filled('insumo_id')) {
            $query->where('insumo_id', $request->insumo_id);
        }

        // Filtro por rango de fechas
        if ($request->filled('from')) {
            $query->where('fecha', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('fecha', '<=', $request->to);
        }

        $entradas = $query->orderBy('created_at', 'desc')->get();

        return response()->json($entradas);
    }

    /**
     * RF32: Registrar una nueva entrada de mercadería y actualizar stock.
     *
     * Al guardar una entrada, el stock del insumo se incrementa
     * automáticamente con la cantidad ingresada. (RNF18: actualización
     * en tiempo real)
     */
    public function storeEntrada(Request $request)
    {
        $request->validate([
            'insumo_id' => 'required|integer|exists:insumo,id',
            'cantidad' => 'required|numeric|min:0.001',
            'fecha' => 'required|date',
            'costo' => 'nullable|numeric|min:0',
            'proveedor' => 'nullable|string|max:100',
            'observacion' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Registrar la entrada en el historial
            $entrada = EntradaMercaderia::create([
                'insumo_id' => $request->insumo_id,
                'cantidad' => $request->cantidad,
                'costo' => $request->costo,
                'proveedor' => $request->proveedor,
                'fecha' => $request->fecha,
                'observacion' => $request->observacion,
            ]);

            // Actualizar el stock del insumo (sumar cantidad ingresada)
            $insumo = Insumo::find($request->insumo_id);
            $insumo->stock_actual += $request->cantidad;
            $insumo->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => "Entrada registrada. Stock actualizado de {$insumo->nombre}: {$insumo->stock_actual} {$insumo->unidad_medida}",
                'stock_actual' => (float) $insumo->stock_actual,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Error al registrar entrada: ' . $e->getMessage()], 500);
        }
    }

    // ================================================================
    // RF33: RECETAS Y DESCUENTO AUTOMÁTICO DE STOCK
    // ================================================================

    /**
     * RF33: Obtener la receta de un producto del menú.
     */
    public function getReceta($menuId)
    {
        $menu = Menu::with(['insumos' => function ($q) {
            $q->select('insumo.id', 'insumo.nombre', 'insumo.unidad_medida', 'insumo.stock_actual');
        }])->find($menuId);

        if (!$menu) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        $receta = $menu->insumos->map(function ($insumo) {
            return [
                'insumo_id' => $insumo->id,
                'nombre' => $insumo->nombre,
                'unidad_medida' => $insumo->unidad_medida,
                'stock_actual' => (float) $insumo->stock_actual,
                'cantidad_requerida' => (float) $insumo->pivot->cantidad_requerida,
            ];
        });

        return response()->json([
            'menu_id' => $menu->id,
            'menu_nombre' => $menu->nombre,
            'ingredientes' => $receta,
        ]);
    }

    /**
     * RF33: Guardar o actualizar la receta completa de un producto.
     *
     * Recibe un array de ingredientes y reemplaza la receta actual.
     */
    public function storeReceta(Request $request)
    {
        $request->validate([
            'menu_id' => 'required|integer|exists:menu,id',
            'ingredientes' => 'required|array|min:1',
            'ingredientes.*.insumo_id' => 'required|integer|exists:insumo,id',
            'ingredientes.*.cantidad_requerida' => 'required|numeric|min:0.001',
        ]);

        DB::beginTransaction();
        try {
            // Eliminar receta anterior
            MenuInsumo::where('menu_id', $request->menu_id)->delete();

            // Insertar nueva receta
            foreach ($request->ingredientes as $ingrediente) {
                MenuInsumo::create([
                    'menu_id' => $request->menu_id,
                    'insumo_id' => $ingrediente['insumo_id'],
                    'cantidad_requerida' => $ingrediente['cantidad_requerida'],
                ]);
            }

            DB::commit();

            $menu = Menu::find($request->menu_id);
            return response()->json([
                'success' => true,
                'msg' => "Receta de '{$menu->nombre}' guardada con " . count($request->ingredientes) . " ingredientes",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Error al guardar receta: ' . $e->getMessage()], 500);
        }
    }

    /**
     * RF33: Descontar stock basado en los pedidos de una venta.
     *
     * Este método se llama desde CajaController al registrar una venta.
     * Parsea el campo 'detalle' de los pedidos vinculados a la venta
     * para determinar qué productos se vendieron y en qué cantidad,
     * luego descuenta los insumos según las recetas definidas.
     */
    public function descontarStock($ventaId)
    {
        $resultado = $this->descontarStockInterno($ventaId);
        return response()->json($resultado);
    }

    /**
     * RF33: Lógica interna de descuento de stock (reutilizable).
     *
     * Puede ser llamada internamente desde CajaController sin
     * pasar por HTTP.
     */
    public function descontarStockInterno($ventaId)
    {
        // Obtener pedidos vinculados a esta venta
        $pedidos = Pedido::where('venta_id', $ventaId)->get();

        if ($pedidos->isEmpty()) {
            return ['success' => false, 'msg' => 'No se encontraron pedidos para esta venta'];
        }

        $productosVendidos = [];
        $alertas = [];

        DB::beginTransaction();
        try {
            foreach ($pedidos as $pedido) {
                // Parsear el campo 'detalle' del pedido
                // Formato esperado: "2 x Pollo entero, 1 x Gaseosa 1L"
                $detalle = (string) $pedido->detalle;
                $parts = array_map('trim', explode(',', $detalle));

                foreach ($parts as $part) {
                    if (preg_match('/(\d+)\s*x\s*(.+)/i', $part, $m)) {
                        $qty = (int) $m[1];
                        $nombre = trim($m[2]);
                        // Limpiar nombre (quitar precio si lo tiene)
                        $nombre = preg_replace('/\(.*$/', '', $nombre);
                        $nombre = trim($nombre);

                        // Buscar el producto en el menú
                        $menu = Menu::where('nombre', $nombre)->first();
                        if (!$menu) continue;

                        // Obtener receta del producto
                        $receta = MenuInsumo::where('menu_id', $menu->id)->get();
                        if ($receta->isEmpty()) continue;

                        // Descontar cada insumo de la receta
                        foreach ($receta as $ingrediente) {
                            $cantidadDescontar = $ingrediente->cantidad_requerida * $qty;

                            $insumo = Insumo::find($ingrediente->insumo_id);
                            if (!$insumo) continue;

                            $insumo->stock_actual = max(0, $insumo->stock_actual - $cantidadDescontar);
                            $insumo->save();

                            // RF34: Verificar si quedó por debajo del mínimo
                            if ($insumo->stock_actual <= $insumo->stock_minimo) {
                                $alertas[] = [
                                    'insumo_id' => $insumo->id,
                                    'nombre' => $insumo->nombre,
                                    'stock_actual' => (float) $insumo->stock_actual,
                                    'stock_minimo' => (float) $insumo->stock_minimo,
                                ];
                            }
                        }

                        $productosVendidos[] = [
                            'menu' => $menu->nombre,
                            'cantidad' => $qty,
                        ];
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'msg' => 'Stock descontado correctamente',
                'productos_procesados' => $productosVendidos,
                'alertas_stock' => $alertas,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'error' => 'Error al descontar stock: ' . $e->getMessage()];
        }
    }

    // ================================================================
    // RF34: ALERTAS DE STOCK MÍNIMO
    // ================================================================

    /**
     * RF34: Obtener insumos con stock bajo o en nivel mínimo.
     */
    public function alertas()
    {
        $alertas = Insumo::stockBajo()
            ->orderBy('stock_actual', 'asc')
            ->get()
            ->map(function ($insumo) {
                $porcentaje = $insumo->stock_minimo > 0
                    ? round(($insumo->stock_actual / $insumo->stock_minimo) * 100, 1)
                    : 0;

                return [
                    'id' => $insumo->id,
                    'nombre' => $insumo->nombre,
                    'categoria' => $insumo->categoria,
                    'unidad_medida' => $insumo->unidad_medida,
                    'stock_actual' => (float) $insumo->stock_actual,
                    'stock_minimo' => (float) $insumo->stock_minimo,
                    'porcentaje' => $porcentaje,
                    'nivel' => $insumo->stock_actual <= 0 ? 'agotado'
                              : ($porcentaje <= 50 ? 'critico' : 'bajo'),
                ];
            });

        return response()->json($alertas);
    }

    /**
     * RF34: Estadísticas de inventario para el Dashboard del administrador.
     */
    public function dashboardStats()
    {
        $totalInsumos = Insumo::activos()->count();
        $insumosStockBajo = Insumo::stockBajo()->count();
        $insumosAgotados = Insumo::where('estado', 'activo')
            ->where('stock_actual', '<=', 0)
            ->count();
        $entradasHoy = EntradaMercaderia::where('fecha', today())->count();

        return response()->json([
            'total_insumos' => $totalInsumos,
            'stock_bajo' => $insumosStockBajo,
            'agotados' => $insumosAgotados,
            'entradas_hoy' => $entradasHoy,
        ]);
    }
}
