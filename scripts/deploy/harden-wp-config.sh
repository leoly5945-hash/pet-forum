#!/usr/bin/env bash
# Apply wp-config hardening on production via WP-CLI.
# Usage: docker exec pet-forum-wordpress bash /scripts/deploy/harden-wp-config.sh
set -euo pipefail

WP="wp --path=/var/www/html --allow-root"

$WP config shuffle-salts
$WP config set table_prefix "${WORDPRESS_TABLE_PREFIX:-pet70_}"
$WP config set DISALLOW_FILE_EDIT true --raw
$WP config set FORCE_SSL_ADMIN true --raw
$WP config set WP_AUTO_UPDATE_CORE false --raw
$WP config set WP_DEBUG false --raw
$WP config set WP_DEBUG_LOG false --raw
$WP config set WP_DEBUG_DISPLAY false --raw
$WP config set SCRIPT_DEBUG false --raw
$WP config set WP_POST_REVISIONS 5 --raw
$WP config set WP_MEMORY_LIMIT '256M'
$WP config set WP_MAX_MEMORY_LIMIT '512M'

echo "=== Security config applied ==="
$WP config list --fields=name,value | grep -E 'DISALLOW|FORCE_SSL|WP_DEBUG|table_prefix|AUTO_UPDATE' || true

echo "Add to wp-config.php if not present:"
echo "  require_once __DIR__ . '/wp-config-extra.php';"
