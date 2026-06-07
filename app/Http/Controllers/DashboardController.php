<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $anios = DB::select("SELECT DISTINCT EXTRACT(YEAR FROM fechaventa)::INT AS anio FROM venta WHERE fechaventa IS NOT NULL ORDER BY anio DESC");
        $ciudades = DB::select("SELECT DISTINCT ciudad FROM sucursal WHERE ciudad IS NOT NULL ORDER BY ciudad");
        $categorias = DB::select("SELECT DISTINCT nombrecategoria AS nombre_categoria FROM categoria WHERE nombrecategoria IS NOT NULL ORDER BY nombre_categoria");

        return view('dashboard', compact('anios', 'ciudades', 'categorias'));
    }

    public function kpis(Request $request): \Illuminate\Http\JsonResponse
    {
        [$where, $params, $joins] = $this->buildWhere($request);
        $row = DB::selectOne("
            SELECT
                COALESCE(SUM(v.montototal), 0)::NUMERIC                  AS \"totalVentas\",
                COALESCE(SUM(v.montototal * 0.3), 0)::NUMERIC            AS \"totalGanancias\",
                COUNT(DISTINCT v.idventa)::INT                          AS \"totalPedidos\",
                COALESCE(ROUND((SUM(v.montototal * 0.3) / NULLIF(SUM(v.montototal), 0)) * 100, 2), 0) AS \"margenRentabilidad\"
            FROM venta v {$joins} {$where}
        ", $params);
        return response()->json($row);
    }

    public function graficos(Request $request): \Illuminate\Http\JsonResponse
    {
        [$where, $params, $joins] = $this->buildWhere($request);

        $ventasCategoria = DB::select("
            SELECT cat.nombrecategoria AS categoria, COALESCE(SUM(dv.subtotal), 0)::NUMERIC AS ventas
            FROM venta v
            JOIN detalleventa dv ON v.idventa = dv.idventa
            JOIN producto p ON dv.idproducto = p.idproducto
            JOIN categoria cat ON p.idproducto = cat.idproducto
            {$joins} {$where}
            GROUP BY cat.nombrecategoria ORDER BY ventas DESC
        ", $params);

        $gananciasRegion = DB::select("
            SELECT s.ciudad AS region, COALESCE(SUM(v.montototal * 0.3), 0)::NUMERIC AS ganancias
            FROM venta v
            JOIN empleadosucursal es ON v.idempleado = es.idempleado
            JOIN sucursal s ON es.idsucursal = s.idsucursal
            {$where}
            GROUP BY s.ciudad ORDER BY ganancias DESC
        ", $params);

        $ventasMensuales = DB::select("
            SELECT EXTRACT(YEAR FROM v.fechaventa)::INT AS anio, EXTRACT(MONTH FROM v.fechaventa)::INT AS mes, COALESCE(SUM(v.montototal), 0)::NUMERIC AS ventas
            FROM venta v {$joins} {$where}
            GROUP BY anio, mes ORDER BY anio, mes
        ", $params);

        $topProductos = DB::select("
            SELECT p.nombreproducto AS producto, COALESCE(SUM(dv.subtotal), 0)::NUMERIC AS ventas
            FROM venta v
            JOIN detalleventa dv ON v.idventa = dv.idventa
            JOIN producto p ON dv.idproducto = p.idproducto
            {$joins} {$where}
            GROUP BY p.nombreproducto ORDER BY ventas DESC LIMIT 5
        ", $params);

        return response()->json([
            'ventasCategoria' => $ventasCategoria,
            'gananciasRegion' => $gananciasRegion,
            'ventasMensuales' => $ventasMensuales,
            'topProductos'    => $topProductos
        ]);
    }

    public function detalle(Request $request): \Illuminate\Http\JsonResponse
    {
        [$where, $params, $joins] = $this->buildWhere($request);
        $rows = DB::select("
            SELECT
                v.idventa AS order_id, TO_CHAR(v.fechaventa, 'YYYY-MM-DD') AS fecha,
                c.nombre || ' ' || c.apellidopaterno AS cliente,
                COALESCE(s.ciudad, 'Sin Sede') AS region,
                cat.nombrecategoria AS categoria, p.nombreproducto AS producto,
                dv.subtotal::FLOAT AS ventas, (dv.subtotal * 0.3)::FLOAT AS ganancia
            FROM venta v
            JOIN cliente c ON v.idcliente = c.idcliente
            JOIN detalleventa dv ON v.idventa = dv.idventa
            JOIN producto p ON dv.idproducto = p.idproducto
            JOIN categoria cat ON p.idproducto = cat.idproducto
            LEFT JOIN empleadosucursal es ON v.idempleado = es.idempleado
            LEFT JOIN sucursal s ON es.idsucursal = s.idsucursal
            {$where} ORDER BY v.fechaventa DESC LIMIT 200
        ", $params);
        return response()->json($rows);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$where, $params, $joins] = $this->buildWhere($request);
        $rows = DB::select("
            SELECT
                v.idventa AS order_id, TO_CHAR(v.fechaventa, 'DD/MM/YYYY') AS fecha,
                c.nombre || ' ' || c.apellidopaterno AS cliente,
                COALESCE(s.ciudad, 'Sin Sede') AS region,
                cat.nombrecategoria AS categoria, p.nombreproducto AS producto,
                dv.subtotal::FLOAT AS ventas, (dv.subtotal * 0.3)::FLOAT AS ganancia
            FROM venta v
            JOIN cliente c ON v.idcliente = c.idcliente
            JOIN detalleventa dv ON v.idventa = dv.idventa
            JOIN producto p ON dv.idproducto = p.idproducto
            JOIN categoria cat ON p.idproducto = cat.idproducto
            LEFT JOIN empleadosucursal es ON v.idempleado = es.idempleado
            LEFT JOIN sucursal s ON es.idsucursal = s.idsucursal
            {$where} ORDER BY v.fechaventa DESC
        ", $params);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Order_ID', 'Fecha', 'Cliente', 'Region', 'Categoria', 'Producto', 'Ventas', 'Ganancia']);
            foreach ($rows as $row) {
                fputcsv($handle, [$row->order_id, $row->fecha, $row->cliente, $row->region, $row->categoria, $row->producto, $row->ventas, $row->ganancia]);
            }
            fclose($handle);
        }, 'reporte_ventas.csv');
    }

    private function buildWhere(Request $request): array
    {
        $w = ["1=1"];
        $p = [];
        $joins = "";
        if ($request->filled('anio')) { $w[] = "EXTRACT(YEAR FROM v.fechaventa) = ?"; $p[] = $request->anio; }
        if ($request->filled('ciudad')) {
            $joins = " LEFT JOIN empleadosucursal es ON v.idempleado = es.idempleado LEFT JOIN sucursal s ON es.idsucursal = s.idsucursal ";
            $w[] = "s.ciudad = ?"; $p[] = $request->ciudad;
        }
        if ($request->filled('categoria')) {
            $w[] = "v.idventa IN (SELECT dv2.idventa FROM detalleventa dv2 JOIN producto p2 ON dv2.idproducto = p2.idproducto JOIN categoria cat2 ON p2.idproducto = cat2.idproducto WHERE cat2.nombrecategoria = ?)";
            $p[] = $request->categoria;
        }
        return ["WHERE " . implode(" AND ", $w), $p, $joins];
    }
}
