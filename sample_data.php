<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Categoria table sampling:\n";
$rows = DB::table('categoria')->limit(5)->get();
print_r($rows);

echo "\nProducto table sampling:\n";
$prows = DB::table('producto')->limit(5)->get();
print_r($prows);
