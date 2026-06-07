<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

ob_start();
echo "DetalleVenta sampling:\n";
$rows = DB::table('detalleventa')->limit(10)->get()->toArray();
print_r($rows);
$content = ob_get_clean();
file_put_contents('sample_dv.txt', $content);
echo "Done\n";
