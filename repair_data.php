<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$products = DB::table('producto')->get();
$details = DB::table('detalleventa')->whereNull('idproducto')->get();

echo "Attempting to match by price...\n";
foreach ($details as $dv) {
    $match = DB::table('producto')->where('precio', $dv->preciounitario)->first();
    if ($match) {
        DB::table('detalleventa')->where('iddetalleventa', $dv->iddetalleventa)->update(['idproducto' => $match->idproducto]);
    }
}

$fixed = DB::table('detalleventa')->whereNotNull('idproducto')->count();
echo "Fixed $fixed rows.\n";
