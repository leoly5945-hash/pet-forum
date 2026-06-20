#!/usr/bin/env bash
# Bootstrap WordPress + plugins via WP-CLI inside the wpcli container.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if [[ -f .env ]]; then
  set -a
  # shellcheck disable=SC1091
  source <(grep -v '^[[:space:]]*#' .env | sed 's/\r$//')
  set +a
fi

WP="${WP:-docker compose exec -T wpcli wp}"
WP_URL="${WP_URL:-http://localhost:8080}"
WP_TITLE="${WP_TITLE:-PetNest Forum}"
WP_ADMIN_USER="${WP_ADMIN_USER:-admin}"
WP_ADMIN_PASSWORD="${WP_ADMIN_PASSWORD:-petforum_admin_secret}"
WP_ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@petforum.local}"

log() { printf '\n==> %s\n' "$*"; }

log "Waiting for WordPress..."
until $WP core is-installed >/dev/null 2>&1; do
  sleep 2
done

if ! $WP core is-installed >/dev/null 2>&1; then
  log "Installing WordPress core..."
  $WP core install \
    --url="$WP_URL" \
    --title="$WP_TITLE" \
    --admin_user="$WP_ADMIN_USER" \
    --admin_password="$WP_ADMIN_PASSWORD" \
    --admin_email="$WP_ADMIN_EMAIL" \
    --skip-email
else
  log "WordPress core already installed."
fi

log "Updating WordPress core to latest..."
$WP core update
$WP core update-db

log "Installing Vietnamese language pack..."
$WP language core install vi
$WP site switch-language en_US
$WP language plugin install vi --all || true

log "Installing and activating plugins..."
$WP plugin install wpforo polylang classic-editor --activate

log "Configuring permalink structure..."
$WP rewrite structure '/%postname%/' --hard
$WP rewrite flush --hard

log "Writing Apache rewrite rules for Docker..."
cat > "$ROOT_DIR/wordpress/.htaccess" <<'EOF'
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
EOF

log "Initializing Polylang languages (EN/VI)..."
$WP eval-file /scripts/init-polylang-languages.php

log "Setup complete."
$WP plugin list
$WP language core list --status=installed

printf '\nWordPress: %s\n' "$WP_URL"
printf 'wp-admin:  %s/wp-admin\n' "$WP_URL"
printf 'Admin:     %s / (see .env WP_ADMIN_PASSWORD)\n\n' "$WP_ADMIN_USER"
