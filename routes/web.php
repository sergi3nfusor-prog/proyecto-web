<?php
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// ── Página de bienvenida ──────────────────────────────────────────────
Route::get('/', function () { return view('welcome'); });

// ── Dashboard principal ───────────────────────────────────────────────
Route::get('/dashboard', function () {
    $anios     = DB::select("SELECT DISTINCT EXTRACT(YEAR FROM fechaVenta)::INT AS anio FROM venta WHERE fechaVenta IS NOT NULL ORDER BY anio DESC");
    $ciudades  = DB::select("SELECT DISTINCT ciudad FROM sucursal WHERE ciudad IS NOT NULL ORDER BY ciudad");
    $categorias = DB::select("SELECT DISTINCT nombreCategoria AS nombre_categoria FROM categoria WHERE nombreCategoria IS NOT NULL ORDER BY nombre_categoria");
    return view('dashboard', compact('anios', 'ciudades', 'categorias'));
})->middleware(['auth', 'verified'])->name('dashboard');

// ── Perfil ────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ── API Dashboard (AJAX) ──────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->prefix('api/dashboard')->group(function () {

    // KPIs
    Route::get('/kpis', function (Request $request) {
        [$where, $params] = whereBase($request);
        $row = DB::selectOne("
            SELECT
                COUNT(DISTINCT v.IDVenta)::INT                    AS total_pedidos,
                COALESCE(SUM(v.montoTotal),0)::NUMERIC            AS ingresos_brutos,
                COALESCE(SUM(v.descuentoAplicado),0)::NUMERIC     AS total_descuentos,
                COALESCE(ROUND(AVG(v.montoTotal)::NUMERIC,2),0)   AS ticket_promedio,
                COUNT(DISTINCT v.IDCliente)::INT                  AS clientes_unicos
            FROM venta v
            LEFT JOIN empleadoSucursal es ON v.IDEmpleado = es.IDEmpleado
            LEFT JOIN sucursal s          ON es.IDSucursal = s.IDSucursal
            $where
        ", $params);
        return response()->json($row);
    });

    // Gráficos
    Route::get('/graficos', function (Request $request) {

        // Evolución mensual
        [$wM, $pM] = whereBase($request);
        $ventasMensuales = DB::select("
            SELECT EXTRACT(YEAR FROM v.fechaVenta)::INT  AS anio,
                   EXTRACT(MONTH FROM v.fechaVenta)::INT AS mes,
                   COALESCE(SUM(v.montoTotal),0)::NUMERIC AS ventas
            FROM venta v
            LEFT JOIN empleadoSucursal es ON v.IDEmpleado = es.IDEmpleado
            LEFT JOIN sucursal s ON es.IDSucursal = s.IDSucursal
            $wM GROUP BY anio,mes ORDER BY anio,mes
        ", $pM);

        // Ventas por categoría (suma de subtotales de línea)
        [$wC, $pC] = whereCat($request);
        $ventasCategoria = DB::select("
            SELECT COALESCE(cat.nombreCategoria,'Sin Categoría') AS categoria,
                   COALESCE(SUM(dv.subtotal),0)::NUMERIC AS ventas
            FROM venta v
            LEFT JOIN empleadoSucursal es ON v.IDEmpleado = es.IDEmpleado
            LEFT JOIN sucursal s ON es.IDSucursal = s.IDSucursal
            JOIN detalleVenta dv  ON v.IDVenta    = dv.IDVenta
            JOIN producto p       ON dv.IDProducto = p.IDProducto
            LEFT JOIN categoria cat ON p.IDProducto = cat.IDProducto
            $wC GROUP BY cat.nombreCategoria ORDER BY ventas DESC LIMIT 6
        ", $pC);

        // Top productos
        [$wP, $pP] = whereCat($request);
        $topProductos = DB::select("
            SELECT COALESCE(p.nombreProducto,'Sin nombre') AS producto,
                   COALESCE(SUM(dv.subtotal),0)::NUMERIC  AS ventas
            FROM venta v
            LEFT JOIN empleadoSucursal es ON v.IDEmpleado = es.IDEmpleado
            LEFT JOIN sucursal s ON es.IDSucursal = s.IDSucursal
            JOIN detalleVenta dv  ON v.IDVenta    = dv.IDVenta
            JOIN producto p       ON dv.IDProducto = p.IDProducto
            LEFT JOIN categoria cat ON p.IDProducto = cat.IDProducto
            $wP GROUP BY p.nombreProducto ORDER BY ventas DESC LIMIT 8
        ", $pP);

        // Ventas por sucursal
        [$wS, $pS] = whereBase($request);
        $ventasSucursal = DB::select("
            SELECT COALESCE(s.nombreSucursal,'Sin Sucursal') AS sucursal,
                   COALESCE(SUM(v.montoTotal),0)::NUMERIC   AS ventas,
                   COUNT(DISTINCT v.IDVenta)::INT             AS total_ventas
            FROM venta v
            LEFT JOIN empleadoSucursal es ON v.IDEmpleado = es.IDEmpleado
            LEFT JOIN sucursal s          ON es.IDSucursal = s.IDSucursal
            $wS GROUP BY s.nombreSucursal ORDER BY ventas DESC
        ", $pS);

        return response()->json(compact('ventasMensuales','ventasCategoria','topProductos','ventasSucursal'));
    });

    // Detalle para tabla
    Route::get('/detalle', function (Request $request) {
        [$where, $params] = whereBase($request);
        $rows = DB::select("
            SELECT v.IDVenta                                              AS id_venta,
                   TO_CHAR(v.fechaVenta,'DD/MM/YYYY')                   AS fecha,
                   (c.nombre||' '||COALESCE(c.apellidoPaterno,''))       AS cliente,
                   COALESCE(s.ciudad,'—')                                AS ciudad,
                   COALESCE(s.nombreSucursal,'—')                        AS sucursal,
                   (e.nombre||' '||COALESCE(e.apellidoPaterno,''))       AS empleado,
                   v.montoTotal::NUMERIC                                  AS monto_total,
                   COALESCE(v.descuentoAplicado,0)::NUMERIC              AS descuento,
                   COALESCE(pg.estadoPago,'—')                           AS estado_pago
            FROM venta v
            JOIN cliente  c  ON v.IDCliente  = c.IDCliente
            JOIN empleado e  ON v.IDEmpleado = e.IDEmpleado
            LEFT JOIN pago pg ON v.IDPago = pg.IDPago
            LEFT JOIN empleadoSucursal es ON v.IDEmpleado = es.IDEmpleado
            LEFT JOIN sucursal s          ON es.IDSucursal = s.IDSucursal
            $where
            ORDER BY v.fechaVenta DESC LIMIT 200
        ", $params);
        return response()->json($rows);
    });
});

// ─────────────────────────────────────────────────────────────────────
// Helpers de filtro
// ─────────────────────────────────────────────────────────────────────
if (!function_exists('whereBase')) {
    function whereBase(Request $r): array {
        $c = []; $p = [];
        if ($r->filled('anio'))   { $c[] = "EXTRACT(YEAR FROM v.fechaVenta) = ?"; $p[] = (int)$r->anio; }
        if ($r->filled('ciudad')) { $c[] = "s.ciudad = ?";                         $p[] = $r->ciudad;    }
        if ($r->filled('categoria')) {
            $c[] = "EXISTS (SELECT 1 FROM detalleVenta _dv JOIN producto _p ON _dv.IDProducto=_p.IDProducto JOIN categoria _cat ON _p.IDProducto=_cat.IDProducto WHERE _dv.IDVenta=v.IDVenta AND _cat.nombreCategoria=?)";
            $p[] = $r->categoria;
        }
        return [$c ? 'WHERE '.implode(' AND ',$c) : '', $p];
    }
}
if (!function_exists('whereCat')) {
    function whereCat(Request $r): array {
        $c = []; $p = [];
        if ($r->filled('anio'))      { $c[] = "EXTRACT(YEAR FROM v.fechaVenta) = ?"; $p[] = (int)$r->anio; }
        if ($r->filled('ciudad'))    { $c[] = "s.ciudad = ?";                         $p[] = $r->ciudad;    }
        if ($r->filled('categoria')) { $c[] = "cat.nombreCategoria = ?";               $p[] = $r->categoria; }
        return [$c ? 'WHERE '.implode(' AND ',$c) : '', $p];
    }
}

require __DIR__.'/auth.php';