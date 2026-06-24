# HANDOVER — Pet Forum Vietnam

Tài liệu bàn giao dự án để tiếp tục làm việc. Nạp file này vào Cursor Composer hoặc phiên agent mới.

**Cập nhật lần cuối:** 2026-06-24  
**Branch:** `cursor/wpforo-user-meta-fields`  
**Commit mới nhất:** `8678bdb`  
**Lịch sử gần đây:** `8678bdb` deploy templates + admin accent · `ef1cbfe` nature theme + vet forum · `fda600d` security hardening · `524fa5d` AI chatbot + upload

---

## 1. TỔNG QUAN DỰ ÁN

| Mục | Giá trị |
|---|---|
| Tên | Pet Forum Vietnam — Diễn đàn thú cưng song ngữ Việt/Anh |
| Stack | WordPress 6.5 + wpForo 3.1.1 + Docker Compose + Nginx (prod) |
| Local | `http://localhost:8080` |
| Production | `https://petforum.vn` *(chưa deploy)* |
| Vai trò PM | Non-developer — thực thi qua Cursor Composer |
| Test tự động | **35/35 passed** (`scripts/test-handover-checklist.php`) |

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

**Lệnh WP-CLI đúng** (container `pet-forum-wordpress` **không** có `wp`):

```bash
docker exec pet-forum-wpcli wp <command> --path=/var/www/html --allow-root
```

### Plugin chính: `pf-unified-users` (v2.1.0)

```
wp-content/plugins/pf-unified-users/
├── pf-unified-users.php
├── includes/
│   ├── class-pf-constants.php        ← Single source of truth + get_vet_forum_url()
│   ├── class-pf-theme.php            ← Enqueue nature theme + admin accent
│   ├── class-pf-i18n.php             ← Song ngữ VI/EN, cookie pf_lang
│   ├── class-pf-register.php         ← Split form member/vet
│   ├── class-pf-roles.php            ← WP roles + wpForo groups + section mods
│   ├── class-pf-user-meta.php        ← Meta fields + privacy + profile
│   ├── class-pf-antispam.php         ← Turnstile, disposable email
│   ├── class-pf-frontend-mod.php     ← Frontend moderation toolbar
│   ├── class-pf-mod-center.php       ← Section Mod dashboard
│   ├── class-pf-admin.php            ← WP Admin (4 sub-pages)
│   ├── class-pf-2fa.php              ← Email OTP admin/sub-admin
│   ├── class-pf-logger.php           ← Mod action log + CSV export
│   ├── class-pf-upload.php           ← Upload restrictions + quota
│   ├── class-pf-image-processor.php  ← Resize 1200px, WebP, strip EXIF
│   ├── class-pf-video-embed.php      ← YouTube/TikTok/Facebook embed
│   ├── class-pf-ads.php              ← AdSense + Affiliate blocks
│   ├── class-pf-engagement.php       ← Feedback form + re-engagement email
│   ├── class-pf-ai-chatbot.php       ← AI widget (Dify/n8n), 20 msg/giờ
│   └── class-pf-security.php         ← Brute force, enum block, audit log
├── templates/
│   ├── split-register.php
│   ├── form-member.php
│   ├── form-vet.php
│   ├── mod-center.php
│   └── email/, partials/, ...
└── assets/
    ├── css/pf-nature-theme.css       ← Site-wide green theme (plugin)
    ├── css/pf-admin-accent.css       ← WP Admin sidebar xanh rừng
    └── css/, js/ (register, chatbot, ads, ...)
```

**Theme Astra** vẫn có customizations (homepage, auth nav). Nature theme **cũng** load từ plugin qua `PF_Theme` (không chỉ theme).

### Database

| Môi trường | Prefix |
|---|---|
| Local | `wp_` |
| Production | `pet70_` (`docker-compose.prod.yml`, `.env.production.example`) |

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

**URL forum dùng board slug làm prefix** — không phải `/forum/...`:

| Mục | URL local |
|---|---|
| Bác sĩ VI | `http://localhost:8080/muc-tieng-viet/bac-si-tu-van/` |
| Bác sĩ EN | `http://localhost:8080/english-section/vet-consultation/` |

Helper: `PF_Constants::get_vet_forum_url()` — dùng trong chatbot, không hardcode URL.

### WordPress roles tùy chỉnh

