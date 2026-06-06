# Obraz PHP-FPM dla stacka LEMP.
# Bazujemy na oficjalnym, otagowanym obrazie z DockerHub (php:8.3-fpm)
# i doinstalowujemy rozszerzenie mysqli, potrzebne do komunikacji z MySQL.
FROM php:8.3-fpm

RUN docker-php-ext-install mysqli pdo_mysql
