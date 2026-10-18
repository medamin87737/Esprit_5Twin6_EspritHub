# NutriTrace — image applicative (PHP 8.2 + Apache)
# Stage 1 : assets Vite (public/build est dans .gitignore)
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci || npm install
COPY . .
RUN npm run build

# Stage 2 : runtime PHP
FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql zip mbstring bcmath \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .
COPY --from=frontend /app/public/build /var/www/html/public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && cp -n .env.example .env \
    && php artisan key:generate --force \
    && chown -R www-data:www-data storage bootstrap/cache \
    && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf '\nServerName localhost\n' >> /etc/apache2/apache2.conf

EXPOSE 80

CMD ["apache2-foreground"]
