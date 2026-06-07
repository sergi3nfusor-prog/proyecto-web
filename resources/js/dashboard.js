// =========================================================
// INSTANCIAS GLOBALES
// =========================================================
let chartVentasCategoria  = null;
let chartGananciasRegion  = null;
let chartEvolucionMensual = null;
let chartTopProductos     = null;
let tabla                 = null;

const BASE_URL = window.AppURL || '';

// =========================================================
// 1. CARGA DE DATOS (REFACTORIZADA)
// =========================================================
async function cargarDatos() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.classList.add('active');

    const params = new URLSearchParams({
        anio:      document.getElementById('sel-year').value,
        ciudad:    document.getElementById('sel-region').value,
        categoria: document.getElementById('sel-category').value,
    }).toString();

    try {
        // 1. Cargamos KPIs (Lo más importante)
        const rK = await fetch(`${BASE_URL}/api/kpis?${params}`);
        if (rK.ok) {
            const k = await rK.json();
            animateValue('kpi-total-ventas',    k.totalVentas,    { decimalPlaces: 2, prefix: '$' });
            animateValue('kpi-total-ganancias', k.totalGanancias, { decimalPlaces: 2, prefix: '$' });
            animateValue('kpi-total-pedidos',   k.totalPedidos);
            animateValue('kpi-margen',          k.margenRentabilidad, { decimalPlaces: 2, suffix: '%' });
        }

        // 2. Cargamos Gráficos
        const rG = await fetch(`${BASE_URL}/api/graficos?${params}`);
        if (rG.ok) {
            const g = await rG.json();
            renderVentasCategoria(g.ventasCategoria);
            renderGananciasRegion(g.gananciasRegion);
            renderEvolucionMensual(g.ventasMensuales);
            renderTopProductos(g.topProductos);
        }

        // 3. Cargamos Tabla
        const rD = await fetch(`${BASE_URL}/api/detalle?${params}`);
        if (rD.ok) {
            const d = await rD.json();
            renderTabla(d);
        }

    } catch (err) {
        console.error('Error Dashboard:', err);
    } finally {
        if (overlay) overlay.classList.remove('active');
    }
}

// =========================================================
// 2. RENDERIZADO DE COMPONENTES
// =========================================================
function animateValue(id, end, opts = {}) {
    const el = document.getElementById(id);
    if (!el) return;
    const val = parseFloat(end) || 0;
    const CU = (window.countUp && window.countUp.CountUp) ? window.countUp.CountUp : (window.CountUp ? window.CountUp : null);
    if (CU) {
        if (!window.counts) window.counts = {};
        if (!window.counts[id]) window.counts[id] = new CU(id, val, { duration: 1.5, separator: ',', decimal: '.', ...opts });
        else window.counts[id].update(val);
        window.counts[id].start();
    } else {
        el.textContent = (opts.prefix||'') + val.toLocaleString() + (opts.suffix||'');
    }
}

function renderVentasCategoria(data) {
    if (!data) return;
    const ctx = document.getElementById('chartVentasCategoria');
    if (!ctx) return;
    if (chartVentasCategoria) chartVentasCategoria.destroy();
    chartVentasCategoria = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map(d => d.categoria),
            datasets: [{
                data: data.map(d => d.ventas),
                backgroundColor: ['#bc0d32','#e61e4d','#ff4d79','#ff80a0','#ffb3c6'],
                borderColor: '#000', borderWidth: 2
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: '#ccc' } } } }
    });
}

function renderGananciasRegion(data) {
    if (!data) return;
    const ctx = document.getElementById('chartGananciasRegion');
    if (!ctx) return;
    if (chartGananciasRegion) chartGananciasRegion.destroy();
    chartGananciasRegion = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.region),
            datasets: [{ label: 'Ganancias', data: data.map(d => d.ganancias), backgroundColor: '#bc0d32' }]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, grid: { color: '#222' } } } }
    });
}

function renderEvolucionMensual(data) {
    if (!data) return;
    const ctx = document.getElementById('chartEvolucionMensual');
    if (!ctx) return;
    if (chartEvolucionMensual) chartEvolucionMensual.destroy();
    chartEvolucionMensual = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => `${d.anio}-${d.mes}`),
            datasets: [{ label: 'Ventas', data: data.map(d => d.ventas), borderColor: '#bc0d32', fill: true, backgroundColor: 'rgba(188,13,50,0.1)', tension: 0.4 }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
}

function renderTopProductos(data) {
    if (!data) return;
    const ctx = document.getElementById('chartTopProductos');
    if (!ctx) return;
    if (chartTopProductos) chartTopProductos.destroy();
    chartTopProductos = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.producto.substring(0,15)),
            datasets: [{ label: 'Ventas', data: data.map(d => d.ventas), backgroundColor: 'rgba(188,13,50,0.6)' }]
        },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false }
    });
}

function renderTabla(data) {
    if (!data) return;
    if (tabla) tabla.destroy();
    const tbody = document.querySelector('#tablaDatos tbody');
    if (!tbody) return;
    tbody.innerHTML = '';
    data.forEach(item => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>#${item.order_id}</td>
            <td>${item.fecha}</td>
            <td>${item.cliente}</td>
            <td>${item.region}</td>
            <td>${item.categoria}</td>
            <td>${item.producto}</td>
            <td>$${parseFloat(item.ventas).toFixed(2)}</td>
            <td style="color: #00c853; font-weight: bold;">$${parseFloat(item.ganancia).toFixed(2)}</td>
        `;
        tbody.appendChild(tr);
    });
    if (window.DataTable) {
        tabla = new DataTable('#tablaDatos', { pageLength: 8, destroy: true, language: { search: 'Buscar:' } });
    }
}

// =========================================================
// 3. EVENTOS
// =========================================================
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btnFiltrar')?.addEventListener('click', cargarDatos);
    document.getElementById('btnExportar')?.addEventListener('click', () => {
        window.location.href = `${BASE_URL}/api/export?${new URLSearchParams({
            anio:      document.getElementById('sel-year').value,
            ciudad:    document.getElementById('sel-region').value,
            categoria: document.getElementById('sel-category').value,
        }).toString()}`;
    });
    cargarDatos(); // Carga inicial
});
