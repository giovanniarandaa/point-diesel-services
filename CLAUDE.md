# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Development (runs server, queue, logs, vite concurrently)
composer dev

# Testing
php artisan test                                          # All tests
php artisan test tests/Feature/Auth/AuthenticationTest.php  # Single file
php artisan test --filter test_users_can_authenticate       # Single test

# Code Quality
composer format            # Fix PHP (Pint)
composer format:test       # Check PHP (Pint, no write)
composer analyze           # Static analysis (Larastan level 8)
npm run lint               # ESLint + auto-fix
npm run format             # Prettier + auto-fix
npm run format:check       # Prettier check only
npx tsc --noEmit           # TypeScript type check

# Code Generation
composer ide-helper                  # Regenerate IDE helper files
php artisan typescript:transform     # Generate TS types from PHP DTOs/Enums
```

## Architecture

**Laravel 12 + Inertia.js v2 + React 19 monolith.** Laravel handles routing, auth, and data; Inertia bridges the two; React renders the UI.

### Request Flow
1. Request hits Laravel route (`routes/web.php`) → Controller
2. Controller calls Action/Service → returns `Inertia::render('page-name', $props)`
3. `HandleInertiaRequests` middleware injects shared props (`auth.user`, `name`, `quote`)
4. Inertia resolves `resources/js/pages/{page-name}.tsx` and renders with props
5. Client-side navigation uses Inertia router (no full page reloads)

### Backend Layers
- **Controllers** — thin, delegate to Actions/Services, return Inertia responses
- **Actions** (`app/Actions/`) — single-responsibility business logic, one public `execute` or `handle` method
- **Services** (`app/Services/`) — orchestrate multiple Actions for complex operations
- **Data** (`app/Data/`) — DTOs via Spatie Laravel Data; never pass raw arrays between layers
- **Repositories** (`app/Repositories/`) — data access abstraction over Eloquent
- **Models** (`app/Models/`) — Eloquent models; User implements `MustVerifyEmail`

### Frontend
- **Pages** in `resources/js/pages/` — mapped to routes, receive typed props from controllers
- **Components** in `resources/js/components/` — shadcn/ui based (Radix UI primitives + Tailwind v4)
- **Path alias:** `@/*` maps to `resources/js/*` (e.g., `import { Button } from '@/components/ui/button'`)
- **Route helper:** `route('name')` via Ziggy (available in all components)
- **Shared types** in `resources/js/types/index.ts` — `User`, `SharedData`, `Auth` interfaces
- **Generated types** output to `resources/types/generated.d.ts` via `php artisan typescript:transform`

### Testing
- **Pest PHP** with `RefreshDatabase` trait on Feature tests
- Tests use **SQLite :memory:** database (configured in `phpunit.xml`), not MySQL
- Feature tests in `tests/Feature/`, unit tests in `tests/Unit/`

## Conventions

### PHP
- All code must pass **Larastan level 8** — full type hints, return types, no mixed types
- `$request->user()` returns `User|null` — always use `/** @var \App\Models\User $user */` assertions in auth-protected routes
- Laravel Pint with default Laravel preset (PSR-12)
- Spatie Laravel Data for DTOs — annotate with `#[TypeScript]` to auto-generate frontend types

### Frontend
- TypeScript strict mode — no `any` types
- Prettier: 150 char width, 4-space tabs, single quotes, organized imports, Tailwind class sorting
- Tailwind utility functions `clsx` and `cn` for conditional classes

### Git
- Conventional commits: `feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`
- CI runs: Pint, Larastan, Pest, ESLint, Prettier, TypeScript check, Vite build
