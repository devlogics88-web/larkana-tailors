# Workspace

## Overview

pnpm workspace monorepo using TypeScript + a standalone PHP 8.4 web application.

## Stack

- **Monorepo tool**: pnpm workspaces
- **Node.js version**: 24
- **Package manager**: pnpm
- **TypeScript version**: 5.9
- **API framework**: Express 5
- **Database**: PostgreSQL + Drizzle ORM
- **Validation**: Zod (`zod/v4`), `drizzle-zod`
- **API codegen**: Orval (from OpenAPI spec)
- **Build**: esbuild (CJS bundle)

## Key Commands

- `pnpm run typecheck` — full typecheck across all packages
- `pnpm run build` — typecheck + build all packages
- `pnpm --filter @workspace/api-spec run codegen` — regenerate API hooks and Zod schemas from OpenAPI spec
- `pnpm --filter @workspace/db run push` — push DB schema changes (dev only)
- `pnpm --filter @workspace/api-server run dev` — run API server locally

See the `pnpm-workspace` skill for workspace structure, TypeScript setup, and package details.

---

## Larkana Tailors App (PHP)

**Location:** `artifacts/larkana-tailors/`  
**Runtime:** PHP 8.4 + SQLite (via PDO)  
**Dev server:** `php -S 0.0.0.0:8000 artifacts/larkana-tailors/router.php`  
**Port:** 8000  
**Workflow:** "Start application"

### App Overview

Tailor shop management web application for Larkana Tailors & Cloth House, Islamabad.

**Login:** username: `larkana`, password: `tailor` (Admin)

### Structure

```
artifacts/larkana-tailors/
├── index.php           # Front controller
├── router.php          # PHP built-in server router
├── data/larkana.db     # SQLite database (auto-created)
├── includes/           # DB, auth, functions, layout
├── views/              # Page templates
└── assets/             # CSS + JS
```

### Features

- Login (Admin + Worker roles)
- Dashboard with live stats
- New/Edit Orders with full measurement form
- Customer search (AJAX, by name/phone)
- Stock management (cloth brands, auto-deduction)
- Invoice printing (Customer Copy + Labour Copy)
- Admin reports (sales, profit/loss, monthly)
- Worker management