```
pf_global_admin, pf_dog_admin, pf_cat_admin, pf_bird_admin,
pf_market_admin, pf_verified_vet
```

User type meta (`pf_user_type`): `member` | `vet_pending` | `verified_vet`

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
| `/community/` | wpForo (legacy board 0 disabled) | 5 | ✅ |
| `/muc-tieng-viet/bac-si-tu-van/` | wpForo VI vet | board 3 | ✅ |
| `/english-section/vet-consultation/` | wpForo EN vet | board 2 | ✅ |

---

## 4. TRẠNG THÁI TÍNH NĂNG

| Tính năng | Trạng thái |
|---|---|
| Hợp nhất pf-unified-users | ✅ Done |
| Register split + `?type=` + song ngữ | ✅ Done |
| Terms modal scroll-to-agree | ✅ Done |
| 2FA OTP (admin/mod) | ✅ Code done — cần SMTP/Mailtrap test |
| 2FA lockout 3 lần OTP sai | ❌ Chưa implement |
| Mod center + FAB | ✅ Done |
| Upload security + image processor | ✅ Code done — E2E forum chưa đủ |
| Video embed (YouTube/TikTok/FB) | ✅ Backend verified |
| AI chatbot (n8n/Dify) | ✅ Code — cần n8n Active + API key |
| Nature theme (`pf-nature-theme.css`) | ✅ Trong plugin + `PF_Theme` |
| WP Admin accent (`pf-admin-accent.css`) | ✅ Done |
| Security hardening | ✅ Code — prefix local vẫn `wp_` |
| Deploy assets | ✅ `deploy/`, `docker-compose.prod.yml` |
| Deploy VPS thực tế | ❌ Chưa |

AI knowledge/prompt:

- `n8n/knowledge/pet-forum-ai-knowledge.md`
- `n8n/prompts/petbot-system-prompt.md`
- `n8n/pet-forum-chatbot.workflow.json`

---

## 5. E2E TEST (2026-06-24)

| Test | Kết quả | Ghi chú |
|---|---|---|
| Đăng ký thành viên | ✅ một phần | UI + `?type=member` OK; submit full chưa qua browser (Turnstile) |
| Đăng ký bác sĩ | ✅ một phần | Form vet + upload bằng cấp OK |
| Đăng nhập + 2FA | ⚠️ | OTP gửi email — cần Mailtrap; chưa test lockout |
| Upload ảnh | ⚠️ | Chặn `.php` OK; resize/EXIF chưa test trên forum |
| Video embed | ✅ | YouTube URL → embed HTML |
| AI Chatbot | ⏭️ | Cần login + n8n running |
| Mod Center | ✅ | Shortcode + FAB hook OK |
| Trang Góp ý | ✅ | Form + star rating hiển thị |

---

## 6. CẤU HÌNH CẦN ĐIỀN

Xem template: `.env.production.example` và checklist: `docs/PRODUCTION-CREDENTIALS.md`

```bash
# Local (.env → docker-compose)
PF_AI_MODE=n8n
PF_N8N_WEBHOOK=http://host.docker.internal:5678/webhook/pet-bot

# Production (deploy/wp-config-extra.php + .env)
WORDPRESS_TABLE_PREFIX=pet70_
PF_HIDE_DEFAULT_LOGIN=true
PF_LOGIN_SLUG=dang-nhap-pet
CLOUDFLARE_TURNSTILE_SITE_KEY=...
CLOUDFLARE_TURNSTILE_SECRET_KEY=...
```

AdSense / Shopee affiliate: `class-pf-ads.php` hoặc WP Admin → PetForum → Ads.

---

## 7. LUỒNG NGƯỜI DÙNG

### Đăng ký

```
/register/ → 2 cards
  → /register/?type=member → form → terms modal (scroll 90%) → email verify
  → /register/?type=vet    → form + upload cert → terms → chờ admin duyệt
```

### Đăng nhập + 2FA

```
/wp-login.php (local) hoặc /dang-nhap-pet (prod)
  → Admin / section mod / global mod → OTP email 6 số (10 phút)
  → Verified vet: không bắt buộc 2FA
```

### Section Mod

```
Login → FAB 👑 Mod (góc trái dưới) → /mod-center/
  Tabs: Bài chờ duyệt | User vi phạm | Log | Pin/Lock
```

