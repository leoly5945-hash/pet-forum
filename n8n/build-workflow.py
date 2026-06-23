#!/usr/bin/env python3
"""Embed knowledge base and system prompt into n8n workflow JSON."""

import json
from pathlib import Path

ROOT = Path(__file__).parent
KNOWLEDGE = (ROOT / "knowledge" / "pet-forum-ai-knowledge.md").read_text(encoding="utf-8")
SYSTEM_PROMPT = (ROOT / "prompts" / "petbot-system-prompt.md").read_text(encoding="utf-8")
TEMPLATE = (ROOT / "ai-respond-claude.js").read_text(encoding="utf-8")
WORKFLOW = ROOT / "pet-forum-chatbot.workflow.json"

prefix = (
    "const KNOWLEDGE_BASE = "
    + json.dumps(KNOWLEDGE, ensure_ascii=False)
    + ";\nconst SYSTEM_PROMPT = "
    + json.dumps(SYSTEM_PROMPT, ensure_ascii=False)
    + ";\n\n"
)
code = prefix + TEMPLATE

wf = json.loads(WORKFLOW.read_text(encoding="utf-8"))
for node in wf["nodes"]:
    if node.get("name") == "AI Respond (Claude)":
        node["parameters"]["jsCode"] = code

WORKFLOW.write_text(json.dumps(wf, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
print(
    f"Built {WORKFLOW.name}: "
    f"{len(KNOWLEDGE):,} chars knowledge, {len(SYSTEM_PROMPT):,} chars system prompt."
)
