<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quantum Analytics | Dashboard</title>
    
    <!-- Fuentes y Librerías Externas -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

    @vite(['resources/css/app.css', 'resources/js/dashboard.js'])

    <!-- Librerías JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Cargamos CountUp como UMD -->
    <script src="https://cdn.jsdelivr.net/npm/countup.js@2.8.0/dist/countUp.umd.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <style>
        /* Ajustes inmediatos para el Dashboard */
        .loading-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(6, 6, 8, 0.9); z-index: 9999;
            display: none; flex-direction: column; align-items: center; justify-content: center;
            backdrop-filter: blur(10px);
        }
        .loading-overlay.active { display: flex; }
        .loader {
            width: 48px; height: 48px; border: 5px solid var(--primary-orange);
            border-bottom-color: transparent; border-radius: 50%;
            animation: rotation 1s linear infinite;
        }
        @keyframes rotation { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* Contenedores de Gráficos */
        .charts-grid-4 {
            display: grid; grid-template-columns: repeat(12, 1fr); gap: 20px; margin-top: 24px;
        }
        .chart-container {
            grid-column: span 6; background: var(--bg-card); border: 1px solid var(--border-dim);
            border-radius: var(--radius-md); padding: 20px; position: relative;
        }
        .chart-container.wide { grid-column: span 12; }
        .chart-header h4 { color: var(--primary-orange); margin-bottom: 15px; font-weight: 600; }
        .chart-body { height: 300px; position: relative; }

        /* DataTables Dark Theme override */
        .table-card { background: var(--bg-card); border-radius: var(--radius-md); padding: 20px; margin-top: 24px; }
        .dataTables_wrapper { color: #ccc; }
        table.dataTable { border-collapse: collapse !important; }
        table.dataTable thead th { color: var(--primary-orange) !important; border-bottom: 1px solid var(--border-dim) !important; }
        table.dataTable tbody td { border-bottom: 1px solid var(--border-dim) !important; padding: 12px 8px !important; }
        .dataTables_filter input { background: #1a1a1a; border: 1px solid #333; color: white; border-radius: 6px; }
        .dataTables_paginate .paginate_button { color: white !important; }
    </style>
</head>
<body>

    <div id="loadingOverlay" class="loading-overlay">
        <div class="loader"></div>
        <p style="margin-top: 20px; color: var(--primary-orange); font-weight: 600;">Procesando Inteligencia de Negocio...</p>
    </div>

    <!-- SIDEBAR -->
    <aside id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <div class="logo-box">
                <span class="logo-icon" style="background: var(--primary-orange); color: black;">Q</span>
                <span class="logo-text">QUANTUM</span>
            </div>
            <button id="sidebarToggle" class="sidebar-toggle">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-group">
                <p class="nav-label">General</p>
                <a href="#" class="nav-link active">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path d="M9 22V12h6v10"/></svg>
                    <span>Power BI Dashboard</span>
                </a>
            </div>

            <div class="nav-group filters-group">
                <p class="nav-label">Segmentación</p>
                
                <div class="filter-item">
                    <label>Año</label>
                    <select id="sel-year">
                        <option value="">Historial Completo</option>
                        @foreach($anios as $a)
                            <option value="{{ $a->anio }}">{{ $a->anio }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-item">
                    <label>Ciudad</label>
                    <select id="sel-region">
                        <option value="">Todas las Sedes</option>
                        @foreach($ciudades as $c)
                            <option value="{{ $c->ciudad }}">{{ $c->ciudad }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-item">
                    <label>Línea de Producto</label>
                    <select id="sel-category">
                        <option value="">Todas las Familias</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->nombre_categoria }}">{{ $cat->nombre_categoria }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-actions">
                    <button id="btnFiltrar" class="btn-primary">Ejecutar Análisis</button>
                    <button id="btnLimpiar" class="btn-outline">Resetear</button>
                </div>
            </div>
        </nav>

        <div class="sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 01-2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                    <span>Desconectarse</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- CONTENT -->
    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <h1 style="font-weight: 800; letter-spacing: -0.5px;">Analytics Dashboard</h1>
                <p class="text-muted">Quantum Sports Intelligent Monitoring</p>
            </div>
            <div class="header-right">
                <button id="btnExportar" class="btn-secondary" style="border-color: var(--primary-orange); color: var(--primary-orange);">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    Descargar CSV
                </button>
                <div class="user-profile">
                    <div class="avatar" style="background: var(--primary-orange); color: black; font-weight: bold;">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                </div>
            </div>
        </header>

        <!-- KPI GRID -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-info">
                    <p class="kpi-label">Ventas Totales</p>
                    <h3 id="kpi-total-ventas">$0.00</h3>
                </div>
                <div class="kpi-icon orange">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-info">
                    <p class="kpi-label">Ganancias (30%)</p>
                    <h3 id="kpi-total-ganancias">$0.00</h3>
                </div>
                <div class="kpi-icon green">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 6l-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/></svg>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-info">
                    <p class="kpi-label">Total Pedidos</p>
                    <h3 id="kpi-total-pedidos">0</h3>
                </div>
                <div class="kpi-icon blue">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4H6z"/><path d="M3 6h18M16 10a4 4 0 01-8 0"/></svg>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-info">
                    <p class="kpi-label">Margen Operativo</p>
                    <h3 id="kpi-margen">0.00%</h3>
                </div>
                <div class="kpi-icon purple">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12A10 10 0 0012 2v10z"/><path d="M21.21 15.89A10 10 0 118 2.83"/></svg>
                </div>
            </div>
        </div>

        <!-- CHARTS GRID -->
        <div class="charts-grid-4">
            <div class="chart-container">
                <div class="chart-header">
                    <h4>Ventas por Categoría</h4>
                </div>
                <div class="chart-body">
                    <canvas id="chartVentasCategoria"></canvas>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-header">
                    <h4>Distribución por Región</h4>
                </div>
                <div class="chart-body">
                    <canvas id="chartGananciasRegion"></canvas>
                </div>
            </div>

            <div class="chart-container wide">
                <div class="chart-header">
                    <h4>Evolución Histórica de Ingresos</h4>
                </div>
                <div class="chart-body">
                    <canvas id="chartEvolucionMensual"></canvas>
                </div>
            </div>

            <div class="chart-container wide">
                <div class="chart-header">
                    <h4>Top 5 Productos Estrella</h4>
                </div>
                <div class="chart-body">
                    <canvas id="chartTopProductos"></canvas>
                </div>
            </div>
        </div>

        <!-- TABLA DE DATOS -->
        <div class="table-card">
            <div class="table-header" style="margin-bottom: 20px;">
                <h3 style="color: var(--text-primary);">Detalle de Transacciones (Audit Log)</h3>
            </div>
            <div class="table-responsive">
                <table id="tablaDatos" class="display nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Región</th>
                            <th>Categoría</th>
                            <th>Producto</th>
                            <th>Venta ($)</th>
                            <th>Ganancia ($)</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>