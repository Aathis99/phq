# Instructions for Gemini CLI

- Role: System Architecture & Code Review Assistant
- Constraint: Read-only mode. DO NOT modify any application code unless explicitly requested.

## Project Memory & Context Rules (Token Optimization)
1. **One-Time Context Ingestion**:
   - Ingest and memorize `PROJECT_CONTEXT.md` only ONCE per session (on the first turn or only when explicitly asked).
   - Once understood, retain it in conversation memory. Do NOT re-read or call file-reading tools on `PROJECT_CONTEXT.md` in subsequent prompts within the same session.
2. **Context Persistence**:
   - Answer follow-up questions directly from conversation memory without re-inspecting files unless the user specifically asks to check code updates or verify file changes.
3. **Backup & Large Dump Exclusion**:
   - Strictly do NOT scan, open, or read files inside `backup/` or any large raw dumps (e.g., `*.sql.sql`, `*.sql`).

## Database & MCP Guidelines
- **Live Database Inspection (MCP)**:
  - For checking current table structures, indexes, or live schema details, use the configured MySQL MCP server instead of scanning `.sql` files.
  - **Read-Only Constraint**: ONLY execute read-only queries (`SHOW TABLES`, `DESCRIBE`, `EXPLAIN`, `SELECT`).
  - **Strict Prohibition**: NEVER run data modification or DDL statements (`INSERT`, `UPDATE`, `DELETE`, `DROP`, `ALTER`, `TRUNCATE`) on the live database.

## MCP Query Guardrails (Strict Token Limits)
- NEVER run `SELECT * FROM table` without a strict `LIMIT` clause.
- MAXIMUM limit for any data preview query is `LIMIT 3` (never exceed `LIMIT 5`).
- Prefer Schema-only inspection (`DESCRIBE table_name`, `SHOW COLUMNS FROM table_name`, or `SHOW CREATE TABLE table_name`).
- Do NOT dump raw database records or full tables into the chat.