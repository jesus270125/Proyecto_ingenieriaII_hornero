<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Propina;
use App\Models\Venta;
use Illuminate\Support\Facades\Validator;

class PropinaController extends Controller
{
    public function index(Request $request)
    {
        $query = Propina::query();

        if ($request->has('fecha_inicio')) {
            $query->whereDate('fecha', '>=', $request->input('fecha_inicio'));
        }
        if ($request->has('fecha_fin')) {
            $query->whereDate('fecha', '<=', $request->input('fecha_fin'));
        }
        if ($request->has('usuario_id')) {
            $query->where('usuario_id', $request->input('usuario_id'));
        }

        return response()->json($query->orderBy('fecha', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'venta_id' => 'nullable|exists:venta,id',
            'monto' => 'required|numeric|min:0',
            'metodo_pago' => 'nullable|string',
            'usuario_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $propina = Propina::create($data);

        return response()->json($propina, 201);
    }

    public function reporte(Request $request)
    {
        // Reporte consolidado por fecha y usuario
        $query = Propina::selectRaw("DATE(fecha) as fecha, usuario_id, SUM(monto) as total_propinas")
            ->groupBy('fecha', 'usuario_id');

        if ($request->has('usuario_id')) {
            $query->where('usuario_id', $request->input('usuario_id'));
        }

        return response()->json($query->get());
    }
}
