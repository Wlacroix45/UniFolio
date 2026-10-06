FROM php:8.4-apache-bookworm AS app_base

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
	APP_ENV=prod \
	APP_DEBUG=0

RUN apt-get update \
	&& apt-get install -y --no-install-recommends \
		libfreetype6-dev \
		libicu-dev \
		libjpeg62-turbo-dev \
		libpng-dev \
		libxml2-dev \
		libzip-dev \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install -j"$(nproc)" \
		gd \
		intl \
		opcache \
		pdo_mysql \
		zip \
	&& a2enmod rewrite \
	&& sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
	&& sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
	&& rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html


FROM app_base AS build

RUN apt-get update \
	&& apt-get install -y --no-install-recommends \
		git \
		nodejs \
		npm \
		unzip \
	&& rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock symfony.lock ./
RUN composer install \
	--no-dev \
	--no-interaction \
	--no-progress \
	--no-scripts \
	--optimize-autoloader \
	--prefer-dist

COPY package*.json ./
RUN npm install --force

COPY assets ./assets
COPY public ./public
COPY webpack.config.js ./
RUN npm run build


FROM app_base AS app

COPY . .
COPY --from=build /var/www/html/vendor ./vendor
COPY --from=build /var/www/html/public/build ./public/build

RUN mkdir -p var/cache var/log \
	&& chown -R www-data:www-data var

EXPOSE 80

CMD ["apache2-foreground"]
