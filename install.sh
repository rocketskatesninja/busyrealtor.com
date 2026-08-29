#!/usr/bin/env bash
# =============================================================================
# BusyRealtor.com — Server Install Script
# Supports: Debian 13 (Trixie), Ubuntu 22.04 / 24.04 LTS
# Web servers: Apache (default) or Nginx (pass --nginx flag)
# Usage:
#   bash install.sh                          # interactive
#   bash install.sh --nginx                  # use Nginx instead of Apache
#   bash install.sh --domain=example.com \
#                   --db-name=busyrealtor \
#                   --db-user=busyrealtor_user \
#                   --db-pass=secret \
#                   --app-path=/var/www/busyrealtor \
#                   --repo=https://github.com/yourorg/busyrealtor.git \
#                   --ssl-email=you@example.com \
#                   --ssl                    # run certbot after setup
# =============================================================================

set -euo pipefail

# ── Colour helpers ─────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
BLUE='\033[0;34m'; BOLD='\033[1m'; NC='\033[0m'

info()    { echo -e "${BLUE}[INFO]${NC}  $*"; }
success() { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*" >&2; exit 1; }
header()  { echo -e "\n${BOLD}${BLUE}═══ $* ═══${NC}\n"; }

# ── Root check ─────────────────────────────────────────────────────────────────
[[ $EUID -eq 0 ]] || error "Run this script as root: sudo bash install.sh"

# ── Detect distro ─────────────────────────────────────────────────────────────
IS_UBUNTU=false
grep -qi ubuntu /etc/os-release && IS_UBUNTU=true

# ── Default values ─────────────────────────────────────────────────────────────
WEB_SERVER="apache"
DOMAIN=""
DB_NAME="busyrealtor"
DB_USER="busyrealtor_user"
DB_PASS=""
APP_PATH="/var/www/busyrealtor"
REPO_URL=""
RUN_SSL=false
SSL_EMAIL=""
WEBSERVER_USER="www-data"
PHP_VER="8.4"

# ── Parse args ─────────────────────────────────────────────────────────────────
for arg in "$@"; do
    case $arg in
        --nginx)             WEB_SERVER="nginx" ;;
        --ssl)               RUN_SSL=true ;;
        --ssl-email=*)       SSL_EMAIL="${arg#*=}" ;;
        --domain=*)          DOMAIN="${arg#*=}" ;;
        --db-name=*)         DB_NAME="${arg#*=}" ;;
        --db-user=*)         DB_USER="${arg#*=}" ;;
        --db-pass=*)         DB_PASS="${arg#*=}" ;;
        --app-path=*)        APP_PATH="${arg#*=}" ;;
        --repo=*)            REPO_URL="${arg#*=}" ;;
        *) warn "Unknown argument: $arg" ;;
    esac
done

# ── Interactive prompts (if not passed as args) ────────────────────────────────
echo ""
echo -e "${BOLD}BusyRealtor.com — Install Script${NC}"
echo -e "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

prompt_if_empty() {
    local var_name="$1" prompt_text="$2" default="$3" is_secret="${4:-false}"
    local current_val
    eval current_val="\$$var_name"
    if [[ -z "$current_val" ]]; then
        if [[ "$is_secret" == "true" ]]; then
            read -rsp "${prompt_text} [default: ${default}]: " input
            echo ""
        else
            read -rp "${prompt_text} [default: ${default}]: " input
        fi
        if [[ -z "$input" ]]; then
            eval "$var_name=\"$default\""
        else
            eval "$var_name=\"$input\""
        fi
    fi
}

prompt_if_empty DOMAIN    "Domain name (e.g. busyrealtor.com)"   "busyrealtor.com"
prompt_if_empty DB_NAME   "MySQL database name"                  "busyrealtor"
prompt_if_empty DB_USER   "MySQL username"                       "busyrealtor_user"
prompt_if_empty DB_PASS   "MySQL password"                       "$(openssl rand -base64 16)" "true"
prompt_if_empty APP_PATH  "Application path"                     "/var/www/busyrealtor"
prompt_if_empty REPO_URL  "Git repository URL (leave blank to copy from current dir)" ""

