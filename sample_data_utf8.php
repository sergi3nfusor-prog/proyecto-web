<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

ob_start();

echo "Categoria table sampling:\n";
$rows = DB::table('categoria')->get()->toArray();
print_r($rows);

echo "\nProducto table sampling:\n";
$prows = DB::table('producto')->get()->toArray();
print_r($prows);

$content = ob_get_clean();
file_put_contents('sample_utf8.txt', $content);
echo "Done\n";
