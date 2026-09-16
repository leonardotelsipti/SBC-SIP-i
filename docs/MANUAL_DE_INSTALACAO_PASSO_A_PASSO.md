# MANUAL DE INSTALAÇÃO E OPERAÇÃO PASSO A PASSO
## SBC Telsipti - SIP-i (Kamailio 5.8+ & Laravel 13 no Debian 12 Bookworm)

---

### 1. Visão Geral da Arquitetura
O **SBC Telsipti** combina:
- **Núcleo de Sinalização:** Kamailio 5.8+ com módulos siputils, uac, dispatcher, sqlops, textops e multipart/mime.
- **Relé e Transcodificação de Mídia:** RTPEngine (kernel module ou userspace).
- **Módulo DETRAF STFC:** Tarifas TU-RL homologadas (Oi, Claro, Vivo, TIM) e apuração bilateral.
- **Camada Web e API:** PHP 8.5, Laravel 13 e Laravel Boost para governança, rotas e auditoria.
- **Persistência de Rede:** Serviço systemd (`sbc-network-restore.service`) e permissões sudoers para replicação imediata no kernel Linux sem perda pós-reboot.

---

### 2. Requisitos de Hardware & Rede
- **Sistema:** Debian 12 64-bit Minimal.
- **CPU:** Mínimo 4 Núcleos (Recomendado 8 Cores para 360+ canais).
- **RAM:** Mínimo 8 GB (16 GB recomendado).
- **Disco:** SSD NVMe 100 GB+.
- **Rede Dupla (Dual-Homed):**
  - `eth0`: 192.168.10.150/24 (Gerência / Web / SSH / MariaDB)
  - `eth1`: 10.200.50.10/24 (Tráfego de Voz / Kamailio / RTPEngine)
  - `eth1:1`: 10.250.250.90/29 (IP Virtual Operadora A)

---

### 3. Instalação Automatizada (Recomendada)
Para instalar tudo automaticamente com um único comando:

```bash
# 1. Extrair o arquivo zip no servidor
sudo mkdir -p /var/www/sbc-sip-i
sudo unzip -o sbc-telsipti-sip-i-laravel13.zip -d /var/www/sbc-sip-i/
cd /var/www/sbc-sip-i

# 2. Executar o script de instalação para Debian 12
chmod +x scripts/install-debian12-kamailio-sbc.sh
sudo ./scripts/install-debian12-kamailio-sbc.sh
```

---

### 4. Roteiro Passo a Passo Manual (Alternativo)

#### Passo 4.1: Atualização e Ferramentas Básicas
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget gnupg2 ca-certificates lsb-release git \
  zip unzip software-properties-common iptables iproute2 sudo sngrep sipp
```

#### Passo 4.2: Repositório e Pacotes do Kamailio 5.8
```bash
wget -O- https://deb.kamailio.org/kamailiodebkey.gpg | sudo gpg --dearmor -o /etc/apt/keyrings/kamailio.gpg
echo "deb [signed-by=/etc/apt/keyrings/kamailio.gpg] https://deb.kamailio.org/kamailio58 bookworm main" | sudo tee /etc/apt/sources.list.d/kamailio.list
sudo apt update
sudo apt install -y kamailio kamailio-mysql-modules kamailio-tls-modules kamailio-websocket-modules kamailio-utils rtpengine
```

#### Passo 4.3: Instalação do PHP 8.5, Nginx e MariaDB
```bash
curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb
sudo dpkg -i /tmp/debsuryorg-archive-keyring.deb
echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ bookworm main" | sudo tee /etc/apt/sources.list.d/php.list
sudo apt update
sudo apt install -y nginx mariadb-server mariadb-client composer \
  php8.5-fpm php8.5-mysql php8.5-mbstring php8.5-xml php8.5-curl php8.5-bcmath php8.5-zip
```

#### Passo 4.4: Criação do Banco de Dados
```bash
sudo mysql -u root << "EOF"
CREATE DATABASE IF NOT EXISTS sbc_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'sbc_user'@'localhost' IDENTIFIED BY 'SbcPass2026!#';
GRANT ALL PRIVILEGES ON sbc_db.* TO 'sbc_user'@'localhost';
FLUSH PRIVILEGES;
EOF
```

#### Passo 4.5: Configuração do Laravel 13
```bash
cd /var/www/sbc-sip-i
cp .env.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force --seed
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### Passo 4.6: Permissões Sudoers e Persistência de Rede
```bash
# Permissão para o usuário www-data executar /sbin/ip
sudo cp etc/sudoers.d/kamailio_routes /etc/sudoers.d/kamailio_routes
sudo chmod 0440 /etc/sudoers.d/kamailio_routes

# Serviço systemd de restauração no boot
sudo cp etc/systemd/system/sbc-network-restore.service /etc/systemd/system/
sudo chmod +x scripts/restore_network_backend.php
sudo systemctl daemon-reload
sudo systemctl enable --now sbc-network-restore.service
```

#### Passo 4.7: Nginx VirtualHost
```bash
sudo cp etc/nginx/sites-available/sbc-sip-i /etc/nginx/sites-available/
sudo ln -sf /etc/nginx/sites-available/sbc-sip-i /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl restart nginx
```

---

### 5. Diagnóstico e Testes

1. **Checagem de Sintaxe do Kamailio:**
   ```bash
   kamailio -c
   ```

2. **Suíte de Diagnóstico Completo:**
   ```bash
   sudo ./scripts/test-sbc-config.sh
   ```

3. **Teste de Ligação em Tempo Real (SIPp com ISUP Encapsulado):**
   ```bash
   sudo ./scripts/test-call-sipp.sh 127.0.0.1:5060 1130030100 11987654321
   ```

4. **Monitoramento ao Vivo de Sinalização SIP/ISUP:**
   ```bash
   sudo sngrep -d eth1
   ```

---

### 6. Acesso ao Painel Web
- **URL:** `http://<IP_DO_SERVIDOR_ETH0>/`
- **Login Padrão:** `admin@sbc-telsipti.com.br`
- **Senha Inicial:** `AdminSbc2026!`
