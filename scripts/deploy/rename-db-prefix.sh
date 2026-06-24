#!/usr/bin/env bash
# Rename DB table prefix in SQL dump BEFORE importing to production.
# Usage: ./scripts/deploy/rename-db-prefix.sh /tmp/export.sql pet70_ > /tmp/export-pet70.sql
set -euo pipefail

INPUT="${1:?SQL file path required}"
NEW_PREFIX="${2:-pet70_}"
OLD_PREFIX="${3:-wp_}"

sed "s/\`${OLD_PREFIX}/\`${NEW_PREFIX}/g; s/ ${OLD_PREFIX}/ ${NEW_PREFIX}/g" "$INPUT"

echo "Renamed ${OLD_PREFIX} → ${NEW_PREFIX}" >&2
