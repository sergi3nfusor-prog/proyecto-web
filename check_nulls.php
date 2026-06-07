<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$null_count = DB::table('detalleventa')->whereNull('idproducto')->count();
$not_null_count = DB::table('detalleventa')->whereNotNull('idproducto')->count();
echo "DetalleVenta idproducto NULL count: $null_count\n";
echo "DetalleVenta idproducto NOT NULL count: $not_null_count\n";