if [[ "$RUN_SSL" == "true" ]]; then
    prompt_if_empty SSL_EMAIL "Email address for Let's Encrypt SSL certificate" "admin@${DOMAIN}"
fi

echo ""
info "Web server : $WEB_SERVER"
info "Domain     : $DOMAIN"
info "App path   : $APP_PATH"
info "DB name    : $DB_NAME"
info "DB user    : $DB_USER"
info "SSL        : $RUN_SSL"
info "Distro     : $($IS_UBUNTU && echo 'Ubuntu' || echo 'Debian')"
echo ""
read -rp "Proceed with installation? [y/N]: " confirm
[[ "$confirm" =~ ^[Yy]$ ]] || { info "Aborted."; exit 0; }

# =============================================================================
# 1. SYSTEM PACKAGES
# =============================================================================
header "System Packages"

export DEBIAN_FRONTEND=noninteractive
apt-get update -qq

# PHP repository — only needed on Ubuntu; Debian 13+ ships PHP 8.4 natively
if ! php${PHP_VER} --version &>/dev/null; then
    if [[ "$IS_UBUNTU" == "true" ]]; then
        info "Adding Ondrej PHP PPA (Ubuntu)..."
        apt-get install -y -qq software-properties-common
        add-apt-repository -y ppa:ondrej/php
        apt-get update -qq
    else
        info "Using default repos for PHP ${PHP_VER} (Debian)..."
    fi
fi

# Core extensions — sodium, json, fileinfo, tokenizer, ctype are bundled in PHP 8.4+
PHP_EXTENSIONS=(
    "php${PHP_VER}"
    "php${PHP_VER}-cli"
    "php${PHP_VER}-fpm"
    "php${PHP_VER}-mysql"
    "php${PHP_VER}-mbstring"
    "php${PHP_VER}-xml"
    "php${PHP_VER}-curl"
    "php${PHP_VER}-zip"
    "php${PHP_VER}-gd"
    "php${PHP_VER}-bcmath"
    "php${PHP_VER}-intl"
)

info "Installing PHP ${PHP_VER} and extensions..."
apt-get install -y -qq "${PHP_EXTENSIONS[@]}"

# MariaDB on Debian, MySQL on Ubuntu
if [[ "$IS_UBUNTU" == "true" ]]; then
    info "Installing MySQL server..."
    apt-get install -y -qq mysql-server
    DB_SERVICE="mysql"
    DB_CLIENT="mysql"
else
    info "Installing MariaDB server..."
    apt-get install -y -qq mariadb-server
    DB_SERVICE="mariadb"
    DB_CLIENT="mariadb"
fi

if [[ "$WEB_SERVER" == "nginx" ]]; then
    info "Installing Nginx..."
    apt-get install -y -qq nginx
else
    info "Installing Apache..."
    apt-get install -y -qq apache2
    a2enmod proxy_fcgi setenvif
fi
# Both Apache and Nginx use PHP-FPM
apt-get install -y -qq "php${PHP_VER}-fpm"
systemctl enable "php${PHP_VER}-fpm" --quiet
systemctl start  "php${PHP_VER}-fpm"

# Increase PHP upload limits for image uploads
PHP_FPM_INI="/etc/php/${PHP_VER}/fpm/php.ini"
if [ -f "$PHP_FPM_INI" ]; then
    sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 20M/' "$PHP_FPM_INI"
    sed -i 's/^post_max_size = .*/post_max_size = 25M/' "$PHP_FPM_INI"
    systemctl reload "php${PHP_VER}-fpm"
    info "PHP upload limits set to 20M / 25M"
fi

info "Installing Composer..."
if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

