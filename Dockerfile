FROM php:8.4-apache-bookworm AS app_base

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
	APP_ENV=dev \
	APP_DEBUG=1

RUN apt-get update \
	&& apt-get install -y --no-install-recommends \
		git \
		libfreetype6-dev \
		libicu-dev \
		libjpeg62-turbo-dev \
		libpng-dev \
		libxml2-dev \
		libzip-dev \
		nodejs \
		npm \
		unzip \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install -j"$(nproc)" \
		gd \
		intl \
		opcache \
		pdo_mysql \
		zip \
	&& a2enmod rewrite \
	&& echo "ServerName localhost" >> /etc/apache2/apache2.conf \
	&& sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
	&& sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
	&& sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
	&& printf '<Directory "${APACHE_DOCUMENT_ROOT}">\n    AllowOverride All\n    Require all granted\n    FallbackResource /index.php\n</Directory>\n' > /etc/apache2/conf-available/symfony.conf \
	&& a2enconf symfony \
	&& echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini \
	&& rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN curl -sS https://get.symfony.com/cli/installer | bash \
	&& mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html

FROM app_base AS build

COPY composer.json composer.lock symfony.lock ./
RUN composer install \
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

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

RUN mkdir -p var/cache var/log \
	&& chown -R www-data:www-data var

EXPOSE 80

# docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint"]
CMD ["apache2-foreground"]
