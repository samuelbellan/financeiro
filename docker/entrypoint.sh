#!/bin/sh
set -e

# Substitui o PORT_HOLDER pela porta dinâmica fornecida pelo Render/Railway ($PORT) ou 80
PORT="${PORT:-80}"
for conf_path in /etc/nginx/http.d/default.conf /etc/nginx/conf.d/default.conf /etc/nginx/sites-available/default; do
    if [ -f "$conf_path" ]; then
        sed -i "s/PORT_HOLDER/${PORT}/g" "$conf_path"
    fi
done

# Garante a criação de pastas necessárias do Laravel e permissões
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache storage/app/public
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Suporte caso utilize SQLite em volume montado
if [ "$DB_CONNECTION" = "sqlite" ] && [ -n "$DB_DATABASE" ] && [ "$DB_DATABASE" != ":memory:" ]; then
    mkdir -p "$(dirname "$DB_DATABASE")"
    touch "$DB_DATABASE"
    chown -R www-data:www-data "$(dirname "$DB_DATABASE")"
    chmod 775 "$DB_DATABASE"
fi

# Executa as migrações no banco de dados se não desativado
if [ "$SKIP_MIGRATIONS" != "true" ]; then
    echo "Executando migrações do banco de dados..."
    php artisan migrate --force || echo "Aviso: Falha ao rodar migrações. Verifique a conexão com o banco de dados."
fi

# Executa o seeder de produção se solicitado explicitamente via variável de ambiente
if [ "$SEED_ON_DEPLOY" = "true" ]; then
    echo "Executando seeder de produção (ProductionDataSeeder)..."
    php artisan db:seed --class=ProductionDataSeeder --force || echo "Aviso: Falha ao executar o seeder de produção."
fi

# Otimizações de cache do Laravel para ambiente de produção
echo "Gerando caches de configuração e rotas..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link --force || true

# Configura automaticamente o webhook do Telegram se solicitado e APP_URL for HTTPS
if [ "$AUTO_SET_TELEGRAM_WEBHOOK" = "true" ] && [ -n "$APP_URL" ] && [ -n "$TELEGRAM_BOT_TOKEN" ]; then
    echo "Configurando Webhook do Telegram para: $APP_URL/webhook/telegram..."
    php artisan telegram:set-webhook "$APP_URL/webhook/telegram" || echo "Aviso: Falha ao registrar webhook do Telegram."
fi

# Inicia o PHP-FPM em segundo plano
php-fpm -D

# Inicia o Nginx em primeiro plano
echo "Servidor iniciado com sucesso na porta $PORT!"
exec nginx -g 'daemon off;'
