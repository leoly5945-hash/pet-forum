# Credentials cần điền trước khi deploy

> Template only — lưu giá trị thật trong password manager hoặc `.env` trên server (không commit).

## Bắt buộc

- [ ] MySQL root password (`docker-compose.prod.yml` / `.env`)
- [ ] WordPress DB user password
- [ ] Cloudflare Turnstile site key + secret — https://dash.cloudflare.com/
- [ ] n8n webhook URL (sau khi deploy n8n)
- [ ] Anthropic API key (cho n8n workflow / Claude node)

## Nếu dùng Dify thay n8n

- [ ] Dify App Token (Dashboard → App → API)
- [ ] Upload `n8n/knowledge/pet-forum-ai-knowledge.md` vào Knowledge base

## Sau khi có traffic

- [ ] Google AdSense publisher ID (`ca-pub-XXXXXXXXXX`)
- [ ] AdSense slot IDs: header, sidebar, in-content
- [ ] Shopee Affiliate links — https://affiliate.shopee.vn/
- [ ] Lazada Affiliate links (nếu dùng)

## SMTP (email production)

- [ ] SMTP host / port / user / password  
  Khuyến nghị: **AWS SES** hoặc **Brevo** (free ~300 emails/ngày)

## SSL & DNS

- [ ] Domain `petforum.vn` → VPS IP
- [ ] Certbot SSL certificate
- [ ] Cloudflare proxy (optional) + DNS records

## Kiểm tra sau deploy

```bash
DOMAIN=petforum.vn ./scripts/deploy/security-checklist.sh
```
