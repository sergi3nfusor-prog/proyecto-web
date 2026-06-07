<?php
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ── WELCOME ───────────────────────────────────────────────────────────────────
Route::get('/', fn () => view('welcome'));

// ── DASHBOARD ─────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// APIs Temporales (Fuera del middleware para diagnóstico)
Route::get('/api/kpis',     [DashboardController::class, 'kpis']);
Route::get('/api/graficos', [DashboardController::class, 'graficos']);
Route::get('/api/detalle',  [DashboardController::class, 'detalle']);
Route::get('/api/export',   [DashboardController::class, 'exportCsv'])->name('dashboard.export');

// ── PERFIL ────────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';