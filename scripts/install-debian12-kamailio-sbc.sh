#!/usr/bin/env bash
# ==============================================================================
# SBC Telsipti - Instalador Completo para Debian 12 (Bookworm)
# Componentes: Kamailio 5.8+ SBC, RTPEngine, MariaDB, Nginx, PHP 8.5, Laravel 13
# Suporte nativo a SIP-i (ISUP Q.1912.5), Módulo DETRAF e Ferramentas de Teste
# ==============================================================================

set -euo pipefail

# Cores de saída
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=====================================================================${NC}"
echo -e "${BLUE}   Iniciando Instalação do SBC Telsipti no Debian 12 (Bookworm)     ${NC}"
echo -e "${BLUE}=====================================================================${NC}"

# 1. Checagem de Root e SO
if [ "$(id -u)" -ne 0 ]; then
    echo -e "${RED}[ERRO] Este script deve ser executado como root (sudo).${NC}"
    exit 1
fi

if ! grep -q "bookworm" /etc/os-release; then
    echo -e "${RED}[ERRO] Este instalador foi projetado exclusivamente para Debian 12 (Bookworm).${NC}"
    exit 1
fi

echo -e "${GREEN}[1/8] Atualizando repositórios e instalando dependências base...${NC}"
apt-get update && apt-get upgrade -y
apt-get install -y curl wget gnupg2 lsb-release ca-certificates apt-transport-https \
    software-properties-common git unzip zip tar ufw net-tools iproute2 htop \
    supervisor iptables build-essential libpcap-dev

echo -e "${GREEN}[2/8] Adicionando repositório oficial do Kamailio 5.8 no Debian 12...${NC}"
curl -sS https://deb.kamailio.org/kamailiodebkey.gpg | gpg --dearmor -o /etc/apt/trusted.gpg.d/kamailio.gpg
echo "deb http://deb.kamailio.org/kamailio58 bookworm main" > /etc/apt/sources.list.d/kamailio.list

echo -e "${GREEN}[3/8] Adicionando repositório PHP 8.5 (Ondřej Surý)...${NC}"
curl -sSLo /tmp/debsury.gpg https://packages.sury.org/php/apt.gpg
gpg --dearmor -o /etc/apt/trusted.gpg.d/php.gpg < /tmp/debsury.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list

apt-get update

echo -e "${GREEN}[4/8] Instalando Kamailio, RTPEngine, sngrep e sipp...${NC}"
apt-get install -y kamailio kamailio-mysql-modules kamailio-tls-modules kamailio-websocket-modules \
    kamailio-json-modules kamailio-extra-modules kamailio-sctp-modules \
    rtpengine rtpengine-daemon sngrep sipp mariadb-server nginx \
    php8.5 php8.5-fpm php8.5-cli php8.5-mysql php8.5-curl php8.5-gd \
    php8.5-mbstring php8.5-xml php8.5-zip php8.5-bcmath php8.5-intl

echo -e "${GREEN}[5/8] Instalando Composer v2...${NC}"
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

echo -e "${GREEN}[6/8] Configurando Banco de Dados MariaDB e Tabelas do Kamailio/Laravel...${NC}"
systemctl enable --now mariadb
mysql -e "CREATE DATABASE IF NOT EXISTS sbc_sip_i CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'sbc_user'@'localhost' IDENTIFIED BY 'Secret_SBC_2026!';"
mysql -e "GRANT ALL PRIVILEGES ON sbc_sip_i.* TO 'sbc_user'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Configuração do kamctlrc
cat << 'EOF' > /etc/kamailio/kamctlrc
SIP_DOMAIN=192.168.10.250
DBENGINE=MYSQL
DBHOST=127.0.0.1
DBNAME=sbc_sip_i
DBRWUSER=sbc_user
DBRWPW="Secret_SBC_2026!"
DBROUSER=sbc_user
DBROPW="Secret_SBC_2026!"
CHARSET="utf8mb4"
EOF

# Cria tabelas padrão do Kamailio silenciosamente
yes | kamdbctl create || true

echo -e "${GREEN}[7/8] Instalando Aplicação Web Laravel 13 & DETRAF STFC...${NC}"
APP_DIR="/var/www/sbc-sip-i"
mkdir -p $APP_DIR
cp -r ./* $APP_DIR/ || true
cd $APP_DIR

composer install --no-dev --optimize-autoloader
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --class=CarrierSeeder --force
php artisan storage:link || true

chown -R www-data:www-data $APP_DIR
chmod -R 775 $APP_DIR/storage $APP_DIR/bootstrap/cache

# Nginx Virtual Host
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

echo -e "${GREEN}[8/9] Configurando Permissões Sudoers de Rede e Persistência Systemd...${NC}"
# Permissão sudo para o usuário www-data executar /sbin/ip sem senha
cat << 'EOF' > /etc/sudoers.d/kamailio_routes
www-data ALL=(ALL) NOPASSWD: /sbin/ip *, /usr/sbin/ip *
EOF
chmod 0440 /etc/sudoers.d/kamailio_routes

# Serviço systemd de restauração de rede no boot
cat << 'EOF' > /etc/systemd/system/sbc-network-restore.service
[Unit]
Description=Restaura Interfaces Virtuais e Rotas do SBC Telsipti
After=network-online.target mariadb.service
Wants=network-online.target

[Service]
Type=oneshot
ExecStart=/usr/bin/php /var/www/sbc-sip-i/scripts/restore_network_backend.php
RemainAfterExit=yes

[Install]
WantedBy=multi-user.target
EOF

chmod +x /var/www/sbc-sip-i/scripts/restore_network_backend.php 2>/dev/null || true
systemctl daemon-reload
systemctl enable sbc-network-restore.service

echo -e "${GREEN}[9/9] Ativando e Inicializando Daemons (Kamailio, RTPEngine, Nginx, PHP-FPM, Restauração de Rede)...${NC}"
systemctl enable --now rtpengine
systemctl enable --now kamailio
systemctl restart php8.5-fpm
systemctl restart nginx
systemctl start sbc-network-restore.service

# Validação do Kamailio
kamailio -c

echo -e "${BLUE}=====================================================================${NC}"
echo -e "${GREEN}  INSTALAÇÃO DO SBC KAMAILIO CONCLUÍDA COM SUCESSO NO DEBIAN 12!     ${NC}"
echo -e "${BLUE}=====================================================================${NC}"
echo " Painel Web: http://$(hostname -I | awk '{print $1}')"
echo " Núcleo SBC Kamailio: Porta 5060 (UDP/TCP)"
echo " RTPEngine: Porta de controle 22222 (UDP)"
echo " Para testar a configuração: bash scripts/test-sbc-config.sh"
echo " Para testar uma ligação real: bash scripts/test-call-sipp.sh"
echo -e "${BLUE}=====================================================================${NC}"
