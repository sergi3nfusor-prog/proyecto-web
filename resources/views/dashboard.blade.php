<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quantum BI — Sport Luxury</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    
    <style>
        /* ── LUXURY DARK SYSTEM ── */
        :root {
            --bg-black: #050505;
            --bg-card: #0d0d0d;
            --bg-accent: #151515;
            --primary-crimson: #9e0d2a;
            --primary-scarlet: #ff2d20;
            --primary-guindo: #660015;
            --gray-deep: #1a1a1a;
            --gray-text: #999999;
            --white-glass: rgba(255, 255, 255, 0.9);
            --border-glow: rgba(158, 13, 42, 0.3);
            --shadow-lux: 0 10px 40px rgba(0, 0, 0, 0.8);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background-color: var(--bg-black);
            color: var(--white-glass);
            font-family: 'Outfit', sans-serif;
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* ── CUSTOM SCROLLBAR ── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-black); }
        ::-webkit-scrollbar-thumb { background: var(--primary-guindo); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary-crimson); }

        /* ── PREMIUM NAVIGATION ── */
        nav {
            height: 70px;
            background: rgba(5, 5, 5, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--gray-deep);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
        }

        .brand {
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -1px;
            display: flex; align-items: center; gap: 10px;
        }
        .brand span { color: var(--primary-scarlet); }

        .nav-filters { display: flex; gap: 15px; align-items: center; }
        
        .nav-filters select {
            background: var(--bg-card);
            border: 1px solid var(--gray-deep);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            outline: none;
            transition: 0.3s;
        }
        .nav-filters select:focus { border-color: var(--primary-crimson); box-shadow: 0 0 10px var(--border-glow); }

        .btn-apply {
            background: linear-gradient(135deg, var(--primary-guindo), var(--primary-crimson));
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-apply:hover { transform: translateY(-2px); box-shadow: 0 5px 15px var(--border-glow); }

        /* ── USER MENU ── */
        .user-menu {
            position: relative;
            display: flex; align-items: center; gap: 12px;
            cursor: pointer;
            padding: 5px 15px;
            border-radius: 12px;
            transition: 0.3s;
            border: 1px solid transparent;
        }
        .user-menu:hover { background: var(--bg-accent); border-color: var(--gray-deep); }
        
        .avatar {
            width: 35px; height: 35px;
            background: linear-gradient(45deg, var(--primary-guindo), var(--primary-scarlet));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; color: white;
        }
        
        .dropdown {
            position: absolute;
            top: 110%; right: 0;
            background: var(--bg-card);
            border: 1px solid var(--gray-deep);
            border-radius: 12px;
            width: 200px;
            display: none;
            flex-direction: column;
            overflow: hidden;
            box-shadow: var(--shadow-lux);
        }
        .dropdown a, .dropdown button {
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.2s;
            background: none; border: none; text-align: left; cursor: pointer;
            font-family: inherit;
        }
        .dropdown a:hover, .dropdown button:hover { background: var(--primary-guindo); }
        .user-menu:hover .dropdown { display: flex; }

        /* ── MAIN CONTENT ── */
        main {
            padding: 100px 40px 40px;
            max-width: 1600px;
            margin: 0 auto;
        }

        .header-section { margin-bottom: 40px; }
        .header-section h2 { font-size: 2.5rem; font-weight: 800; margin-bottom: 5px; }
        .header-section p { color: var(--gray-text); letter-spacing: 1px; }

        /* ── KPIs ── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .kpi-card {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 20px;
            border: 1px solid var(--gray-deep);
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        .kpi-card:hover { transform: translateY(-10px); border-color: var(--primary-crimson); }
        .kpi-card::after {
            content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(90deg, transparent, var(--primary-crimson), transparent);
        }
        .kpi-label { color: var(--gray-text); font-size: 0.8rem; text-transform: uppercase; font-weight: 600; }
        .kpi-value { font-size: 2.2rem; font-weight: 800; margin-top: 10px; color: white; }

        /* ── DASHBOARD GRID ── */
        .chart-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 40px;
        }
        .chart-card {
            background: var(--bg-card);
            padding: 25px;
            border-radius: 24px;
            border: 1px solid var(--gray-deep);
        }
        .chart-card h3 { font-size: 1.1rem; margin-bottom: 20px; color: var(--gray-text); display: flex; align-items: center; gap: 10px; }
        .chart-card h3::before { content: ''; width: 4px; height: 18px; background: var(--primary-scarlet); border-radius: 2px; }

        /* ── TABLE LUXURY ── */
        .table-section {
            background: var(--bg-card);
            border-radius: 24px;
            border: 1px solid var(--gray-deep);
            padding: 30px;
            overflow: hidden;
        }
        .table-container { overflow-x: auto; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: var(--gray-text); text-transform: uppercase; font-size: 0.75rem; font-weight: 700; border-bottom: 1px solid var(--gray-deep); }
        td { padding: 18px 15px; border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 0.9rem; }
        tr:hover td { background: rgba(158, 13, 42, 0.05); }
        
        .badge {
            padding: 4px 12px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;
        }
        .badge-success { background: rgba(0, 255, 100, 0.1); color: #00ff64; border: 1px solid rgba(0, 255, 100, 0.2); }

        /* ── LOADING ── */
        #loading {
            position: fixed; inset: 0; background: var(--bg-black); z-index: 9999;
            display: flex; align-items: center; justify-content: center;
            transition: 0.5s;
        }
        #loading.hidden { opacity: 0; pointer-events: none; }
        .loader { width: 48px; height: 48px; border: 5px solid #FFF; border-bottom-color: var(--primary-scarlet); border-radius: 50%; animation: rotation 1s linear infinite; }
        @keyframes rotation { 0% { transform: rotate(0deg) } 100% { transform: rotate(360deg) } }

        @media (max-width: 1024px) { .chart-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <div id="loading"><span class="loader"></span></div>

    <nav>
        <div class="brand">QUANTUM<span>SPORTS</span></div>
        <div class="nav-filters">
            <select id="sel-anio">
                <option value="">AÑO</option>
                @foreach($anios as $a) <option value="{{ $a->anio }}">{{ $a->anio }}</option> @endforeach
            </select>
            <select id="sel-ciudad">
                <option value="">CIUDAD</option>
                @foreach($ciudades as $c) <option value="{{ $c->ciudad }}">{{ $c->ciudad }}</option> @endforeach
            </select>
            <select id="sel-categoria">
                <option value="">CATEGORÍA</option>
                @foreach($categorias as $cat) 
                    {{-- ✅ CORRECCIÓN: nombre_categoria con alias devuelto por Postgres --}}
                    <option value="{{ $cat->nombre_categoria }}">{{ $cat->nombre_categoria }}</option> 
                @endforeach
            </select>
            <button class="btn-apply" onclick="cargarDatos()">FILTRAR</button>
        </div>
        <div class="user-menu">
            <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div class="user-info">
                <div style="font-size: 0.85rem; font-weight: 600;">{{ auth()->user()->name }}</div>
                <div style="font-size: 0.7rem; color: var(--gray-text);">Premium Access</div>
            </div>
            <div class="dropdown">
                <a href="{{ route('profile.edit') }}">Mi Cuenta</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Cerrar Sesión</button>
                </form>
            </div>
        </div>
    </nav>

    <main>
        <div class="header-section">
            <p>SISTEMA DE INTELIGENCIA DE NEGOCIO</p>
            <h2>Dashboard Operativo</h2>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Ingresos Totales</div>
                <div class="kpi-value" id="kpi-ingresos">0.00</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Transacciones</div>
                <div class="kpi-value" id="kpi-pedidos">0</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Ahorro Aplicado</div>
                <div class="kpi-value" id="kpi-descuentos">0.00</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Ticket Promedio</div>
                <div class="kpi-value" id="kpi-ticket">0.00</div>
            </div>
        </div>

        <div class="chart-grid">
            <div class="chart-card">
                <h3>Evolución de Ingresos</h3>
                <div style="height: 350px;"><canvas id="chartEvolucion"></canvas></div>
            </div>
            <div class="chart-card">
                <h3>Ventas por Categoría</h3>
                <div style="height: 350px;"><canvas id="chartDona"></canvas></div>
            </div>
        </div>

        <div class="table-section">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Transacciones Recientes</h3>
                <button class="btn-apply" style="padding: 5px 15px; font-size: 0.7rem;" id="btnExportar">EXPORTAR CSV</button>
            </div>
            <div class="table-container">
                <table id="tablaDatos">
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Sucursal</th>
                            <th>Monto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tablaBody">
                        <!-- JS Content -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Libs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/countup.js@2.8.0/dist/countUp.umd.js"></script>

    <script>
        // Color Palette para Gráficos
        const LUX_RED = '#ff2d20';
        const LUX_GUINDO = '#660015';
        const LUX_CRIMSON = '#9e0d2a';
        
        let charts = {};

        async function cargarDatos() {
            document.getElementById('loading').classList.remove('hidden');
            
            const params = new URLSearchParams({
                anio: document.getElementById('sel-anio').value,
                ciudad: document.getElementById('sel-ciudad').value,
                categoria: document.getElementById('sel-categoria').value
            }).toString();

            try {
                const [rK, rG, rD] = await Promise.all([
                    fetch(`/api/dashboard/kpis?${params}`),
                    fetch(`/api/dashboard/graficos?${params}`),
                    fetch(`/api/dashboard/detalle?${params}`)
                ]);

                const kpis = await rK.json();
                const graficos = await rG.json();
                const detalle = await rD.json();

                renderKpis(kpis);
                renderGraficos(graficos);
                renderTabla(detalle);

            } catch (e) { console.error(e); }
            finally { document.getElementById('loading').classList.add('hidden'); }
        }

        function renderKpis(k) {
            animValue('kpi-ingresos', k.ingresos_brutos, 2, 'Bs. ');
            animValue('kpi-pedidos', k.total_pedidos, 0);
            animValue('kpi-descuentos', k.total_descuentos, 2, 'Bs. ');
            animValue('kpi-ticket', k.ticket_promedio, 2, 'Bs. ');
        }

        function animValue(id, val, dec, prefix = '') {
            const countUp = new window.countUp.CountUp(id, val, { 
                decimalPlaces: dec, 
                prefix: prefix,
                duration: 2,
                separator: ','
            });
            countUp.start();
        }

        function renderGraficos(g) {
            // Line Chart
            destroyChart('evolucion');
            const ctxL = document.getElementById('chartEvolucion').getContext('2d');
            const gradL = ctxL.createLinearGradient(0, 0, 0, 300);
            gradL.addColorStop(0, 'rgba(255, 45, 32, 0.4)');
            gradL.addColorStop(1, 'transparent');

            charts.evolucion = new Chart(ctxL, {
                type: 'line',
                data: {
                    labels: g.ventasMensuales.map(d => `${d.mes}/${d.anio}`),
                    datasets: [{
                        label: 'Ventas',
                        data: g.ventasMensuales.map(d => d.ventas),
                        borderColor: LUX_RED,
                        backgroundColor: gradL,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 0,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { grid: { color: '#1a1a1a' }, ticks: { color: '#999' } },
                        x: { grid: { display: false }, ticks: { color: '#999' } }
                    }
                }
            });

            // Donut Chart
            destroyChart('dona');
            charts.dona = new Chart(document.getElementById('chartDona'), {
                type: 'doughnut',
                data: {
                    labels: g.ventasCategoria.map(d => d.categoria),
                    datasets: [{
                        data: g.ventasCategoria.map(d => d.ventas),
                        backgroundColor: [LUX_RED, LUX_CRIMSON, LUX_GUINDO, '#333', '#111'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '80%',
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#999', padding: 20, boxWidth: 10 } }
                    }
                }
            });
        }

        function destroyChart(id) { if(charts[id]) charts[id].destroy(); }

        function renderTabla(data) {
            const tbody = document.getElementById('tablaBody');
            tbody.innerHTML = data.map(r => `
                <tr>
                    <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.7rem; color: var(--primary-scarlet);">#${r.id_venta}</td>
                    <td>${r.fecha}</td>
                    <td style="font-weight: 600;">${r.cliente}</td>
                    <td style="color: var(--gray-text);">${r.sucursal}</td>
                    <td style="font-weight: 800;">${parseFloat(r.monto_total).toLocaleString()} Bs.</td>
                    <td><span class="badge badge-success">${r.estado_pago}</span></td>
                </tr>
            `).join('');
        }

        window.onload = () => {
            cargarDatos();
            setTimeout(() => document.getElementById('loading').classList.add('hidden'), 500);
        };
    </script>
</body>
</html>