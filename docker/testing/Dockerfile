# Container para testing automatizado de Palafito B2B
FROM wordpress:cli

# Instalar dependencias para testing
RUN apk add --no-cache \
    bash \
    curl \
    git \
    mysql-client \
    subversion \
    wget \
    unzip \
    less \
    jq

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar PHPUnit
RUN wget https://phar.phpunit.de/phpunit-9.phar \
    && chmod +x phpunit-9.phar \
    && mv phpunit-9.phar /usr/local/bin/phpunit

# Configurar WordPress test suite
RUN mkdir -p /tmp/wordpress-tests-lib \
    && cd /tmp \
    && wget -O wordpress-tests.tar.gz https://develop.svn.wordpress.org/tags/6.4/tests/phpunit/includes/ \
    || echo "WordPress tests will be downloaded during setup"

# Crear directorio para tests
RUN mkdir -p /var/www/html/tests

# Script de configuración de tests
COPY docker/testing/setup-tests.sh /usr/local/bin/setup-tests
RUN chmod +x /usr/local/bin/setup-tests

# Script de ejecución de tests
COPY docker/testing/run-tests.sh /usr/local/bin/run-tests
RUN chmod +x /usr/local/bin/run-tests

# Configuración PHPUnit
COPY docker/testing/phpunit.xml /var/www/html/phpunit.xml

WORKDIR /var/www/html

# Comando por defecto
CMD ["run-tests"]