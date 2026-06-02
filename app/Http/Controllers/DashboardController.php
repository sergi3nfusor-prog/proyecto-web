<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // VISTA PRINCIPAL
    // ─────────────────────────────────────────────────────────────────────────
    public function index(): \Illuminate\View\View
    {
        $anios     = DB::select("
            SELECT DISTINCT EXTRACT(YEAR FROM fechaventa)::INT AS anio
            FROM venta
            WHERE fechaventa IS NOT NULL
            ORDER BY anio DESC
        ");

        $ciudades  = DB::select("
            SELECT DISTINCT ciudad
            FROM sucursal
            WHERE ciudad IS NOT NULL
            ORDER BY ciudad
        ");

        $categorias = DB::select("
            SELECT DISTINCT nombrecategoria AS nombre_categoria
            FROM categoria
            WHERE nombrecategoria IS NOT NULL
            ORDER BY nombre_categoria
        ");

        return view('dashboard', compact('anios', 'ciudades', 'categorias'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API — KPIs
    // ─────────────────────────────────────────────────────────────────────────
    public function kpis(Request $request): \Illuminate\Http\JsonResponse
    {
        [$where, $params] = $this->buildWhere($request);

        $row = DB::selectOne("
            SELECT
                SUM(v.montototal)::NUMERIC              AS \"totalVentas\",
                SUM(v.montototal * 0.7)::NUMERIC         AS \"totalGanancias\",
                COUNT(DISTINCT v.idventa)::INT          AS \"totalPedidos\",
                ROUND((SUM(v.montototal * 0.3) / NULLIF(SUM(v.montototal), 0)) * 100, 2) AS \"margenRentabilidad\"
            FROM venta v
            LEFT JOIN empleadosucursal es ON v.idempleado = es.idempleado
            LEFT JOIN sucursal s          ON es.idsucursal = s.idsucursal
            {$where}
        ", $params);

        return response()->json($row);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API — GRÁFICOS
    // ─────────────────────────────────────────────────────────────────────────
    public function graficos(Request $request): \Illuminate\Http\JsonResponse
    {
        [$where, $params] = $this->buildWhere($request);

        // 1. Gráfico Dona: Ventas por Categoría
        $ventasCategoria = DB::select("
            SELECT 
                cat.nombrecategoria AS categoria,
                SUM(dv.subtotal)::NUMERIC AS ventas
            FROM venta v
            JOIN detalleventa dv ON v.idventa = dv.idventa
            JOIN producto p ON dv.idproducto = p.idproducto
            JOIN categoria cat ON p.idproducto = cat.idproducto
            LEFT JOIN empleadosucursal es ON v.idempleado = es.idempleado
            LEFT JOIN sucursal s ON es.idsucursal = s.idsucursal
            {$where}
            GROUP BY cat.nombrecategoria
            ORDER BY ventas DESC
        ", $params);

        // 2. Gráfico Barras: Ganancias por Región (Ciudad)
        $gananciasRegion = DB::select("
            SELECT 
                s.ciudad AS region,
                SUM(v.montototal * 0.3)::NUMERIC AS ganancias
            FROM venta v
            JOIN empleadosucursal es ON v.idempleado = es.idempleado
            JOIN sucursal s ON es.idsucursal = s.idsucursal
            {$where}
            GROUP BY s.ciudad
            ORDER BY ganancias DESC
        ", $params);

        // 3. Gráfico Línea: Evolución Mensual
        $ventasMensuales = DB::select("
            SELECT 
                EXTRACT(YEAR FROM v.fechaventa)::INT AS anio,
                EXTRACT(MONTH FROM v.fechaventa)::INT AS mes,
                SUM(v.montototal)::NUMERIC AS ventas
            FROM venta v
            LEFT JOIN empleadosucursal es ON v.idempleado = es.idempleado
            LEFT JOIN sucursal s ON es.idsucursal = s.idsucursal
            {$where}
            GROUP BY anio, mes
            ORDER BY anio, mes
        ", $params);

        // 4. Gráfico Horizontal: Top Productos
        $topProductos = DB::select("
            SELECT 
                p.nombreproducto AS producto,
                SUM(dv.subtotal)::NUMERIC AS ventas
            FROM venta v
            JOIN detalleventa dv ON v.idventa = dv.idventa
            JOIN producto p ON dv.idproducto = p.idproducto
            LEFT JOIN empleadosucursal es ON v.idempleado = es.idempleado
            LEFT JOIN sucursal s ON es.idsucursal = s.idsucursal
            {$where}
            GROUP BY p.nombreproducto
            ORDER BY ventas DESC
            LIMIT 5
        ", $params);

        return response()->json([
            'ventasCategoria' => $ventasCategoria,
            'gananciasRegion' => $gananciasRegion,
            'ventasMensuales' => $ventasMensuales,
            'topProductos'     => $topProductos
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    // API — TABLA DE DETALLE
    // ─────────────────────────────────────────────────────────────────────────
    public function detalle(Request $request): \Illuminate\Http\JsonResponse
    {
        [$where, $params] = $this->buildWhere($request);

        $rows = DB::select("
            SELECT
                v.idventa                                      AS order_id,
                TO_CHAR(v.fechaventa, 'YYYY-MM-DD')           AS fecha,
                c.nombre || ' ' || c.apellidopaterno           AS cliente,
                s.ciudad                                       AS region,
                cat.nombrecategoria                            AS categoria,
                p.nombreproducto                               AS producto,
                dv.subtotal::FLOAT                             AS ventas,
                (dv.subtotal * 0.3)::FLOAT                     AS ganancia
            FROM venta v
            JOIN cliente c            ON v.idcliente   = c.idcliente
            JOIN empleadosucursal es  ON v.idempleado  = es.idempleado
            JOIN sucursal s           ON es.idsucursal = s.idsucursal
            JOIN detalleventa dv      ON v.idventa     = dv.idventa
            JOIN producto p           ON dv.idproducto = p.idproducto
            JOIN categoria cat        ON p.idproducto  = cat.idproducto
            {$where}
            ORDER BY v.fechaventa DESC
            LIMIT 200
        ", $params);

        return response()->json($rows);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EXPORTAR CSV
    // ─────────────────────────────────────────────────────────────────────────
    public function exportCsv(Request $request): StreamedResponse
    {
        [$where, $params] = $this->buildWhere($request);

        $rows = DB::select("
            SELECT
                v.idventa                                      AS order_id,
                TO_CHAR(v.fechaventa, 'DD/MM/YYYY')           AS fecha,
                c.nombre || ' ' || c.apellidopaterno           AS cliente,
                s.ciudad                                       AS region,
                cat.nombrecategoria                            AS categoria,
                p.nombreproducto                               AS producto,
                dv.subtotal::FLOAT                             AS ventas,
                (dv.subtotal * 0.3)::FLOAT                     AS ganancia
            FROM venta v
            JOIN cliente c            ON v.idcliente   = c.idcliente
            JOIN empleadosucursal es  ON v.idempleado  = es.idempleado
            JOIN sucursal s           ON es.idsucursal = s.idsucursal
            JOIN detalleventa dv      ON v.idventa     = dv.idventa
            JOIN producto p           ON dv.idproducto = p.idproducto
            JOIN categoria cat        ON p.idproducto  = cat.idproducto
            {$where}
            ORDER BY v.fechaventa DESC
        ", $params);

        $filename = 'reporte_ventas_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Order_ID', 'Fecha', 'Cliente', 'Region', 'Categoria', 'Producto', 'Ventas', 'Ganancia']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->order_id,
                    $row->fecha,
                    $row->cliente,
                    $row->region,
                    $row->categoria,
                    $row->producto,
                    $row->ventas,
                    $row->ganancia,
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    // HELPER PRIVADO — Construir cláusula WHERE
    // ─────────────────────────────────────────────────────────────────────────
    private function buildWhere(Request $request): array
    {
        $w = [];
        $p = [];

        if ($request->filled('anio')) {
            $w[] = "EXTRACT(YEAR FROM v.fechaventa) = ?";
            $p[] = $request->anio;
        }

        if ($request->filled('ciudad')) {
            $w[] = "s.ciudad = ?";
            $p[] = $request->ciudad;
        }

        if ($request->filled('categoria')) {
            $w[] = "v.idventa IN (
                SELECT dv2.idventa
                FROM detalleventa dv2
                JOIN producto p2   ON dv2.idproducto = p2.idproducto
                JOIN categoria cat2 ON p2.idproducto = cat2.idproducto
                WHERE cat2.nombrecategoria = ?
            )";
            $p[] = $request->categoria;
        }

        $clause = $w ? 'WHERE ' . implode(' AND ', $w) : '';

        return [$clause, $p];
    }
}
