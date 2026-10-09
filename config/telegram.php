<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Token do Bot (obtido com @BotFather)
    |--------------------------------------------------------------------------
    */
    'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Chat ID autorizado a enviar comandos
    |--------------------------------------------------------------------------
    | Apenas mensagens deste chat serão processadas.
    | Para descobrir seu Chat ID, fale com @userinfobot no Telegram.
    */
    'allowed_chat_id' => env('TELEGRAM_ALLOWED_CHAT_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Token secreto para validar o webhook
    |--------------------------------------------------------------------------
    | Usado no header X-Telegram-Bot-Api-Secret-Token para validar
    | que as requisições realmente vêm do Telegram.
    */
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | ID do usuário dono das transações
    |--------------------------------------------------------------------------
    */
    'user_id' => env('TELEGRAM_USER_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | URL do Projeto Local (Roteamento Local-First)
    |--------------------------------------------------------------------------
    | Quando definido no projeto da Nuvem, todas as requisições do Telegram
    | serão primeiramente enviadas para o projeto local. Se o projeto local
    | estiver offline (PC desligado/túnel fechado), a Nuvem processa como fallback.
    */
    'local_webhook_url' => env('TELEGRAM_LOCAL_WEBHOOK_URL', env('LOCAL_APP_URL', '')),
];

