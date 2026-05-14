<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$compras = Illuminate\Support\Facades\DB::table('cartao_compras')->orderBy('id', 'desc')->limit(2)->get();
print_r($compras);
$parcelas = Illuminate\Support\Facades\DB::table('cartao_parcelas')->orderBy('id', 'desc')->limit(2)->get();
print_r($parcelas);
