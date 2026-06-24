#!/usr/bin/env bash
# Post-deploy security smoke tests.
# Usage: DOMAIN=petforum.vn ./scripts/deploy/security-checklist.sh
set -euo pipefail

DOMAIN="${DOMAIN:-petforum.vn}"
BASE="https://${DOMAIN}"

check_code() {
  local path="$1"
  local label="$2"
  local code
  code=$(curl -s -o /dev/null -w '%{http_code}' "${BASE}${path}" || echo "000")
  echo "${code} → ${label}"
}

echo "=== Block sensitive files ==="
check_code "/.env" "env"
check_code "/wp-config.php" "wp-config"
check_code "/xmlrpc.php" "xmlrpc"
check_code "/readme.html" "readme"

echo ""
echo "=== Login URL ==="
check_code "/wp-login.php" "wp-login (expect 404)"
check_code "/dang-nhap-pet" "custom login (expect 200/302)"

echo ""
echo "=== Security headers ==="
curl -sI "${BASE}/" | grep -iE 'strict-transport|x-frame|x-content-type|referrer-policy' || true

echo ""
echo "Manual: https://www.ssllabs.com/ssltest/analyze.html?d=${DOMAIN}"
echo "Manual: https://securityheaders.com/?q=${BASE}"
