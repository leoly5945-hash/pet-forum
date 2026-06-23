const item = $input.first().json;

function parseIncomingBody(rawItem) {
  if (!rawItem || typeof rawItem !== 'object') {
    return {};
  }

  let body = rawItem.body ?? rawItem.json?.body ?? rawItem;

  if (typeof body === 'string') {
    try {
      body = JSON.parse(body);
    } catch (e) {
      return {};
    }
  }

  // n8n webhook wrapper: { headers, params, query, body: { message, metadata } }
  if (body && typeof body === 'object' && body.body && !body.message) {
    const inner = body.body;
    body = typeof inner === 'string' ? JSON.parse(inner) : inner;
  }

  return body && typeof body === 'object' ? body : {};
}

function parseMetadata(raw) {
  if (!raw) {
    return {};
  }
  if (typeof raw === 'string') {
    try {
      return JSON.parse(raw);
    } catch (e) {
      return {};
    }
  }
  return typeof raw === 'object' ? raw : {};
}

const body = parseIncomingBody(item);

function loadKnowledge() {
  if (typeof KNOWLEDGE_BASE === 'string' && KNOWLEDGE_BASE.length > 100) {
    return KNOWLEDGE_BASE;
  }
  try {
    const fs = require('fs');
    const paths = [
      '/data/knowledge/pet-forum-ai-knowledge.md',
      '/knowledge/pet-forum-ai-knowledge.md',
    ];
    for (const p of paths) {
      if (fs.existsSync(p)) {
        return fs.readFileSync(p, 'utf8');
      }
    }
  } catch (e) {
    // n8n sandbox may block fs — use embedded KNOWLEDGE_BASE from build script.
  }
  return '';
}

function loadSystemPrompt() {
  if (typeof SYSTEM_PROMPT === 'string' && SYSTEM_PROMPT.length > 100) {
    return SYSTEM_PROMPT;
  }
  try {
    const fs = require('fs');
    const paths = [
      '/data/prompts/petbot-system-prompt.md',
      '/prompts/petbot-system-prompt.md',
    ];
    for (const p of paths) {
      if (fs.existsSync(p)) {
        return fs.readFileSync(p, 'utf8');
      }
    }
  } catch (e) {
    // n8n sandbox may block fs — use embedded SYSTEM_PROMPT from build script.
  }
  return '';
}

function fillTemplate(template, vars) {
  return String(template).replace(/\{\{(\w+)\}\}/g, (_, key) => {
    if (Object.prototype.hasOwnProperty.call(vars, key)) {
      return String(vars[key]);
    }
    return `{{${key}}}`;
  });
}

function detectMessageLang(text, fallback) {
  if (/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/i.test(text)) {
    return 'vi';
  }
  if (/[a-z]/i.test(text)) {
    return 'en';
  }
  return fallback === 'en' ? 'en' : 'vi';
}

const message = String(body.message || '').trim();
const meta = parseMetadata(body.metadata);
const preferredLang = meta.preferred_lang === 'en' ? 'en' : 'vi';
const responseLang = detectMessageLang(message, preferredLang);
const convId = body.conversation_id || '';
const isNewConversation = !convId;
const username = meta.username || 'User';
const role = meta.role || 'subscriber';
const isVet = !!meta.is_vet;
const forumUrl = meta.forum_url || 'https://petforum.vn';
const vetSectionUrl = meta.vet_section_url || `${forumUrl}/forum/bac-si-tu-van`;

if (!message) {
  return [{
    json: {
      answer: responseLang === 'en' ? 'Please enter a question.' : 'Vui lòng nhập câu hỏi.',
      conversation_id: convId || `pf_${meta.user_id || 0}`,
      message_id: `msg_${Date.now()}`,
    },
  }];
}

const templateVars = {
  username,
  preferred_lang: preferredLang,
  role,
  is_vet: isVet ? 'true' : 'false',
  role_label_vi: meta.role_label_vi || 'Thành viên',
  role_label_en: meta.role_label_en || 'Member',
  forum_url: forumUrl,
  vet_section_url: vetSectionUrl,
};

const systemCore = fillTemplate(loadSystemPrompt(), templateVars);
const knowledge = loadKnowledge();
const systemPrompt = [
  systemCore,
  '',
  '--- RUNTIME CONTEXT (from WordPress) ---',
  `User message language detected: ${responseLang === 'en' ? 'English' : 'Vietnamese'} — respond in THIS language.`,
  `Preferred profile language: ${preferredLang}`,
  `New conversation: ${isNewConversation ? 'yes (greet by name if appropriate)' : 'no (do not repeat greeting)'}`,
  `Verified veterinarian: ${isVet ? 'yes' : 'no'}`,
  '',
  '--- OFFICIAL KNOWLEDGE BASE (cite for first aid & forum rules; do not invent medical facts) ---',
  knowledge,
].join('\n');

