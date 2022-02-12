FROM php:8.0-fpm

# Set working directory
WORKDIR /app

# Add docker php ext repo
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install php extensions
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions mbstring pdo_mysql zip exif pcntl gd memcached

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    unzip \
    git \
    curl \
    lua-zlib-dev \
    libmemcached-dev \
    nginx

# Install supervisor
# RUN apt-get install -y supervisor

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Clear cache
# RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Add user for laravel application
# RUN groupadd -g 1000 www
# RUN useradd -u 1000 -ms /bin/bash -g www www

# Copy code to /var/www
# COPY --chown=www:www-data . /var/www

# add root to www group
# RUN chmod -R ug+w /var/www/storage

# Copy nginx/php/supervisor configs
# RUN cp docker/supervisor.conf /etc/supervisord.conf
# RUN cp docker/php.ini /usr/local/etc/php/conf.d/app.ini
# RUN cp docker/nginx.conf /etc/nginx/sites-enabled/default

# PHP Error Log Files
# RUN mkdir /var/log/php
# RUN touch /var/log/php/errors.log && chmod 777 /var/log/php/errors.log

COPY . /app

# Consume Evn Args
ARG APP_NAME
ENV APP_NAME=${APP_NAME}

ARG APP_ENV
ENV APP_ENV=${APP_ENV}

ARG APP_KEY
ENV APP_KEY=${APP_KEY}

ARG APP_URL
ENV APP_URL=${APP_URL}

ARG DB_CONNECTION
ENV DB_CONNECTION=${DB_CONNECTION}

ARG DB_HOST
ENV DB_HOST=${DB_HOST}

ARG DB_PORT
ENV DB_PORT=${DB_PORT}

ARG DB_DATABASE
ENV DB_DATABASE=${DB_DATABASE}

ARG DB_USERNAME
ENV DB_USERNAME=${DB_USERNAME}

ARG DB_PASSWORD
ENV DB_PASSWORD=${DB_PASSWORD}

ARG MAIL_MAILER
ENV MAIL_MAILER=${MAIL_MAILER}

ARG MAIL_HOST
ENV MAIL_HOST=${MAIL_HOST}

ARG MAIL_PORT
ENV MAIL_PORT=${MAIL_PORT}

ARG MAIL_USERNAME
ENV MAIL_USERNAME=${MAIL_USERNAME}

ARG MAIL_PASSWORD
ENV MAIL_PASSWORD=${MAIL_PASSWORD}

ARG MAIL_ENCRYPTION
ENV MAIL_ENCRYPTION=${MAIL_ENCRYPTION}

ARG MAIL_FROM_ADDRESS
ENV MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS}

ARG MAIL_FROM_NAME
ENV MAIL_FROM_NAME=${MAIL_FROM_NAME}

ARG MOLLIE_KEY
ENV MOLLIE_KEY=${MOLLIE_KEY}

# Deployment steps
RUN composer update --ignore-platform-reqs
RUN composer install --optimize-autoloader --no-dev --ignore-platform-reqs
# RUN chmod +x /var/www/docker/run.sh



EXPOSE 80
# ENTRYPOINT ["/var/www/docker/run.sh"]
CMD php artisan serve --host=0.0.0.0 --port=80
