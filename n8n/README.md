# Pet Forum — n8n AI Chatbot (Claude)

Workflow kết nối widget chat WordPress với **Anthropic Claude** qua n8n.

## Claude hay OpenAI?

| | **Claude (khuyên dùng cho bạn)** | OpenAI |
|---|----------------------------------|--------|
| Tiếng Việt | Rất tốt | Tốt |
| Sơ cứu / hướng dẫn dài | Rõ ràng, an toàn | Ổn |
| Bạn đã trả phí | Dùng API Anthropic | Cần tài khoản OpenAI riêng |

> **Lưu ý:** Gói **Claude Pro** trên claude.ai và **Anthropic API** là hai kênh billing khác nhau. Cần API key tại [console.anthropic.com](https://console.anthropic.com) (nạp credit API).

## Import

1. n8n → **Workflows** → **Import from File** → `pet-forum-chatbot.workflow.json`
2. **Activate** workflow

Webhook URL:

```text
# Local
http://localhost:5678/webhook/pet-bot

# Production
https://YOUR-N8N-DOMAIN/webhook/pet-bot
```

## Biến môi trường n8n

n8n **2.x chặn `$env` trong Code node** mặc định. Chọn **một** cách:

### Cách A — File `~/.n8n/.env` (khuyên dùng)

```bash
mkdir -p ~/.n8n
nano ~/.n8n/.env
```

```env
N8N_BLOCK_ENV_ACCESS_IN_NODE=false
ANTHROPIC_API_KEY=sk-ant-api03-...
ANTHROPIC_MODEL=claude-sonnet-4-6
```

**Tắt hẳn n8n → mở lại** (hoặc `npx n8n start` từ terminal).

### Cách B — n8n Settings → Variables

Thêm biến `ANTHROPIC_API_KEY` (và tùy chọn `ANTHROPIC_MODEL`). Code node đọc qua `$vars` — không cần unblock `$env`.

| Biến | Bắt buộc | Mặc định |
|------|----------|----------|
| `ANTHROPIC_API_KEY` | Có | — (`sk-ant-api03-...`) |
| `ANTHROPIC_MODEL` | Không | `claude-sonnet-4-6` |

Model rẻ hơn: `claude-haiku-4-5`

### API key bị lỗi / hết hạn?

1. Vào [console.anthropic.com](https://console.anthropic.com) → **Settings → API Keys**
2. **Revoke** key cũ (nếu đã lộ hoặc nghi ngờ lỗi)
3. **Create Key** → copy key mới (`sk-ant-api03-...`)
4. n8n → **Settings → Variables** → sửa `ANTHROPIC_API_KEY`
5. **Khởi động lại n8n** (env chỉ load lúc start)
6. Test key trực tiếp:

```bash
curl -s https://api.anthropic.com/v1/messages \
  -H "x-api-key: sk-ant-YOUR-NEW-KEY" \
  -H "anthropic-version: 2023-06-01" \
  -H "content-type: application/json" \
  -d '{"model":"claude-sonnet-4-6","max_tokens":50,"messages":[{"role":"user","content":"hi"}]}'
```

- Trả về JSON có `content` → key OK
- `"type":"authentication_error"` → key sai/hết hạn
- `"credit"` / `402` → cần nạp credit API (khác gói Claude Pro trên web)

> **Không** dán key vào Code node — chỉ dùng biến `ANTHROPIC_API_KEY` trong n8n Variables.

Model rẻ hơn (FAQ đơn giản): `claude-3-5-haiku-20241022`

## WordPress `.env`

```env
PF_AI_MODE=n8n
PF_N8N_WEBHOOK=http://host.docker.internal:5678/webhook/pet-bot
```

```bash
docker compose up -d wordpress
```

## Test

```bash
curl -s -X POST http://localhost:5678/webhook/pet-bot \
  -H 'Content-Type: application/json' \
  -d '{
    "message": "Chó bị ngộ độc socola phải làm gì?",
    "metadata": {
      "user_id": 1,
      "username": "Test",
      "preferred_lang": "vi",
      "role": "subscriber",
      "is_vet": false
    }
  }' | jq -r .answer
```

## Luồng

```text
Webhook POST /pet-bot
  → Code "AI Respond (Claude)" — Anthropic Messages API
  → Respond to Webhook → { answer, conversation_id, message_id }
```

Source code node (để sửa prompt): `n8n/ai-respond-claude.js`

## System prompt & knowledge base

| File | Mục đích |
|------|----------|
| `n8n/prompts/petbot-system-prompt.md` | Persona PetBot, guardrails, cấu trúc trả lời (paste từ Dify) |
| `n8n/knowledge/pet-forum-ai-knowledge.md` | Sơ cứu, nội quy, FAQ |

Sau khi sửa prompt hoặc knowledge:

```bash
python3 n8n/build-workflow.py
```

Re-import `pet-forum-chatbot.workflow.json` vào n8n.

**Cập nhật mà không re-import** — mount volumes khi chạy n8n:

```yaml
volumes:
  - ./n8n/knowledge:/data/knowledge:ro
  - ./n8n/prompts:/data/prompts:ro
```
