<?php

/*
 * Backup do app (php artisan backup:drive), de hora em hora:
 *  - o banco SQLite (principal no PC) ganha uma cópia no Google Drive;
 *  - os arquivos (principal no Google Drive, ver config/dados.php) ganham um espelho no PC.
 */
return [
    // Pasta do Google Drive que recebe as cópias do banco.
    'dir' => env('BACKUP_DRIVE_DIR'),

    // Pasta do PC que recebe o espelho dos arquivos do Drive (vazio = storage/espelho-drive).
    'local' => env('BACKUP_LOCAL_DIR') ?: storage_path('espelho-drive'),

    // Retenção das cópias do banco: as N mais recentes, depois 1 por dia por D dias e 1 por semana por S semanas.
    'recentes' => (int) env('BACKUP_DRIVE_RECENTES', 24),
    'diarios' => (int) env('BACKUP_DRIVE_DIARIOS', 14),
    'semanais' => (int) env('BACKUP_DRIVE_SEMANAIS', 8),

    // backup:conferir (a cada 3 h): cópia mais nova com mais que isto conta como atraso.
    'atraso_horas' => (float) env('BACKUP_ATRASO_HORAS', 3),
];
