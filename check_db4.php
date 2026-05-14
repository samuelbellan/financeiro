<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$previsoes = Illuminate\Support\Facades\DB::table('transacao_previsoes')->where('mes', 5)->get();
print_r($previsoes);