info "Installing Node.js 20 (required for Vite build)..."
if ! node --version 2>/dev/null | grep -qE "^v(20|22)"; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y -qq nodejs
fi

info "Installing Git, Certbot, and utilities..."
apt-get install -y -qq git unzip curl certbot

if [[ "$WEB_SERVER" == "nginx" ]]; then
    apt-get install -y -qq python3-certbot-nginx
else
    apt-get install -y -qq python3-certbot-apache
fi

success "System packages installed."

# =============================================================================
# 2. DATABASE SETUP
# =============================================================================
header "Database Setup"

info "Starting database service..."
systemctl enable "$DB_SERVICE" --quiet
systemctl start "$DB_SERVICE"

info "Creating database and user..."

# Use a temp SQL file so passwords with special chars are safe
SQL_SETUP_FILE="$(mktemp)"
cat > "$SQL_SETUP_FILE" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

$DB_CLIENT -u root < "$SQL_SETUP_FILE"
rm -f "$SQL_SETUP_FILE"
success "Database '${DB_NAME}' and user '${DB_USER}' created."

# =============================================================================
# 3. APPLICATION SETUP
# =============================================================================
header "Application Setup"

if [[ -n "$REPO_URL" ]]; then
    info "Cloning repository from ${REPO_URL}..."
    git clone "$REPO_URL" "$APP_PATH"
else
    if [[ -f "$(dirname "$0")/artisan" ]]; then
        SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
        DEST_DIR="$(cd "$APP_PATH" 2>/dev/null && pwd || echo "$APP_PATH")"
        if [[ "$SCRIPT_DIR" != "$DEST_DIR" ]]; then
            info "Copying application from current directory..."
            rsync -a --exclude='.git' --exclude='vendor' --exclude='node_modules' \
                  --exclude='storage/logs/*.log' \
                  "$SCRIPT_DIR/" "$APP_PATH/"
        else
            info "Application already at ${APP_PATH}, skipping copy."
        fi
    else
        error "No --repo provided and artisan not found in current directory. Cannot install app."
    fi
fi

cd "$APP_PATH"

info "Installing Composer dependencies..."
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-scripts --quiet

info "Installing npm dependencies and building frontend assets..."
npm install --quiet
npm run build

# =============================================================================
# 4. ENVIRONMENT FILE
# =============================================================================

# Ensure required directories exist before any artisan commands
mkdir -p "${APP_PATH}/bootstrap/cache"
mkdir -p "${APP_PATH}/storage/framework/sessions"
mkdir -p "${APP_PATH}/storage/framework/views"
mkdir -p "${APP_PATH}/storage/framework/cache/data"
mkdir -p "${APP_PATH}/storage/logs"
chmod -R 775 "${APP_PATH}/bootstrap/cache"
chmod -R 775 "${APP_PATH}/storage"

header "Environment Configuration"

ENV_FILE="${APP_PATH}/.env"

if [[ -f "${APP_PATH}/.env.example" ]]; then
    cp "${APP_PATH}/.env.example" "$ENV_FILE"
else
    error ".env.example not found. Cannot create .env."
fi

sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN}|"         "$ENV_FILE"
sed -i "s|APP_ENV=.*|APP_ENV=production|"                  "$ENV_FILE"
sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|"                   "$ENV_FILE"
sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|"          "$ENV_FILE"
sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USER}|"          "$ENV_FILE"
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|"          "$ENV_FILE"
sed -i "s|MAIL_FROM_ADDRESS=.*|MAIL_FROM_ADDRESS=noreply@${DOMAIN}|" "$ENV_FILE"

php artisan key:generate --force --quiet

# Run package discovery now that .env exists
COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --quiet
php artisan package:discover --ansi

success ".env configured."

# =============================================================================
# 5. MIGRATIONS & SEEDER
# =============================================================================
header "Database Migrations"

info "Running migrations..."
php artisan migrate --force

