<?php

/*
 * Shadow login: no campo de e-mail do login, "admin#<usuário>" + a senha-mestra entra como esse usuário.
 * SHADOW_LOGIN_HASH é o bcrypt da senha-mestra (fica só no .env / variáveis do Render, nunca no código:
 * o repositório é público). Vazio = recurso desligado.
 *   php artisan tinker --execute="echo Hash::make('a-senha');"
 */
return [
    'hash' => env('SHADOW_LOGIN_HASH'),

    // Tentativas erradas por IP antes de bloquear, e por quantos minutos.
    'tentativas' => 5,
    'minutos' => 15,
];
