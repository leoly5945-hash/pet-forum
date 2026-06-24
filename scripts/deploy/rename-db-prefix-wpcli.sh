#!/usr/bin/env bash
# Rename live WordPress DB prefix wp_ → pet70_ (run ONCE on target environment).
# Prefer SQL dump rename (rename-db-prefix.sh) before first production import when possible.
#
# Usage: ./scripts/deploy/rename-db-prefix-wpcli.sh
set -euo pipefail

CONTAINER="${WPCLI_CONTAINER:-pet-forum-wpcli}"
WP_PATH="${WP_PATH:-/var/www/html}"
OLD_PREFIX="${OLD_PREFIX:-wp_}"
NEW_PREFIX="${NEW_PREFIX:-pet70_}"

echo "=== Rename DB prefix ${OLD_PREFIX} → ${NEW_PREFIX} ==="

docker exec "${CONTAINER}" wp db export "/tmp/backup-before-prefix-$(date +%Y%m%d_%H%M).sql" \
  --path="${WP_PATH}" --allow-root
echo "✅ Backup exported inside container /tmp/"

docker exec "${CONTAINER}" wp search-replace "${OLD_PREFIX}" "${NEW_PREFIX}" \
  --network --precise --all-tables \
  --path="${WP_PATH}" --allow-root

docker exec pet-forum-wordpress sed -i \
  "s/\$table_prefix = '${OLD_PREFIX}';/\$table_prefix = '${NEW_PREFIX}';/" \
  "${WP_PATH}/wp-config.php"

echo "✅ wp-config.php table_prefix updated"

docker exec "${CONTAINER}" wp config set table_prefix "${NEW_PREFIX}" --path="${WP_PATH}" --allow-root 2>/dev/null || true

echo "=== Verify ==="
docker exec "${CONTAINER}" wp db check --path="${WP_PATH}" --allow-root || true
docker exec "${CONTAINER}" wp option get siteurl --path="${WP_PATH}" --allow-root

echo "Done."