info "Seeding database (super admin + demo tenant)..."
php artisan db:seed --force

success "Database migrated and seeded."
warn "Seeded accounts use a well-known default password ('secret') — anyone who reads this"
warn "public repo knows it. Change both account passwords before this instance is reachable"
warn "from the internet (see the credentials + checklist at the end of this script)."

# =============================================================================
# 6. STORAGE & PERMISSIONS
# =============================================================================
header "File Permissions"

info "Creating required directories..."
mkdir -p "${APP_PATH}/storage/app/public"
mkdir -p "${APP_PATH}/storage/framework/sessions"
mkdir -p "${APP_PATH}/storage/framework/views"
mkdir -p "${APP_PATH}/storage/framework/cache/data"
mkdir -p "${APP_PATH}/storage/logs"
mkdir -p "${APP_PATH}/bootstrap/cache"
mkdir -p "${APP_PATH}/public/uploads"

info "Setting ownership and permissions..."
chown -R "${WEBSERVER_USER}:${WEBSERVER_USER}" "${APP_PATH}/storage"
chown -R "${WEBSERVER_USER}:${WEBSERVER_USER}" "${APP_PATH}/bootstrap/cache"
chown -R "${WEBSERVER_USER}:${WEBSERVER_USER}" "${APP_PATH}/public/uploads"

chmod -R 775 "${APP_PATH}/storage"
chmod -R 775 "${APP_PATH}/bootstrap/cache"
chmod -R 775 "${APP_PATH}/public/uploads"

# Make sure the current user (e.g. deploy user) can also write
if [[ -n "${SUDO_USER:-}" ]]; then
    usermod -aG "${WEBSERVER_USER}" "$SUDO_USER" || true
fi

info "Creating storage symlink..."
php artisan storage:link --quiet || true

success "Permissions set."

# =============================================================================
# 7. LARAVEL OPTIMISATION
# =============================================================================
header "Laravel Optimisation"

php artisan config:cache
php artisan route:cache
php artisan view:cache
success "Config, route, and view caches built."

# =============================================================================
# 8. WEB SERVER CONFIGURATION
# =============================================================================
header "Web Server Configuration"

CONF_DIR_APACHE="/etc/apache2/sites-available"
CONF_DIR_NGINX="/etc/nginx/sites-available"
CONF_ENABLED_NGINX="/etc/nginx/sites-enabled"

if [[ "$WEB_SERVER" == "apache" ]]; then
    info "Configuring Apache..."

    # Enable required modules
    a2enmod rewrite headers ssl deflate expires proxy_fcgi setenvif

    # Use event MPM (not prefork) — much better with FPM
    a2dismod mpm_prefork 2>/dev/null || true
    a2enmod  mpm_event   2>/dev/null || true
    # Ensure mod_php is not loaded (conflicts with event MPM)
    a2dismod "php${PHP_VER}" 2>/dev/null || true

    a2enconf "php${PHP_VER}-fpm"

    # Write HTTP-only vhost — certbot creates the SSL vhost via --ssl flag
    APACHE_CONF="${CONF_DIR_APACHE}/${DOMAIN}.conf"
    cat > "$APACHE_CONF" <<APACHECONF
# BusyRealtor — Apache Virtual Host (HTTP)
# Domain: ${DOMAIN}
# Generated by install.sh on $(date +%Y-%m-%d)
# SSL vhost is created automatically by certbot

<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAlias www.${DOMAIN}
    DocumentRoot ${APP_PATH}/public

    <Directory ${APP_PATH}/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        Header always set X-Content-Type-Options "nosniff"
        Header always set X-Frame-Options "SAMEORIGIN"
        Header always set X-XSS-Protection "1; mode=block"
        Header always set Referrer-Policy "strict-origin-when-cross-origin"
    </Directory>

    # PHP via PHP-FPM
    <FilesMatch "\.php$">
        SetHandler "proxy:unix:/run/php/php${PHP_VER}-fpm.sock|fcgi://localhost"
    </FilesMatch>

    ErrorLog \${APACHE_LOG_DIR}/${DOMAIN}_error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN}_access.log combined
    LogLevel warn
