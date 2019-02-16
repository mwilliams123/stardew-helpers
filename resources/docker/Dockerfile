###############################################################################
FROM nginx:alpine AS nginx

WORKDIR /code/public

ADD resources/docker/nginx/laravel-vhost.conf /etc/nginx/conf.d/default.conf

###############################################################################
FROM php:7.2-fpm-alpine AS with-extensions

RUN apk --no-cache add libpng-dev
RUN docker-php-ext-install json gd

###############################################################################
FROM with-extensions AS with-configuration

COPY resources/docker/php/php.ini /usr/local/etc/php/conf.d/laravel-php.ini
COPY resources/docker/php/fpm.conf /usr/local/etc/php-fpm.d/laravel-fpm.conf

###############################################################################
FROM with-configuration AS with-start-script

COPY resources/docker/php/start.sh /usr/local/bin/start

###############################################################################
FROM with-configuration AS with-composer

RUN apk --no-cache add zip git

COPY --from=composer:1.8 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_HOME=/.composer
ENV PATH=${COMPOSER_HOME}/vendor/bin:${PATH}

RUN composer global require --no-progress --no-suggest -n \
        hirak/prestissimo \
        squizlabs/php_codesniffer \
    && chmod -R 777 $COMPOSER_HOME \
    && composer clear-cache

###############################################################################
FROM with-composer AS with-xdebug

RUN apk --no-cache add $PHPIZE_DEPS \
    && pecl install xdebug-2.6.1 \
    && docker-php-ext-enable xdebug

###############################################################################
FROM with-composer AS with-vendor

WORKDIR /code

COPY composer.json composer.lock /code/

RUN composer install --no-dev --no-scripts --no-autoloader --no-progress --no-suggest \
    && composer clear-cache

###############################################################################
FROM node:9 AS npm-production

WORKDIR /code

COPY package.json package-lock.json webpack.mix.js /code/
COPY resources/assets /code/resources/assets

RUN npm install \
    && npm run production \
    && npm cache clean --force \
    && rm -rf /code/node_modules

###############################################################################
FROM with-composer AS dump-autoload

WORKDIR /code

COPY --from=with-vendor /code/vendor /code/vendor
COPY ./ /code
COPY --from=npm-production /code/public/mix-manifest.json /code/public

# Remove the files needed to build the docker image
RUN rm -rf /code/resources/docker

# This can't be done until all the files are present...
RUN composer dump-autoload --optimize

###############################################################################
FROM with-start-script AS php-production

WORKDIR /code

COPY --from=dump-autoload /code /code

CMD ["/usr/local/bin/start"]

###############################################################################
FROM nginx AS web-production

COPY --from=npm-production /code/public /code/public
COPY public/robots.txt public/favicon.ico /code/public/