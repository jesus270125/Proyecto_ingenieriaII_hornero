<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\ReciboController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\InventarioController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::get('/menu', [MenuController::class, 'index']);
Route::post('/menu', [MenuController::class, 'store']);
Route::put('/menu/{id}', [MenuController::class, 'update']);
Route::delete('/menu/{id}', [MenuController::class, 'destroy']);

Route::prefix('caja')->group(function () {
    Route::post('/abrir', [CajaController::class, 'abrir']);
    Route::post('/cerrar', [CajaController::class, 'cerrar']);
    Route::get('/estado', [CajaController::class, 'estado']);
    Route::post('/venta', [CajaController::class, 'registrarVenta']);
    Route::post('/recibo', [ReciboController::class, 'generar']);
    Route::post('/sunat', [ReciboController::class, 'enviarSunat']);
});

Route::get('/pedidos', [PedidoController::class, 'index']);
Route::post('/pedidos', [PedidoController::class, 'store']);
Route::post('/pedidos/actualizar', [PedidoController::class, 'updateStatusFromPost']);
Route::delete('/pedidos/{id}', [PedidoController::class, 'destroy']);

Route::prefix('admin')->group(function () {
    Route::get('/stats', [AdminController::class, 'stats']);
    Route::get('/recientes', [AdminController::class, 'recientes']);
    Route::get('/clientes', [AdminController::class, 'clientes']);
    Route::get('/usuarios', [AdminController::class, 'clientes']);
    Route::get('/ventas-diarias', [AdminController::class, 'ventasDiarias']);
    Route::get('/ventas-mensuales', [AdminController::class, 'ventasMensuales']);
    Route::get('/ventas-anuales', [AdminController::class, 'ventasAnuales']);
    Route::get('/pedidos-diarios', [AdminController::class, 'pedidosDiarios']);
    Route::get('/pedidos-mensuales', [AdminController::class, 'pedidosMensuales']);
    Route::get('/pedidos-anuales', [AdminController::class, 'pedidosAnuales']);
    Route::get('/reportes/pedidos', [AdminController::class, 'reportePedidos']);
    Route::get('/reportes/pedidos-mesero', [AdminController::class, 'reportePedidosPorMesero']);
    Route::get('/reportes/recibos-entregados', [AdminController::class, 'reporteRecibosEntregados']);
    Route::get('/caja/config', [AdminController::class, 'cajaConfig']);
    Route::post('/caja/config', [AdminController::class, 'updateCajaConfig']);
    
    Route::post('/usuarios', [AdminController::class, 'crearUsuario']);
    Route::put('/usuarios/{id}', [AdminController::class, 'actualizarUsuario']);
    Route::delete('/usuarios/{id}', [AdminController::class, 'eliminarUsuario']);
});

// ================================================================
// MÓDULO DE GESTIÓN DE INVENTARIOS (RF31 - RF34)
// ================================================================
Route::prefix('inventario')->group(function () {
    // RF31: CRUD de Insumos
    Route::get('/insumos', [InventarioController::class, 'indexInsumos']);
    Route::post('/insumos', [InventarioController::class, 'storeInsumo']);
    Route::put('/insumos/{id}', [InventarioController::class, 'updateInsumo']);
    Route::delete('/insumos/{id}', [InventarioController::class, 'destroyInsumo']);
    Route::get('/categorias', [InventarioController::class, 'categorias']);

    // RF32: Entradas de Mercadería
    Route::get('/entradas', [InventarioController::class, 'indexEntradas']);
    Route::post('/entradas', [InventarioController::class, 'storeEntrada']);

    // RF33: Recetas y Descuento de Stock
    Route::get('/recetas/{menuId}', [InventarioController::class, 'getReceta']);
    Route::post('/recetas', [InventarioController::class, 'storeReceta']);
    Route::post('/descontar/{ventaId}', [InventarioController::class, 'descontarStock']);

    // RF34: Alertas y Estadísticas
    Route::get('/alertas', [InventarioController::class, 'alertas']);
    Route::get('/stats', [InventarioController::class, 'dashboardStats']);
});
