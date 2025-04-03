FROM php:8.1-fpm

# Instala extensões do PHP necessárias para o Laravel
RUN apt-get update && apt-get install -y \
    libonig-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring zip

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copia arquivos para o contêiner
COPY . /var/www/html

# Instala dependências do projeto (Laravel)
RUN composer install --no-dev --optimize-autoloader

# Ajustes de permissões (caso necessário)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expõe a porta da aplicação (Laravel interno na 8000)
EXPOSE 8000

# Comando para rodar a aplicação
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
