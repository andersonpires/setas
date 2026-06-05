FROM php:8.2-apache

# Habilitar mod_rewrite do Apache e definir ServerName para evitar warnings
RUN a2enmod rewrite && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Instalar dependências de sistema e extensões do PHP necessárias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configurar o diretório raiz do Apache (se necessário, pode ser mantido o padrão /var/www/html)
# Copiar todos os arquivos do projeto para o container
COPY . /var/www/html/

# Configurar as permissões das pastas de upload e temporárias
# Certificar de que as pastas existam para que possam receber as permissões
RUN mkdir -p /var/www/html/assets/img/foto_colaborador \
    && mkdir -p /var/www/html/temp \
    && chown -R www-data:www-data /var/www/html/assets/img/foto_colaborador \
    && chown -R www-data:www-data /var/www/html/temp \
    && chmod -R 775 /var/www/html/assets/img/foto_colaborador \
    && chmod -R 775 /var/www/html/temp

# Expor porta 80
EXPOSE 80
