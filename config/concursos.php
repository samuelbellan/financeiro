<?php

return [
    /*
    | Liga o módulo novo de concursos (meta diária, questões, curadoria).
    | true só no PC servidor, onde está o Postgres local; false na nuvem,
    | onde as rotas do módulo respondem 404 e o menu o esconde.
    | (O app estático antigo em /concursos não depende desta flag.)
    */
    'enabled' => (bool) env('CONCURSOS_ENABLED', false),

    'connection' => 'concursos_pgsql',

    'backup' => [
        // Pasta sincronizada com o Google Drive onde o pg_dump diário é salvo.
        'dir' => env('CONCURSOS_BACKUP_DIR'),
        'diarios' => (int) env('CONCURSOS_BACKUP_DIARIOS', 14),
        'semanais' => (int) env('CONCURSOS_BACKUP_SEMANAIS', 8),
        // Pasta do pg_dump.exe (vazio = usa o PATH).
        'pg_bin' => env('CONCURSOS_PG_BIN', 'C:\\Program Files\\PostgreSQL\\18\\bin'),
    ],
];
