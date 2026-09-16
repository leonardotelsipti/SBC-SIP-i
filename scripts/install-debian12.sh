#!/usr/bin/env bash
# ==============================================================================
# SBC Telsipti - SIP-i & Módulo DETRAF STFC
# Script de Instalação Automatizada no Debian 12 (Bookworm)
# Stack: PHP 8.5 + Nginx + MariaDB + Composer + Laravel 13 + Laravel Boost
# ==============================================================================

set -e

echo "=== [1/7] Atualizando sistema base Debian 12 ==="
apt-get update && apt-get upgrade -y
apt-get install -y lsb-release ca-certificates apt-transport-https software-properties-common \
    curl wget git unzip zip tar ufw net-tools iproute2 htop supervisor nginx mariadb-server

echo "=== [2/7] Adicionando Repositório Ondřej Surý para PHP 8.5 ==="
curl -sSLo /tmp/debsury.gpg https://packages.sury.org/php/apt.gpg
gpg --dearmor -o /etc/apt/trusted.gpg.d/php.gpg < /tmp/debsury.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/php.list
apt-get update

echo "=== [3/7] Instalando PHP 8.5 e Extensões Necessárias ==="
apt-get install -y php8.5 php8.5-fpm php8.5-cli php8.5-mysql php8.5-curl php8.5-gd \
    php8.5-mbstring php8.5-xml php8.5-zip php8.5-bcmath php8.5-intl php8.5-sqlite3 php8.5-redis

echo "=== [4/7] Instalando Composer v2 ==="
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo "=== [5/7] Configurando Banco de Dados MariaDB ==="
systemctl enable --now mariadb
mysql -e "CREATE DATABASE IF NOT EXISTS sbc_sip_i CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'sbc_user'@'localhost' IDENTIFIED BY 'Secret_SBC_2026!';"
mysql -e "GRANT ALL PRIVILEGES ON sbc_sip_i.* TO 'sbc_user'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo "=== [6/7] Instalando e Configurando o Laravel 13 ==="
INSTALL_DIR="/var/www/sbc-sip-i"
mkdir -p $INSTALL_DIR

cp -r ./* $INSTALL_DIR/ || true
cd $INSTALL_DIR

composer install --no-dev --optimize-autoloader

if [ ! -f ".env" ]; then
    cp .env.example .env
fi

php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --class=CarrierSeeder --force
php artisan storage:link || true

chown -R www-data:www-data $INSTALL_DIR
chmod -R 775 $INSTALL_DIR/storage $INSTALL_DIR/bootstrap/cache

echo "=== [7/7] Configurando Nginx para o SBC Telsipti ==="
cat << 'EOF' > /etc/nginx/sites-available/sbc-sip-i
server {
    listen 80;
    server_name _;
    root /var/www/sbc-sip-i/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

ln -sf /etc/nginx/sites-available/sbc-sip-i /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
systemctl restart php8.5-fpm
systemctl restart nginx

echo "=============================================================================="
echo " SBC Telsipti - SIP-i instalado com sucesso no Debian 12!"
echo " Acesse: http://$(hostname -I | awk '{print $1}')"
echo " Módulo DETRAF STFC & Gestão de Operadoras Ativado!"
echo "=============================================================================="
