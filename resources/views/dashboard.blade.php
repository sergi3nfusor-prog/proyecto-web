<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quantum Analytics | Dark Luxury</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/countup.js@2.8.0/dist/countUp.umd.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <style>
        :root {
            --bg-deep: #050505;
            --bg-card: #0d0d0d;
            --primary: #bc0d32;
            --primary-glow: rgba(188, 13, 50, 0.4);
            --text-main: #ffffff;
            --text-dim: #888888;
            --border: rgba(255, 255, 255, 0.08);
            --sidebar-w: 280px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: var(--bg-deep); 
            color: var(--text-main); 
            font-family: 'Inter', sans-serif; 
            overflow-x: hidden; 
            display: flex;
        }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-w);
            height: 100vh;
            background: #000;
            border-right: 1px solid var(--border);
            padding: 30px;
            position: fixed;
            display: flex;
            flex-direction: column;
            z-index: 100;
        }

        .logo-section { display: flex; align-items: center; gap: 15px; margin-bottom: 50px; }
        .logo-box { width: 35px; height: 35px; background: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 20px; box-shadow: 0 0 15px var(--primary-glow); }
        .logo-text { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }

        .filter-group { margin-bottom: 25px; }
        .filter-label { display: block; color: var(--text-dim); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; font-weight: 600; }
        .filter-select { 
            width: 100%; background: #111; border: 1px solid var(--border); border-radius: 8px; 
            color: #fff; padding: 12px; font-size: 14px; outline: none; transition: 0.3s;
        }
        .filter-select:focus { border-color: var(--primary); box-shadow: 0 0 10px var(--primary-glow); }

        .btn-update {
            width: 100%; background: var(--primary); color: white; border: none; padding: 15px;
            border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.3s;
            margin-top: 20px; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;
        }
        .btn-update:hover { transform: translateY(-2px); box-shadow: 0 5px 20px var(--primary-glow); filter: brightness(1.1); }

        /* MAIN CONTENT */
        .main-content {
            margin-left: var(--sidebar-w);
            width: calc(100% - var(--sidebar-w));
            padding: 40px;
        }

        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-name { font-weight: 600; text-align: right; }
        .user-role { display: block; font-size: 11px; color: var(--text-dim); }
        .user-avatar { width: 45px; height: 45px; background: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-family: 'Outfit'; }

        /* KPIs */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .kpi-card { 
            background: var(--bg-card); border: 1px solid var(--border); padding: 25px; border-radius: 16px;
            transition: 0.3s; position: relative; overflow: hidden;
        }
        .kpi-card:hover { border-color: var(--primary); transform: translateY(-3px); }
        .kpi-label { color: var(--text-dim); font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 15px; display: block; }
        .kpi-value { font-size: 32px; font-weight: 800; font-family: 'Outfit'; }

        /* CHARTS */
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 40px; }
        .chart-container { 
            background: var(--bg-card); 
            border: 1px solid var(--border); 
            padding: 25px; 
            border-radius: 20px; 
            height: 350px; 
            position: relative;
        }
        .chart-title { font-size: 16px; font-weight: 700; margin-bottom: 20px; display: block; color: #ddd; }

        /* TABLE */
        .table-container { background: var(--bg-card); border: 1px solid var(--border); padding: 25px; border-radius: 20px; }
        table.dataTable { border-collapse: collapse !important; background: transparent !important; color: #ccc !important; }
        table.dataTable thead th { background: #111 !important; border-bottom: 1px solid var(--border) !important; color: var(--text-dim) !important; font-size: 11px !important; text-transform: uppercase !important; padding: 15px !important; }
        table.dataTable tbody td { border-bottom: 1px solid rgba(255,255,255,0.03) !important; padding: 15px !important; font-size: 13px !important; }
        .dataTables_wrapper .dataTables_filter input { background: #111 !important; border: 1px solid var(--border) !important; border-radius: 6px !important; color: white !important; }

        #loadingOverlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8); backdrop-filter: blur(5px);
            display: none; align-items: center; justify-content: center; z-index: 9999;
        }
        #loadingOverlay.active { display: flex; }
        .spinner { width: 40px; height: 40px; border: 4px solid var(--border); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <div id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo-section">
            <div class="logo-box">Q</div>
            <span class="logo-text">QUANTUM</span>
        </div>

        <p class="filter-label" style="margin-top: 20px;">Segmentación</p>

        <div class="filter-group">
            <label class="filter-label">Periodo (Año)</label>
            <select id="sel-year" class="filter-select">
                <option value="">Todo el Historial</option>
                @foreach($anios as $a) <option value="{{ $a->anio }}">{{ $a->anio }}</option> @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label class="filter-label">Ubicación (Ciudad)</label>
            <select id="sel-region" class="filter-select">
                <option value="">Todas las Sedes</option>
                @foreach($ciudades as $c) <option value="{{ $c->ciudad }}">{{ $c->ciudad }}</option> @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label class="filter-label">Categoría</label>
            <select id="sel-category" class="filter-select">
                <option value="">Todas las Líneas</option>
                @foreach($categorias as $cat) <option value="{{ $cat->nombre_categoria }}">{{ $cat->nombre_categoria }}</option> @endforeach
            </select>
        </div>

        <button id="btnFiltrar" class="btn-update">Actualizar Panel</button>

        <div style="margin-top: auto;">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" style="width: 100%; background: none; border: 1px solid #333; color: #555; padding: 10px; border-radius: 8px; cursor: pointer; font-size: 11px;">Cerrar Sesión</button>
            </form>
        </div>
    </aside>

    <!-- CONTENT -->
    <main class="main-content">
        <div class="header-top">
            <div>
                <h1 style="font-size: 2.2rem; font-weight: 800; letter-spacing: -1px;">Analytics Dashboard</h1>
                <p style="color: var(--text-dim);">Quantum Sports Intelligent Monitoring</p>
            </div>
            
            <div class="user-info">
                <button id="btnExportar" style="background: #111; color: var(--primary); border: 1px solid var(--primary); padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; margin-right: 15px;">Exportar CSV</button>
                <div class="user-name">
                    {{ Auth::user()->name }}
                    <span class="user-role">Administrator</span>
                </div>
                <div class="user-avatar">{{ substr(Auth::user()->name, 0, 1) }}</div>
            </div>
        </div>

        <!-- KPIs -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <span class="kpi-label">Ventas Totales</span>
                <div class="kpi-value" id="kpi-total-ventas">$0.00</div>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">Ganancias (30%)</span>
                <div class="kpi-value" id="kpi-total-ganancias" style="color: #00c853;">$0.00</div>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">Nro. Pedidos</span>
                <div class="kpi-value" id="kpi-total-pedidos" style="color: #bc0d32;">0</div>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">Margen Rent.</span>
                <div class="kpi-value" id="kpi-margen" style="color: #aa00ff;">0.00%</div>
            </div>
        </div>

        <!-- CHARTS -->
        <div class="charts-grid">
            <div class="chart-container">
                <span class="chart-title">Ventas por Categoría</span>
                <canvas id="chartVentasCategoria"></canvas>
            </div>
            <div class="chart-container">
                <span class="chart-title">Ganancias por Región</span>
                <canvas id="chartGananciasRegion"></canvas>
            </div>
        </div>

        <div class="charts-grid" style="grid-template-columns: 1fr;">
            <div class="chart-container">
                <span class="chart-title">Evolución Mensual</span>
                <canvas id="chartEvolucionMensual"></canvas>
            </div>
        </div>

        <!-- TABLE -->
        <div class="table-container">
            <span class="chart-title">Detalle de Operaciones</span>
            <table id="tablaDatos" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Región</th>
                        <th>Categoría</th>
                        <th>Producto</th>
                        <th>Venta</th>
                        <th>Ganancia</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </main>

    <script>
        const BASE_URL = "{{ url('/') }}";
        let charts = {};
        let dataTable = null;

        async function cargarDatos() {
            document.getElementById('loadingOverlay').classList.add('active');
            const query = new URLSearchParams({
                anio: document.getElementById('sel-year').value,
                ciudad: document.getElementById('sel-region').value,
                categoria: document.getElementById('sel-category').value,
            }).toString();

            try {
                // 1. KPIs
                const rK = await fetch(`${BASE_URL}/api/kpis?${query}`);
                const k = await rK.json();
                actualizarKPI('kpi-total-ventas', k.totalVentas, '$');
                actualizarKPI('kpi-total-ganancias', k.totalGanancias, '$');
                actualizarKPI('kpi-total-pedidos', k.totalPedidos, '');
                actualizarKPI('kpi-margen', k.margenRentabilidad, '', '%');

                // 2. GRÁFICOS
                const rG = await fetch(`${BASE_URL}/api/graficos?${query}`);
                const g = await rG.json();
                renderDona('chartVentasCategoria', g.ventasCategoria);
                renderBarras('chartGananciasRegion', g.gananciasRegion);
                renderLinea('chartEvolucionMensual', g.ventasMensuales);

                // 3. TABLA
                const rD = await fetch(`${BASE_URL}/api/detalle?${query}`);
                const d = await rD.json();
                renderTabla(d);

            } catch (e) {
                console.error(e);
            } finally {
                document.getElementById('loadingOverlay').classList.remove('active');
            }
        }

        function actualizarKPI(id, val, prefix = '', suffix = '') {
            const num = parseFloat(val) || 0;
            const el = document.getElementById(id);
            if (window.countUp && window.countUp.CountUp) {
                new window.countUp.CountUp(id, num, { prefix, suffix, decimalPlaces: 2 }).start();
            } else {
                el.innerText = prefix + num.toLocaleString() + suffix;
            }
        }

        function renderDona(id, data) {
            if (charts[id]) charts[id].destroy();
            charts[id] = new Chart(document.getElementById(id), {
                type: 'doughnut',
                data: {
                    labels: data.map(x => x.categoria),
                    datasets: [{ data: data.map(x => x.ventas), backgroundColor: ['#bc0d32','#e61e4d','#ff4d79','#ff80a0','#ffb3c6'], borderWidth: 0 }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: '#888' } } } }
            });
        }

        function renderBarras(id, data) {
            if (charts[id]) charts[id].destroy();
            charts[id] = new Chart(document.getElementById(id), {
                type: 'bar',
                data: {
                    labels: data.map(x => x.region),
                    datasets: [{ label: 'Ganancia', data: data.map(x => x.ganancias), backgroundColor: '#bc0d32' }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { grid: { color: '#222' } }, x: { grid: { display: false } } } }
            });
        }

        function renderLinea(id, data) {
            if (charts[id]) charts[id].destroy();
            charts[id] = new Chart(document.getElementById(id), {
                type: 'line',
                data: {
                    labels: data.map(x => `${x.anio}-${x.mes}`),
                    datasets: [{ label: 'Ventas', data: data.map(x => x.ventas), borderColor: '#bc0d32', tension: 0.4, fill: true, backgroundColor: 'rgba(188,13,50,0.1)' }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { grid: { color: '#222' } } } }
            });
        }

        function renderTabla(data) {
            if (dataTable) dataTable.destroy();
            const body = document.querySelector('#tablaDatos tbody');
            body.innerHTML = data.map(x => `
                <tr>
                    <td>#${x.order_id}</td>
                    <td>${x.fecha}</td>
                    <td>${x.cliente}</td>
                    <td>${x.region || 'Sin Sede'}</td>
                    <td>${x.categoria}</td>
                    <td>${x.producto}</td>
                    <td>$${parseFloat(x.ventas).toFixed(2)}</td>
                    <td style="color:#00c853; font-weight:bold;">$${parseFloat(x.ganancia).toFixed(2)}</td>
                </tr>
            `).join('');
            dataTable = $('#tablaDatos').DataTable({ pageLength: 8, language: { search: 'Buscar:' } });
        }

        document.getElementById('btnFiltrar').addEventListener('click', cargarDatos);
        document.getElementById('btnExportar').addEventListener('click', () => {
             const query = new URLSearchParams({
                anio: document.getElementById('sel-year').value,
                ciudad: document.getElementById('sel-region').value,
                categoria: document.getElementById('sel-category').value,
            }).toString();
            window.location.href = `${BASE_URL}/api/export?${query}`;
        });

        window.onload = cargarDatos;
    </script>
</body>
</html>