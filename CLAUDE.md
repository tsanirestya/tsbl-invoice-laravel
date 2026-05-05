# TSBL Invoice Laravel — Project CLAUDE.md

## Project Overview

**App:** TSBL Invoice System
**Stack:** Laravel 11, PHP 8.2.12, MySQL (XAMPP MariaDB), Bootstrap 5.3, DomPDF
**DB:** `tsbl_invoice` — pre-existing data, never run `migrate:fresh`
**URL:** `http://localhost/tsbl-invoice-laravel/public`
**Root:** `D:\XAMPP NEW\htdocs\tsbl-invoice-laravel`
**Obsidian docs:** `D:\XAMPP NEW\htdocs\tsbl-invoice-laravel\Obsidian\`

### Current Phase
- Phase 1 done: Auth, Dashboard, Models, Base Layout
- Phase 2 next: User Mgmt, Partner Mgmt, Products Module

---

## Agency Agents — Always Check Before Working

Before any feature or task, check `../agency-agents/` and recommend the right agent.

### Directories
- `../agency-agents/engineering/` — Backend, Frontend, AI, DevOps, etc.
- `../agency-agents/design/` — UI Designer, Image Prompt Engineer, etc.
- `../agency-agents/product/` — Product Manager, etc.

### Format before starting any feature

```
Agent: [agent name]
File: ../agency-agents/[category]/[file].md
Reason: [why this agent fits]
```

---

## Obsidian Update — Every Feature Build/Change

Every time a feature is built or changed, update Obsidian docs at `Obsidian/`.

### Format

```markdown
## [Feature Name]
- **Date:** YYYY-MM-DD
- **Branch:** feat/phase-N-description
- **Agent used:** [agent name]
- **Changes:** bullet list of what changed
- **Commit:** conventional commit message
```

---

## Git — Conventional Commits

```
feat:     new feature
fix:      bug fix
docs:     documentation only
chore:    setup/config/tooling
refactor: restructure without feature change
```

### Branch Strategy (trunk-based)

- `main` — always stable
- `feat/phase-N-description` — per-phase feature branches

---

## Tech Stack

| Layer      | Tech                          |
|------------|-------------------------------|
| Backend    | Laravel 11, PHP 8.2.12        |
| Frontend   | Bootstrap 5.3 + Bootstrap Icons (CDN) |
| DB         | MySQL/MariaDB via XAMPP       |
| PDF        | barryvdh/laravel-dompdf       |
| Session    | File driver (not DB)          |

### Project Structure

```
tsbl-invoice-laravel/
├── app/
│   ├── Http/Controllers/
│   ├── Http/Middleware/
│   └── Models/
├── resources/views/
├── routes/web.php
├── Obsidian/          ← project docs
└── CLAUDE.md
```

---

## Coding Rules

- All agent outputs MUST be typed with TypeScript interfaces (if TS used)
- Use structured JSON schemas for every agent input/output
- Comments only for WHY, not WHAT
- Agent files single-responsibility — one agent, one file
- Every `.env` key must have a corresponding entry in `.env.example`
- Never run `php artisan migrate:fresh` — existing data must be preserved
- All migrations use `Schema::hasTable()` guards
