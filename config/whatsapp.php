<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Token secreto para validar o webhook
    |--------------------------------------------------------------------------
    | Defina WHATSAPP_WEBHOOK_TOKEN no .env e coloque o mesmo valor
    | nas configurações de webhook da Evolution API.
    */
    'webhook_token' => env('WHATSAPP_WEBHOOK_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Número de celular autorizado a enviar comandos
    |--------------------------------------------------------------------------
    | Apenas mensagens deste número serão processadas.
    | Formato: DDI + DDD + número, sem espaços ou símbolos.
    | Exemplo: 5511999999999
    */
    'allowed_number' => env('WHATSAPP_ALLOWED_NUMBER', ''),

    /*
    |--------------------------------------------------------------------------
    | Evolution API — configurações de conexão
    |--------------------------------------------------------------------------
    */
    'evolution_url'      => env('EVOLUTION_API_URL', 'http://localhost:8080'),
    'evolution_key'      => env('EVOLUTION_API_KEY', ''),
    'evolution_instance' => env('EVOLUTION_INSTANCE', 'financeiro'),
];
