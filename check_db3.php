<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$transacoes = Illuminate\Support\Facades\DB::table('transacoes')
    ->whereIn('descricao', ['My Cookie', 'Pix Zamilda', 'VUON'])
    ->orWhere('descricao', 'like', '%VUON%')
    ->orWhere('descricao', 'like', '%Cookie%')
    ->orWhere('descricao', 'like', '%Zamilda%')
    ->get();
print_r($transacoes);
