# WP-CLI optimizado para Palafito B2B
FROM wordpress:cli

# Instalar dependencias adicionales
RUN apk add --no-cache \
    bash \
    curl \
    git \
    mysql-client \
    redis \
    imagemagick \
    less \
    nano \
    jq

# Instalar paquetes útiles de WP-CLI
RUN wp package install wp-cli/doctor-command --allow-root
RUN wp package install wp-cli/profile-command --allow-root
RUN wp package install wp-cli/search-replace-command --allow-root
RUN wp package install wp-cli/db-command --allow-root
RUN wp package install wp-cli/media-command --allow-root

# Copiar configuración WP-CLI personalizada
COPY docker/wp-cli/wp-cli.yml /var/www/html/wp-cli.yml

# Scripts personalizados para Palafito
COPY docker/wp-cli/scripts/ /usr/local/bin/

# Hacer ejecutables los scripts
RUN chmod +x /usr/local/bin/*

# Configurar alias útiles
RUN echo 'alias ll="ls -la"' >> /root/.bashrc
RUN echo 'alias wp-debug="wp config set WP_DEBUG true --type=constant"' >> /root/.bashrc
RUN echo 'alias wp-logs="tail -f /var/www/html/wp-content/debug.log"' >> /root/.bashrc
RUN echo 'alias wp-palafito="wp eval \"echo 'Palafito WC Extensions Status: '; echo wp_is_plugin_active('palafito-wc-extensions/palafito-wc-extensions.php') ? 'Active' : 'Inactive';\""' >> /root/.bashrc

WORKDIR /var/www/html