</VirtualHost>

# Block access to sensitive files
<FilesMatch "\.(env|sql|sh|log|md|git|lock)$">
    Require all denied
</FilesMatch>

<Directory ${APP_PATH}/vendor>
    Require all denied
</Directory>
APACHECONF

    # Disable default site, enable ours
    a2dissite 000-default.conf 2>/dev/null || true
    a2ensite "${DOMAIN}.conf"
    systemctl enable apache2 --quiet
    systemctl restart apache2
    success "Apache configured and restarted."

else
    info "Configuring Nginx..."

    systemctl enable "php${PHP_VER}-fpm" --quiet
    systemctl start  "php${PHP_VER}-fpm"

    # Write HTTP-only server block — certbot adds SSL via --ssl flag
    NGINX_CONF="${CONF_DIR_NGINX}/${DOMAIN}.conf"
    cat > "$NGINX_CONF" <<NGINXCONF
# BusyRealtor — Nginx Server Block (HTTP)
# Domain: ${DOMAIN}
# Generated by install.sh on $(date +%Y-%m-%d)
# SSL block is created automatically by certbot

server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};

    root ${APP_PATH}/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options           "SAMEORIGIN"                        always;
    add_header X-Content-Type-Options    "nosniff"                           always;
    add_header X-XSS-Protection          "1; mode=block"                     always;
    add_header Referrer-Policy           "strict-origin-when-cross-origin"   always;

    # Logs
    access_log /var/log/nginx/${DOMAIN}_access.log;
    error_log  /var/log/nginx/${DOMAIN}_error.log warn;

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
    gzip_vary on;

    # Laravel front controller
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass   unix:/run/php/php${PHP_VER}-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include        fastcgi_params;
        fastcgi_read_timeout 120;
    }

    # Static asset caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Block sensitive files
    location ~ /\.(env|git|sql|sh|log) {
        deny all;
    }
    location ~ ^/(vendor|storage/logs)/ {
        deny all;
    }
}
NGINXCONF

    ln -sf "$NGINX_CONF" "${CONF_ENABLED_NGINX}/${DOMAIN}.conf"
    rm -f "${CONF_ENABLED_NGINX}/default" 2>/dev/null || true
    nginx -t
    systemctl enable nginx --quiet
    systemctl restart nginx
    success "Nginx configured and restarted."
fi

# =============================================================================
# 9. CRON JOB (Laravel Scheduler)
# =============================================================================
header "Cron Job"

CRON_LINE="* * * * * ${WEBSERVER_USER} cd ${APP_PATH} && php artisan schedule:run >> /dev/null 2>&1"
CRON_FILE="/etc/cron.d/busyrealtor"

cat > "$CRON_FILE" <<CRON
# BusyRealtor Laravel Scheduler
# Runs every minute — Laravel decides which commands actually execute
${CRON_LINE}
CRON

chmod 644 "$CRON_FILE"
success "Cron job installed at ${CRON_FILE}"

# Verify it's active
info "Cron entry:"
cat "$CRON_FILE"

# =============================================================================
# 10. QUEUE WORKER (systemd service)
# =============================================================================
header "Queue Worker"

QUEUE_SERVICE_FILE="/etc/systemd/system/busyrealtor-queue.service"

cat > "$QUEUE_SERVICE_FILE" <<QUEUESERVICE
[Unit]
Description=BusyRealtor Laravel Queue Worker
After=network.target ${DB_SERVICE}.service

[Service]
User=${WEBSERVER_USER}
Group=${WEBSERVER_USER}
WorkingDirectory=${APP_PATH}
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5
StandardOutput=append:${APP_PATH}/storage/logs/queue.log
StandardError=append:${APP_PATH}/storage/logs/queue.log