function getAnthropicConfig() {
  const defaults = { apiKey: '', model: 'claude-sonnet-4-6', source: 'none' };

  // n8n UI Variables (Settings → Variables) — works when $env is blocked
  try {
    if (typeof $vars !== 'undefined') {
      const key = $vars.ANTHROPIC_API_KEY || $vars.anthropic_api_key || '';
      const model = $vars.ANTHROPIC_MODEL || $vars.anthropic_model || '';
      if (key) {
        return { apiKey: String(key), model: model || defaults.model, source: 'vars' };
      }
    }
  } catch (e) {
    // ignore
  }

  // OS env — requires N8N_BLOCK_ENV_ACCESS_IN_NODE=false in ~/.n8n/.env
  try {
    const key = $env.ANTHROPIC_API_KEY || '';
    const model = $env.ANTHROPIC_MODEL || '';
    if (key) {
      return { apiKey: String(key), model: model || defaults.model, source: 'env' };
    }
  } catch (e) {
    return {
      apiKey: '',
      model: defaults.model,
      source: 'blocked',
      blockError: String(e?.message || e),
    };
  }

  return defaults;
}

const { apiKey, model, source, blockError } = getAnthropicConfig();

let answer = '';

if (!apiKey) {
  if (source === 'blocked') {
    answer = responseLang === 'en'
      ? '⚠️ n8n blocked $env access. Fix: add N8N_BLOCK_ENV_ACCESS_IN_NODE=false to ~/.n8n/.env, restart n8n, OR set ANTHROPIC_API_KEY in n8n Settings → Variables.'
      : '⚠️ n8n chặn truy cập $env. Sửa: thêm N8N_BLOCK_ENV_ACCESS_IN_NODE=false vào ~/.n8n/.env, khởi động lại n8n, HOẶC set ANTHROPIC_API_KEY trong Settings → Variables.';
  } else {
    answer = responseLang === 'en'
      ? `[Demo] Set ANTHROPIC_API_KEY in ~/.n8n/.env or n8n Settings → Variables. You asked: "${message}".`
      : `[Demo] Cấu hình ANTHROPIC_API_KEY trong ~/.n8n/.env hoặc Settings → Variables. Bạn hỏi: "${message}".`;
  }
} else {
  const resp = await fetch('https://api.anthropic.com/v1/messages', {
    method: 'POST',
    headers: {
      'x-api-key': apiKey,
      'anthropic-version': '2023-06-01',
      'content-type': 'application/json',
    },
    body: JSON.stringify({
      model,
      max_tokens: 1500,
      system: systemPrompt,
      messages: [{ role: 'user', content: message }],
    }),
  });

  const data = await resp.json();

  if (!resp.ok) {
    const errMsg = data?.error?.message || `Anthropic HTTP ${resp.status}`;
    if (resp.status === 401 || /api[_ ]?key|authentication|invalid/i.test(errMsg)) {
      answer = responseLang === 'en'
        ? `⚠️ Anthropic API key invalid or expired (HTTP ${resp.status}). Create a new key at console.anthropic.com → Settings → API Keys, then set ANTHROPIC_API_KEY in n8n Variables and restart n8n.`
        : `⚠️ API key Anthropic không hợp lệ hoặc đã hết hạn (HTTP ${resp.status}). Tạo key mới tại console.anthropic.com → Settings → API Keys, rồi cập nhật ANTHROPIC_API_KEY trong n8n Variables và khởi động lại n8n.`;
    } else if (resp.status === 404 || /not_found_error|model:/i.test(errMsg)) {
      answer = responseLang === 'en'
        ? `⚠️ Model "${model}" not found. Set ANTHROPIC_MODEL=claude-sonnet-4-6 in n8n Variables (or claude-haiku-4-5 for cheaper).`
        : `⚠️ Model "${model}" không tồn tại. Đặt ANTHROPIC_MODEL=claude-sonnet-4-6 trong n8n Variables (hoặc claude-haiku-4-5 rẻ hơn).`;
    } else if (resp.status === 402 || /credit|balance|billing/i.test(errMsg)) {
      answer = responseLang === 'en'
        ? `⚠️ Anthropic account has no API credits. Add billing at console.anthropic.com → Plans & Billing.`
        : `⚠️ Tài khoản Anthropic chưa có credit API. Nạp credit tại console.anthropic.com → Plans & Billing.`;
    } else {
      throw new Error(errMsg);
    }
  } else {
    answer = data.content?.[0]?.text
      || (responseLang === 'en' ? 'No response from Claude.' : 'Không nhận được phản hồi từ Claude.');
  }
}

return [{
  json: {
    answer,
    conversation_id: convId || `pf_${meta.user_id || 0}_${Date.now()}`,
    message_id: `msg_${Date.now()}`,
  },
}];
