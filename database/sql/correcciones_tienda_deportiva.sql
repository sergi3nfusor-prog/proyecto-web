-- =========================================================
-- CORRECCIONES TIENDA DEPORTIVA — PostgreSQL
-- Proyecto: Laravel 13 + PHP 8.3 + PostgreSQL
-- =========================================================
-- Ejecutar con: \c tienda_deportiva
-- =========================================================


-- =========================================================
-- CORRECCIÓN 1
-- ALTER TABLE: Añadir IDProducto a detalleVenta (si no existe aún)
-- =========================================================
ALTER TABLE detalleVenta ADD COLUMN IF NOT EXISTS IDProducto INT;
ALTER TABLE detalleVenta DROP CONSTRAINT IF EXISTS fk_detalle_producto;
ALTER TABLE detalleVenta ADD CONSTRAINT fk_detalle_producto 
    FOREIGN KEY (IDProducto) REFERENCES producto(IDProducto);


-- =========================================================
-- CORRECCIÓN 2
-- FUNCIÓN 18 (fn_empleados_de_sucursal)
-- PROBLEMA: JOINs múltiples sin DISTINCT generaban filas duplicadas
-- cuando un empleado tenía más de un turno o estado registrado.
-- SOLUCIÓN: Se agrega DISTINCT ON (e.IDEmpleado) + ORDER BY requerido.
-- =========================================================
CREATE OR REPLACE FUNCTION fn_empleados_de_sucursal(p_id_sucursal INT)
RETURNS TABLE(
    id_empleado     INT,
    nombre_completo TEXT,
    turno           VARCHAR(45),
    estado          VARCHAR(45)
) AS $$
BEGIN
    RETURN QUERY
    SELECT DISTINCT ON (e.IDEmpleado)
        e.IDEmpleado,
        e.nombre || ' ' || e.apellidoPaterno,
        t.nombreTurno,
        ee.nombreEstado
    FROM empleadoSucursal es
    JOIN empleado e        ON es.IDEmpleado = e.IDEmpleado
    JOIN estadoEmpleado ee ON e.IDEmpleado  = ee.IDEmpleado
    JOIN empleadoTurno et  ON e.IDEmpleado  = et.IDEmpleado
    JOIN turno t           ON et.IDTurno    = t.IDTurno
    WHERE es.IDSucursal = p_id_sucursal
    ORDER BY e.IDEmpleado;
END;
$$ LANGUAGE plpgsql;


