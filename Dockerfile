FROM php:7-fpm

COPY . .

RUN docker-php-ext-install pdo_mysql

RUN apt-get update && apt-get install -y iputils-ping && apt-get clean all
