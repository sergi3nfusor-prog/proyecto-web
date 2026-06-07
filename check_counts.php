<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = ['producto', 'categoria', 'detalleventa', 'venta'];
foreach ($tables as $table) {
    $count = DB::table($table)->count();
    echo "$table: $count rows\n";
}

$ventas_con_categoria = DB::select("
    SELECT COUNT(*) as cnt
    FROM detalleventa dv
    JOIN categoria cat ON dv.idproducto = cat.idproducto
");
echo "detalleventa with categoria: " . $ventas_con_categoria[0]->cnt . "\n";
