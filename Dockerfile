# Usar una imagen oficial de PHP 8.1 con PHP-FPM y Alpine (ligera)
FROM php:8.1-fpm-alpine

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Instalar dependencias del sistema y extensiones de PHP necesarias
RUN apk add --no-cache git unzip \
    && docker-php-ext-install pdo pdo_mysql opcache

# Instalar Composer (gestor de dependencias de PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar los archivos de la aplicación al contenedor
COPY . .

# Añadir el directorio actual como seguro para Git (SOLUCIÓN SECUNDARIA)
RUN git config --global --add safe.directory /var/www/html

# Instalar las dependencias de la aplicación con Composer
# Usamos --ignore-platform-reqs para solucionar los problemas de versiones antiguas
RUN composer install --no-interaction --optimize-autoloader --no-dev --ignore-platform-reqs

# Crear las carpetas necesarias ANTES de cambiar sus permisos
RUN mkdir -p /var/www/html/storage \
    && mkdir -p /var/www/html/bootstrap/cache

# Cambiar el propietario de los archivos al usuario del servidor web
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Exponer el puerto en el que PHP-FPM escucha
EXPOSE 9000

# Habilitar mod_rewrite y configurar AllowOverride All
RUN apk add --no-cache apache2 apache2-utils && \
    sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/httpd.conf && \
    sed -i 's/#LoadModule rewrite_module/LoadModule rewrite_module/' /etc/apache2/httpd.conf

# Mover php.ini-production para evitar Deprecation Warnings
COPY php.ini-production /usr/local/etc/php/php.ini

# Ajustar DocumentRoot a /public
RUN sed -i 's|DocumentRoot "/var/www/html"|DocumentRoot "/var/www/html/public"|' /etc/apache2/httpd.conf

# Comando para iniciar el servicio de PHP-FPM
CMD ["php-fpm"]