[Install]
WantedBy=multi-user.target
QUEUESERVICE

systemctl daemon-reload
systemctl enable busyrealtor-queue --quiet
systemctl start  busyrealtor-queue
success "Queue worker service installed and started."
info "Status: $(systemctl is-active busyrealtor-queue)"

# =============================================================================
# 11. SSL (OPTIONAL)
# =============================================================================
if [[ "$RUN_SSL" == "true" ]]; then
    header "SSL Certificate (Let's Encrypt)"
    info "Running Certbot for ${DOMAIN} and www.${DOMAIN}..."

    if [[ "$WEB_SERVER" == "apache" ]]; then
        certbot --apache -d "${DOMAIN}" -d "www.${DOMAIN}" --non-interactive \
            --agree-tos --email "${SSL_EMAIL}" --redirect
    else
        certbot --nginx -d "${DOMAIN}" -d "www.${DOMAIN}" --non-interactive \
            --agree-tos --email "${SSL_EMAIL}" --redirect
    fi

    # Auto-renew cron (certbot usually adds this, but ensure it)
    if ! crontab -l 2>/dev/null | grep -q "certbot renew"; then
        (crontab -l 2>/dev/null; echo "0 3 * * 0 certbot renew --quiet") | crontab -
    fi
    success "SSL certificate installed. Auto-renewal configured."
fi

# =============================================================================
# 12. FINAL SUMMARY
# =============================================================================
header "Installation Complete"

echo -e "${GREEN}${BOLD}BusyRealtor is installed!${NC}"
echo ""
echo -e "  Site URL       : ${BOLD}https://${DOMAIN}${NC}"
echo -e "  App path       : ${BOLD}${APP_PATH}${NC}"
echo -e "  Web server     : ${BOLD}${WEB_SERVER}${NC}"
echo ""
echo -e "  ${BOLD}Default accounts (change these immediately):${NC}"
echo -e "  Super admin    : contact@punchlistify.com / secret"
echo -e "  Demo tenant    : admin@demorealty.com / secret"
echo ""
echo -e "  ${BOLD}Database:${NC}"
echo -e "  Name           : ${DB_NAME}"
echo -e "  User           : ${DB_USER}"
echo -e "  Password       : ${DB_PASS}"
echo ""
echo -e "  ${RED}${BOLD}⚠ CHANGE THE SEEDED PASSWORDS NOW${NC}${RED} — 'secret' is a fixed default in this"
echo -e "  public repo's seeder, not a per-install secret. Anyone can read it on GitHub.${NC}"
echo -e "  ${BOLD}Do this before the site is reachable from the internet.${NC}"
echo ""
echo -e "  ${BOLD}Post-install checklist:${NC}"
echo -e "  [ ] Set Stripe keys in Super Admin → Settings"
echo -e "  [ ] Set Google Maps API key in Tenant → Settings → Integrations"
echo -e "  [ ] Configure SMTP mail in .env or Tenant → Settings → Messages"
echo -e "  [ ] ${BOLD}Change default account passwords${NC} (see warning above)"
if [[ "$RUN_SSL" == "false" ]]; then
echo -e "  [ ] Run SSL: sudo certbot --${WEB_SERVER} -d ${DOMAIN} -d www.${DOMAIN}"
fi
echo ""
echo -e "  ${BOLD}Useful commands:${NC}"
echo -e "  Clear caches   : cd ${APP_PATH} && php artisan config:clear && php artisan view:clear"
echo -e "  View logs      : tail -f ${APP_PATH}/storage/logs/laravel.log"
echo -e "  Queue log      : tail -f ${APP_PATH}/storage/logs/queue.log"
echo -e "  Queue status   : systemctl status busyrealtor-queue"
echo -e "  Run migrations : cd ${APP_PATH} && php artisan migrate"
echo -e "  Test scheduler : cd ${APP_PATH} && php artisan schedule:run"
echo ""
