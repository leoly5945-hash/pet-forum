#!/usr/bin/env bash
# Publish pet-forum branch to GitHub and open a draft PR.
#
# Prerequisites (run once):
#   brew install gh
#   gh auth login -h github.com -p https -s repo,read:org
#
# Usage:
#   ./scripts/publish-to-github.sh
#   ./scripts/publish-to-github.sh --public
#   ./scripts/publish-to-github.sh --push-only   # repo đã tạo thủ công trên GitHub
#   GITHUB_REPO=leoly5945-hash/pet-forum ./scripts/publish-to-github.sh
#
# Lưu ý: KHÔNG dùng placeholder "ten-user/ten-repo" — thay bằng owner/repo thật.

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

BRANCH="${BRANCH:-cursor/hybrid-homepage}"
BASE="${BASE:-main}"
VISIBILITY="--private"
PUSH_ONLY=0
for arg in "$@"; do
	case "$arg" in
		--public) VISIBILITY="--public" ;;
		--push-only) PUSH_ONLY=1 ;;
	esac
done

GH="$(command -v gh || true)"
if [[ -z "$GH" ]]; then
	echo "❌ gh CLI not found. Install: brew install gh"
	exit 1
fi

if ! "$GH" auth status >/dev/null 2>&1; then
	echo "❌ Chưa đăng nhập GitHub. Chạy:"
	echo "   gh auth login -h github.com -p https -s repo,read:org"
	exit 1
fi

if ! git rev-parse --verify "$BRANCH" >/dev/null 2>&1; then
	echo "❌ Branch không tồn tại: $BRANCH"
	exit 1
fi

if ! git rev-parse --verify "$BASE" >/dev/null 2>&1; then
	echo "❌ Branch base không tồn tại: $BASE"
	echo "   Tạo main trống trước hoặc đặt BASE=main sau khi đã có nhánh main."
	exit 1
fi

REMOTE="${GITHUB_REPO:-}"
if [[ -z "$REMOTE" ]]; then
	USER="$("$GH" api user -q .login)"
	REMOTE="${USER}/pet-forum"
fi
echo "→ Repo đích: $REMOTE"

setup_remote() {
	if git remote get-url origin >/dev/null 2>&1; then
		echo "→ Remote origin: $(git remote get-url origin)"
		return 0
	fi

	if "$GH" repo view "$REMOTE" >/dev/null 2>&1; then
		echo "→ Repo đã tồn tại trên GitHub, gắn remote..."
		git remote add origin "https://github.com/${REMOTE}.git"
		return 0
	fi

	if [[ "$PUSH_ONLY" -eq 1 ]]; then
		echo "❌ --push-only: repo $REMOTE chưa tồn tại hoặc bạn không có quyền xem."
		echo "   Tạo repo thủ công: https://github.com/new → tên pet-forum"
		echo "   Rồi chạy lại: GITHUB_REPO=${REMOTE} ./scripts/publish-to-github.sh --push-only"
		exit 1
	fi

	echo "→ Tạo repo GitHub: $REMOTE ($VISIBILITY)"
	if ! "$GH" repo create "$REMOTE" $VISIBILITY --description "Pet Forum — WordPress + wpForo hybrid homepage" 2>/dev/null; then
		echo ""
		echo "❌ Không tạo được repo — token thiếu quyền createRepository."
		echo ""
		echo "Cách 1 — Đăng nhập lại với đủ quyền:"
		echo "   gh auth logout"
		echo "   gh auth login -h github.com -p https -s repo,read:org"
		echo "   (chọn Login with a web browser, KHÔNG dùng fine-grained token hạn chế)"
		echo ""
		echo "Cách 2 — Tạo repo thủ công:"
		echo "   1. Mở https://github.com/new"
		echo "   2. Repository name: pet-forum"
		echo "   3. Không tick README/license (repo trống)"
		echo "   4. Chạy: GITHUB_REPO=${REMOTE} ./scripts/publish-to-github.sh --push-only"
		exit 1
	fi
	git remote add origin "https://github.com/${REMOTE}.git"
}

setup_remote

echo "→ Push $BASE..."
git push -u origin "$BASE"

echo "→ Push $BRANCH..."
git push -u origin "$BRANCH"

PR_URL="$("$GH" pr list --head "$BRANCH" --base "$BASE" --json url -q '.[0].url' 2>/dev/null || true)"
if [[ -n "$PR_URL" && "$PR_URL" != "null" ]]; then
	echo "✅ PR đã có: $PR_URL"
	exit 0
fi

echo "→ Tạo draft PR..."
"$GH" pr create --draft --base "$BASE" --head "$BRANCH" \
	--title "Add hybrid homepage with RSS news aggregator" \
	--body "$(cat <<'EOF'
## Summary
- Add `pf-news-aggregator` plugin (RSS fetch, AI/Google translate, `pet_news` CPT)
- Add PF Home template: hero, news grid, trending, lazy-loaded wpForo sidebar
- Universal translation layer + bilingual reply toolbar for wpForo

## Test plan
- [ ] Visit homepage and verify hero + news grid
- [ ] `docker compose exec -T wpcli wp pf-news fetch --path=/var/www/html --allow-root`
- [ ] Community sidebar loads via AJAX
- [ ] Reply translation toolbar on topic page
EOF
)"

echo "✅ Xong!"
