# Stage 1: Compilação dos arquivos estáticos (Vite / Tailwind / JS)
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: Servidor de Produção PHP + Nginx
FROM php:8.3-fpm-alpine

# Instalação de dependências do sistema e pacotes do PostgreSQL/Nginx
RUN apk add --no-cache \
    nginx \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    icu-dev \
    oniguruma-dev

# Instalação de extensões PHP essenciais
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    pdo_mysql \
    bcmath \
    zip \
    intl \
    opcache

# Copia o executável do Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Diretório de trabalho na imagem
WORKDIR /var/www/html

# Copia todo o código da aplicação
COPY . .

# Copia a pasta dist/build gerada pelo Node.js no Stage 1
COPY --from=frontend /app/public/build ./public/build

# Instala as dependências de produção do Laravel via Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copia e configura o Nginx
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Prepara o script de entrada (entrypoint)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
