#!/usr/bin/env bash
# Post-deploy security smoke tests.
# Usage: DOMAIN=petforum.vn ./scripts/deploy/security-checklist.sh
set -euo pipefail

DOMAIN="${DOMAIN:-petforum.vn}"
BASE="https://${DOMAIN}"

pass() { echo "✅ $1"; }
fail() { echo "❌ $1"; }

echo "=== Security Checklist — ${DOMAIN} ==="

STATUS=$(curl -s -o /dev/null -w '%{http_code}' "${BASE}/" || echo "000")
[ "$STATUS" = "200" ] && pass "HTTPS home ${STATUS}" || fail "HTTPS home ${STATUS}"

REDIRECT=$(curl -s -o /dev/null -w '%{redirect_url}' "http://${DOMAIN}/" || true)
echo "HTTP→HTTPS redirect: ${REDIRECT:-none}"

LOGIN=$(curl -s -o /dev/null -w '%{http_code}' "${BASE}/wp-login.php" || echo "000")
[ "$LOGIN" = "403" ] || [ "$LOGIN" = "404" ] && pass "wp-login.php blocked (${LOGIN})" || fail "wp-login.php should be 403/404, got ${LOGIN}"

CUSTOM=$(curl -s -o /dev/null -w '%{http_code}' "${BASE}/dang-nhap-pet" || echo "000")
[ "$CUSTOM" = "200" ] || [ "$CUSTOM" = "302" ] && pass "custom login /dang-nhap-pet (${CUSTOM})" || fail "custom login (${CUSTOM})"

XMLRPC=$(curl -s -o /dev/null -w '%{http_code}' "${BASE}/xmlrpc.php" || echo "000")
[ "$XMLRPC" = "403" ] || [ "$XMLRPC" = "404" ] && pass "xmlrpc.php blocked (${XMLRPC})" || fail "xmlrpc.php (${XMLRPC})"

ENV=$(curl -s -o /dev/null -w '%{http_code}' "${BASE}/.env" || echo "000")
[ "$ENV" = "403" ] || [ "$ENV" = "404" ] && pass ".env not public (${ENV})" || fail ".env exposed (${ENV})"

HSTS=$(curl -sI "${BASE}/" | grep -i 'strict-transport' || true)
[ -n "$HSTS" ] && pass "HSTS present" || fail "HSTS missing"

XFO=$(curl -sI "${BASE}/" | grep -i 'x-frame-options' || true)
[ -n "$XFO" ] && pass "X-Frame-Options present" || fail "X-Frame-Options missing"

echo ""
echo "Manual: https://www.ssllabs.com/ssltest/analyze.html?d=${DOMAIN}"
echo "Manual: https://securityheaders.com/?q=${BASE}"
echo "=== Done ==="
