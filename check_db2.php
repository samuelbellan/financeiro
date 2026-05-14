<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$transacoes = Illuminate\Support\Facades\DB::table('transacoes')->whereMonth('data', 5)->get();
print_r($transacoes);
