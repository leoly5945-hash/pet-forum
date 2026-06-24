# HANDOVER — Pet Forum Vietnam

Tài liệu bàn giao dự án để tiếp tục làm việc. Nạp file này vào Cursor Composer hoặc phiên agent mới.

**Cập nhật lần cuối:** 2026-06-22  
**Branch:** `cursor/wpforo-user-meta-fields`  
**Commits gần nhất:** `fda600d` (security hardening), `524fa5d` (AI chatbot + upload + homepage)

---

## 1. TỔNG QUAN DỰ ÁN

| Mục | Giá trị |
|---|---|
| Tên | Pet Forum Vietnam — Diễn đàn thú cưng song ngữ Việt/Anh |
| Stack | WordPress 6.5 + wpForo 3.1.1 + Docker Compose + Nginx (prod) |
| Local | `http://localhost:8080` |
| Production | `https://petforum.vn` *(chưa deploy)* |
| Vai trò PM | Non-developer — thực thi qua Cursor Composer |

---

## 2. KIẾN TRÚC KỸ THUẬT

### Docker containers (tên chính xác)

```
pet-forum-wordpress   → WordPress + Apache + PHP 8.2  (:8080)
pet-forum-db          → MySQL 8.0
pet-forum-wpcli       → WP-CLI (dùng container này cho lệnh wp)
pet-forum-phpmyadmin  → phpMyAdmin (:8081)
pet-forum-wp-cron     → WP-Cron runner (optional, có trong compose)
```

**Lệnh WP-CLI đúng:**

```bash
docker exec pet-forum-wpcli wp <command> --path=/var/www/html --allow-root
```

### Plugin chính: `pf-unified-users` (v2.1.0)

```
wp-content/plugins/pf-unified-users/
├── pf-unified-users.php
├── includes/
│   ├── class-pf-constants.php        ← Single source of truth
│   ├── class-pf-i18n.php             ← Song ngữ VI/EN, cookie pf_lang
│   ├── class-pf-register.php         ← Split form member/vet
│   ├── class-pf-roles.php            ← WP roles + wpForo groups + section mods
│   ├── class-pf-user-meta.php        ← Meta fields + privacy + profile
│   ├── class-pf-antispam.php         ← Turnstile, disposable email
│   ├── class-pf-frontend-mod.php     ← Frontend moderation toolbar
│   ├── class-pf-mod-center.php       ← Section Mod dashboard
│   ├── class-pf-admin.php            ← WP Admin (4 sub-pages)
│   ├── class-pf-2fa.php              ← Email OTP cho admin/sub-admin
│   ├── class-pf-logger.php           ← Mod action log + CSV export
│   ├── class-pf-upload.php           ← Upload restrictions + quota
│   ├── class-pf-image-processor.php  ← Resize, WebP, strip EXIF
│   ├── class-pf-video-embed.php      ← YouTube/TikTok/Facebook embed
│   ├── class-pf-ads.php              ← AdSense + Affiliate blocks
│   ├── class-pf-engagement.php       ← Feedback form + re-engagement email
│   ├── class-pf-ai-chatbot.php       ← AI widget (Dify/n8n proxy)
│   └── class-pf-security.php         ← Brute force, enum block, audit log
├── templates/
│   ├── split-register.php
│   ├── form-member.php               ← (handover cũ gọi register-member.php)
│   ├── form-vet.php
│   ├── mod-center.php
│   └── email/, partials/, ...
└── assets/css/, assets/js/
```

**Theme Astra cũng đã chỉnh** (homepage, auth nav, nature theme CSS) — không chỉ nằm trong plugin.

### Database

| Prefix local | `wp_` |
| Prefix production | `pet70_` (cấu hình trong `docker-compose.prod.yml`) |

Bảng tùy chỉnh:

```sql
wp_pf_spam_log
wp_pf_mod_log
wp_pf_ai_log
```

### wpForo boards (multiboard)

| Board ID | Slug | Locale | Ghi chú |
|---|---|---|---|
| 2 | `english-section` | en_US | Forums EN |
| 3 | `muc-tieng-viet` | vi | Forums VI |
| 4 | `world-languages` | en_US | Đa ngôn ngữ |
| 0 | `community` | en_US | Legacy, disabled |

### WordPress roles tùy chỉnh

```
pf_global_admin, pf_dog_admin, pf_cat_admin, pf_bird_admin,
pf_market_admin, pf_verified_vet
```

User type meta (`pf_user_type`): `member` | `vet_pending` | `verified_vet`

### Forum slugs quan trọng

| Mục | Slug VI (board 3) | Slug EN (board 2) |
|---|---|---|
| Bác sĩ tư vấn | `bac-si-tu-van` | `vet-consultation` |
| Chó | `cho-canh`, `hoi-cuong-cho` | `dogs`, `dog-lovers-community` |
| Mèo | `meo-canh`, `hoi-cuong-meo` | `cats`, `cat-lovers-community` |
| Chim | `chim-canh`, `hoi-cuong-chim` | `pet-birds`, `bird-lovers-community` |
| Mua bán | `goc-mua-ban` | `marketplace` |

Constants: `PF_Constants::VET_FORUM_SLUGS`, `PF_Constants::SECTION_FORUM_SLUGS`

---

## 3. TRANG WORDPRESS

| URL | Shortcode / nội dung | Page ID | Trạng thái |
|---|---|---|---|
| `/register/` | `[pf_register]` | 96 | ✅ |
| `/mod-center/` | `[pf_mod_center]` | 108 | ✅ |
| `/gop-y/` | `[pf_feedback]` | 109 | ✅ |
| `/terms/` | HTML | 98 | ✅ |
| `/terms-for-vets/` | HTML | 106 | ✅ |
| `/subadmin-conduct/` | HTML protected | 107 | ✅ |
| `/forum/` | wpForo page | 5 | ✅ |
| `/muc-tieng-viet/bac-si-tu-van/` | wpForo VI vet section | board 3 | ✅ |
| `/english-section/vet-consultation/` | wpForo EN vet section | board 2 | ✅ |