### AI Chatbot

```
User đăng nhập → bubble 🐾 → wp_ajax_pf_ai_chat → n8n/Dify
Rate limit: 20 tin/giờ, 100 tin/ngày
```

---

## 8. DEPLOY SCRIPTS

| Script | Mục đích |
|---|---|
| `scripts/deploy/rename-db-prefix.sh` | Đổi prefix trong **SQL dump** trước import |
| `scripts/deploy/rename-db-prefix-wpcli.sh` | Đổi prefix **live** qua WP-CLI (backup trước) |
| `scripts/deploy/harden-wp-config.sh` | Hardening wp-config production |
| `scripts/deploy/security-checklist.sh` | Smoke test HTTPS, wp-login block, headers |
| `scripts/setup-vet-forum-slug.php` | Đồng bộ slug `bac-si-tu-van` board 3 |
| `scripts/test-handover-checklist.php` | 35 automated checks |
| `scripts/test-unified-users.php` | Migration / plugin sanity |

Thứ tự deploy khuyến nghị:

1. Đổi prefix DB (`rename-db-prefix.sh` trên dump HOẶC `rename-db-prefix-wpcli.sh` live)
2. Copy `.env.production.example` → `.env` trên server, điền secrets
3. `docker compose -f docker-compose.prod.yml up -d`
4. `harden-wp-config.sh` + Certbot SSL
5. `DOMAIN=petforum.vn ./scripts/deploy/security-checklist.sh`

---

## 9. BACKLOG

### 🔴 Trước deploy

- [ ] Cấu hình SMTP/Mailtrap → test 2FA end-to-end
- [ ] (Optional) OTP lockout sau 3 lần sai
- [ ] Turnstile production keys
- [ ] n8n workflow **Active** + Anthropic API key
- [ ] Test upload resize trên wpForo post thật
- [ ] Đổi prefix `wp_` → `pet70_` khi export prod

### 🟡 Soft launch

- [ ] Homepage hero / nội dung giới thiệu
- [ ] AdSense + Shopee affiliate IDs
- [ ] Submit đăng ký member/vet full flow trên browser

### 🟢 Sau launch

- [ ] Deploy VPS (`cursor-deploy-vps.md` / `deploy/`)
- [ ] Google Search Console
- [ ] Monitoring uptime

---

## 10. LỆNH THƯỜNG DÙNG

```bash
# Test tổng (mục tiêu 35/35)
docker exec pet-forum-wpcli wp eval-file /scripts/test-handover-checklist.php \
  --path=/var/www/html --allow-root

# Plugin + users
docker exec pet-forum-wpcli wp plugin list --path=/var/www/html --allow-root
docker exec pet-forum-wpcli wp user list --role=pf_dog_admin --path=/var/www/html --allow-root

# Vet forum slug
docker exec pet-forum-wpcli wp eval-file /scripts/setup-vet-forum-slug.php \
  --path=/var/www/html --allow-root

# Cache + logs
docker exec pet-forum-wpcli wp cache flush --path=/var/www/html --allow-root
docker logs pet-forum-wordpress --tail=50
```

---

## 11. XÁC NHẬN TRẠNG THÁI

| Câu hỏi | Trả lời |
|---|---|
| Plugin active? | ✅ `pf-unified-users` v2.1.0 |
| DB prefix local? | `wp_` |
| Tests? | ✅ 35/35 passed |
| Branch pushed? | ✅ `cursor/wpforo-user-meta-fields` @ `8678bdb` |
| Plugin cũ? | Xóa khỏi git; inactive trên WP |

---

## 12. NGUYÊN TẮC LÀM VIỆC

- **Ưu tiên code trong `pf-unified-users`** — theme Astra có thêm homepage/nav
- **`wp_usermeta` là nguồn sự thật** — không tạo bảng user mới
- **Section mods** bị chặn WP Admin → dùng `/mod-center/`
- **Song ngữ:** cookie `pf_lang`, `PF_I18n::get($key, $lang)`
- **Constants:** meta keys / roles / forum slugs trong `PF_Constants`
- **Forum URLs:** `PF_Constants::get_vet_forum_url()` — không hardcode
- **Container names:** `pet-forum-*` (không dùng `petforum_*`)
- **WP-CLI:** luôn qua `pet-forum-wpcli`, không `pet-forum-wordpress`