-- =========================================================
-- CORRECCIÓN 3
-- FUNCIÓN 21 (fn_resumen_ventas_mes)
-- PROBLEMA: ON COMMIT DROP destruye la tabla temporal antes de que
-- RETURN QUERY pueda leerla en modo autocommit de PostgreSQL.
-- SOLUCIÓN: Eliminamos la tabla temporal y usamos RETURN QUERY directo.
-- =========================================================
CREATE OR REPLACE FUNCTION fn_resumen_ventas_mes(p_anio INT)
RETURNS TABLE(
    mes         INT,
    num_ventas  BIGINT,
    total_bs    NUMERIC(18,2)
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        EXTRACT(MONTH FROM fechaVenta)::INT,
        COUNT(*),
        ROUND(SUM(montoTotal)::NUMERIC, 2)
    FROM venta
    WHERE EXTRACT(YEAR FROM fechaVenta) = p_anio
    GROUP BY EXTRACT(MONTH FROM fechaVenta)
    ORDER BY 1;
END;
$$ LANGUAGE plpgsql;


-- =========================================================
-- CORRECCIÓN 4
-- PROCEDIMIENTO sp_registrar_venta_integral
-- PROBLEMA A: ROLLBACK explícito inválido dentro del bloque EXCEPTION.
--             PostgreSQL hace rollback automáticamente; poner ROLLBACK
--             explícito provoca error o comportamiento indefinido.
-- PROBLEMA B: El INSERT de detalleVenta no incluía IDProducto,
--             haciendo que el trigger tr_venta_actualiza_stock fallara
--             siempre porque NEW.IDProducto = NULL.
-- SOLUCIÓN: Eliminar ROLLBACK del EXCEPTION y agregar IDProducto al INSERT.
-- =========================================================
CREATE OR REPLACE PROCEDURE sp_registrar_venta_integral(
    p_id_cliente      INT,
    p_id_empleado     INT,
    p_id_producto     INT,
    p_cantidad        INT,
    p_precio_unitario DECIMAL(18,2),
    p_descuento_venta DECIMAL(18,2),
    p_razon_social    VARCHAR(45),
    p_nit             VARCHAR(45)
)
AS $$
DECLARE
    v_id_pago    INT;
    v_id_factura INT;
    v_id_venta   INT;
    v_monto_total DECIMAL(18,2);
    v_impuesto    DECIMAL(18,2);
BEGIN
    -- 1. Cálculo de montos
    v_monto_total := (p_cantidad * p_precio_unitario) - p_descuento_venta;
    v_impuesto    := v_monto_total * 0.13;

    -- 2. Registrar el Pago
    INSERT INTO pago (fechaPago, montoPago, estadoPago)
    VALUES (CURRENT_DATE, v_monto_total, 'PAGADO')
    RETURNING IDPago INTO v_id_pago;

    -- 3. Generar la Factura
    INSERT INTO factura (numeroFactura, cuf, nit, razonSocial, total)
    VALUES (
        floor(random() * 900000 + 100000)::INT,
        md5(random()::text),
        p_nit,
        p_razon_social,
        v_monto_total
    )
    RETURNING IDFactura INTO v_id_factura;

    -- 4. Registrar la Venta (Cabecera)
    INSERT INTO venta (
        montoTotal, impuesto, descuentoAplicado,
        fechaVenta, precioMomento,
        IDPago, IDEmpleado, IDFactura, IDCliente
    )
    VALUES (
        v_monto_total, v_impuesto, p_descuento_venta,
        CURRENT_DATE, p_precio_unitario,
        v_id_pago, p_id_empleado, v_id_factura, p_id_cliente
    )
    RETURNING IDVenta INTO v_id_venta;

    -- 5. Registrar el Detalle de Venta
    -- ✅ CORRECCIÓN: Se agrega IDProducto para que el trigger
    --    tr_venta_actualiza_stock pueda descontar el stock correctamente.
    INSERT INTO detalleVenta (
        cantidad, precioUnitario, descuento,
        fechaPedido, IDVenta, IDProducto
    )
    VALUES (
        p_cantidad, p_precio_unitario, p_descuento_venta,
        CURRENT_DATE, v_id_venta, p_id_producto   -- ← IDProducto incluido
    );

    -- Confirmación de transacción (válido en PROCEDURE PostgreSQL 11+)
    COMMIT;

    RAISE NOTICE 'Venta registrada con éxito. Venta ID: %, Factura ID: %',
                 v_id_venta, v_id_factura;

EXCEPTION
    WHEN OTHERS THEN
        -- ✅ CORRECCIÓN: Se elimina ROLLBACK explícito.
        --    PostgreSQL revierte automáticamente al lanzar la excepción.
        RAISE EXCEPTION 'Error en la transacción de venta: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;


-- =========================================================
-- CORRECCIÓN 5
-- CUBO OLAP 1 — Rendimiento Temporal y Categorías
-- PROBLEMA: JOIN a producto sin condición real con venta
--           (p.IDProveedor IS NOT NULL) generaba un producto
--           cartesiano masivo — resultados completamente incorrectos.
-- SOLUCIÓN: Relacionar venta → detalleVenta → producto → categoria.
-- =========================================================
-- Cubo corregido (ejecutar directamente como query de análisis):
SELECT 
    EXTRACT(YEAR  FROM v.fechaVenta)::INT  AS anio,
    EXTRACT(MONTH FROM v.fechaVenta)::INT  AS mes,
    cat.nombreCategoria,
    COUNT(DISTINCT v.IDVenta)              AS nro_transacciones,
    SUM(v.montoTotal)                      AS ingresos_brutos,
    SUM(v.descuentoAplicado)               AS total_descuentos,
    SUM(v.montoTotal - v.descuentoAplicado) AS ingresos_netos
FROM venta v
JOIN cliente c      ON v.IDCliente    = c.IDCliente
-- ✅ CORRECCIÓN: relación correcta via detalleVenta con IDProducto
JOIN detalleVenta dv  ON v.IDVenta      = dv.IDVenta
JOIN producto p       ON dv.IDProducto  = p.IDProducto
JOIN categoria cat    ON p.IDProducto   = cat.IDProducto
GROUP BY anio, mes, cat.nombreCategoria
ORDER BY anio DESC, mes DESC, ingresos_netos DESC;


-- =========================================================
-- CORRECCIÓN 6
-- CUBO OLAP 3 — Salud de Stock y Movimiento
-- PROBLEMA: LEFT JOIN venta v ON v.IDVenta IS NOT NULL
--           es siempre TRUE → genera un CROSS JOIN con todas
--           las ventas para cada producto → totales incorrectos.
-- SOLUCIÓN: Relacionar directamente producto → detalleVenta
--           usando el IDProducto añadido al detalle.
-- =========================================================
SELECT 
    p.marca,
    p.nombreProducto,
    i.stockActual,
    i.stockMinimo,
    COALESCE(SUM(dv.cantidad), 0) AS unidades_vendidas_historicas,
    CASE 
        WHEN i.stockActual <= i.stockMinimo           THEN 'REPOSICIÓN INMEDIATA'
        WHEN i.stockActual <= (i.stockMinimo * 1.5)   THEN 'RIESGO BAJO'
        ELSE                                               'STOCK SALUDABLE'
    END AS alerta_logistica
FROM producto p
JOIN inventario i ON p.IDInventario = i.IDInventario
-- ✅ CORRECCIÓN: relación directa por IDProducto, sin pasar por venta
LEFT JOIN detalleVenta dv ON p.IDProducto = dv.IDProducto
GROUP BY p.marca, p.nombreProducto, i.stockActual, i.stockMinimo
ORDER BY unidades_vendidas_historicas DESC NULLS LAST;


-- =========================================================
-- CORRECCIÓN 7
-- CUBO OLAP 4 — Geografía y Eficiencia Operativa
-- PROBLEMA: División SUM / COUNT sin protección ante COUNT = 0.
-- SOLUCIÓN: Envolver el COUNT en NULLIF(..., 0) para devolver
--           NULL en lugar de lanzar error de división por cero.
-- =========================================================
SELECT 
    s.ciudad,
    s.nombreSucursal,
    COUNT(DISTINCT v.IDVenta)   AS total_ventas,
    COUNT(DISTINCT es.IDEmpleado) AS nro_empleados,
    SUM(v.montoTotal)           AS recaudacion_total,
    -- ✅ CORRECCIÓN: NULLIF protege contra división por cero
    ROUND(SUM(v.montoTotal) / NULLIF(COUNT(DISTINCT es.IDEmpleado), 0), 2)
        AS productividad_por_empleado
FROM sucursal s
JOIN empleadoSucursal es ON s.IDSucursal = es.IDSucursal
JOIN venta v             ON es.IDEmpleado = v.IDEmpleado
GROUP BY s.ciudad, s.nombreSucursal
ORDER BY recaudacion_total DESC;


-- =========================================================
-- CORRECCIÓN 8 (OPCIONAL / BUENAS PRÁCTICAS)
-- CUBO OLAP 2 — Segmentación de clientes
-- PROBLEMA: BETWEEN 2000 AND 5000 incluye exactamente 5000 en 'Oro',
--           pero la condición Platino es > 5000 (excluye el 5000 exacto).
-- SOLUCIÓN: Usar >= para consistencia.
-- =========================================================
SELECT 
    c.IDCliente,
    c.nombre || ' ' || c.apellidoPaterno AS nombre_completo,
    COALESCE(pf.nivel, 'Sin Registro')   AS nivel_fidelidad,
    COUNT(v.IDVenta)                      AS total_compras,
    SUM(v.montoTotal)                     AS valor_total_vida,
    ROUND(AVG(v.montoTotal), 2)           AS ticket_promedio,
    -- ✅ CORRECCIÓN: >= en lugar de > para que 5000 caiga en Platino
    CASE 
        WHEN SUM(v.montoTotal) >= 5000                       THEN 'Platino'
        WHEN SUM(v.montoTotal) >= 2000                       THEN 'Oro'
        ELSE                                                      'Plata'
    END AS segmento_valor
FROM cliente c
LEFT JOIN programaFidelizacion pf ON c.IDCliente = pf.IDCliente
JOIN venta v                      ON c.IDCliente = v.IDCliente
GROUP BY c.IDCliente, c.nombre, c.apellidoPaterno, pf.nivel
ORDER BY valor_total_vida DESC;


-- =========================================================
-- FIN DE CORRECCIONES
-- =========================================================