*Legacy alias slug `bac-si-thu-y` listed in `VET_FORUM_SLUGS` for compatibility.*

---

## 4. TRẠNG THÁI PROMPT / TÍNH NĂNG

Các file `cursor-*.md` **không được lưu trong repo**. Logic đã implement trực tiếp trong code:

| Tính năng | Trạng thái |
|---|---|
| Hợp nhất pf-unified-users | ✅ Done |
| Register split + ?type= + song ngữ | ✅ Done |
| Terms modal + trang terms | ✅ Done |
| 2FA OTP + mod log | ✅ Done |
| Mod center frontend | ✅ Done |
| Upload security + video embed | ✅ Done |
| AI chatbot (n8n/Dify) | ✅ Code done — cần cấu hình runtime |
| Security hardening | ✅ Code done — prefix chưa đổi local |
| Nature theme | ⚠️ Trong theme Astra, không trong plugin |
| `pf-admin-accent.css` | ❌ Chưa có |
| Deploy VPS | ❌ Chưa |

AI knowledge/prompt nằm tại:

- `n8n/knowledge/pet-forum-ai-knowledge.md`
- `n8n/prompts/petbot-system-prompt.md`
- `n8n/pet-forum-chatbot.workflow.json`

---

## 5. CẤU HÌNH CẦN ĐIỀN

```php
// .env → docker-compose.yml → wp-config.php
PF_AI_MODE=n8n                    // hoặc 'dify'
PF_N8N_WEBHOOK=http://host.docker.internal:5678/webhook/pet-bot
PF_DIFY_API_URL=https://api.dify.ai/v1
PF_DIFY_APP_TOKEN=app-XXXXXXXX

// Production (deploy/wp-config-extra.php)
PF_HIDE_DEFAULT_LOGIN=true
PF_LOGIN_SLUG=dang-nhap-pet

// class-pf-ads.php — AdSense (placeholder)
// Shopee affiliate links trong class-pf-ads.php + class-pf-video-embed.php
```

---

## 6. LUỒNG NGƯỜI DÙNG

### Đăng ký

```
/register/ → 2 cards
  → /register/?type=member → form → terms modal → email verify
  → /register/?type=vet    → form → terms → chờ admin duyệt
```

### Đăng nhập + 2FA

```
/dang-nhap-pet (prod) hoặc /wp-login.php (local)
  → Admin / sub-admin / global mod → OTP email 6 số (10 phút)
  → Verified vet: không bắt buộc 2FA
```

### Section Mod

```
Login → FAB 👑 Mod → /mod-center/
  Tabs: Bài chờ duyệt | User vi phạm | Log | Pin/Lock
```

### AI Chatbot

```
User đăng nhập → bubble 🐾 → AJAX pf_ai_chat → n8n/Dify
```

---

## 7. BACKLOG

### 🔴 Trước deploy

- [ ] Test E2E đăng ký member/vet trên browser
- [ ] Test 2FA với Mailtrap (local)
- [ ] Turnstile keys thật
- [ ] n8n workflow Active + Anthropic API key
- [ ] Đổi DB prefix `wp_` → `pet70_` trước export prod (`scripts/deploy/rename-db-prefix.sh`)

### 🟡 Soft launch

- [ ] Homepage hero / nội dung giới thiệu
- [ ] Test upload resize + video embed
- [ ] AdSense + Shopee affiliate IDs

### 🟢 Sau launch

- [ ] `docker compose -f docker-compose.prod.yml up`
- [ ] Certbot SSL + `scripts/deploy/security-checklist.sh`
- [ ] Google Search Console

---

## 8. LỆNH THƯỜNG DÙNG

```bash
# Plugin
docker exec pet-forum-wpcli wp plugin list --path=/var/www/html --allow-root

# Test checklist
docker exec pet-forum-wpcli wp eval-file /scripts/test-handover-checklist.php --path=/var/www/html --allow-root

# Test migration cơ bản
docker exec pet-forum-wpcli wp eval-file /scripts/test-unified-users.php --path=/var/www/html --allow-root

# Tạo / đồng bộ slug forum bác sĩ
docker exec pet-forum-wpcli wp eval-file /scripts/setup-vet-forum-slug.php --path=/var/www/html --allow-root

# Cache + logs
docker exec pet-forum-wpcli wp cache flush --path=/var/www/html --allow-root
docker logs pet-forum-wordpress --tail=50
```

---

## 9. XÁC NHẬN TRẠNG THÁI (2026-06-22)

| Câu hỏi | Trả lời |
|---|---|
| Plugin tồn tại? | ✅ `pf-unified-users` v2.1.0 active |
| DB prefix? | `wp_` (local) |
| Localhost? | ✅ `:8080` HTTP 200 |
| Plugin cũ? | Đã xóa khỏi git; inactive trên WP |
| Branch pushed? | ✅ `cursor/wpforo-user-meta-fields` |

---

## 10. NGUYÊN TẮC LÀM VIỆC

- **Ưu tiên code trong `pf-unified-users`** — theme Astra đã có customizations riêng (homepage, nav)
- **`wp_usermeta` là nguồn sự thật** — không tạo bảng user mới
- **Section mods** bị chặn WP Admin → dùng `/mod-center/`
- **Song ngữ:** cookie `pf_lang`, `PF_I18n::get($key, $lang)`
- **Constants:** mọi meta key / role slug trong `PF_Constants`
- **Container names:** `pet-forum-*` (không dùng `petforum_*`)
