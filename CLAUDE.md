# Point Diesel Services

## Stack

- **Backend**: Laravel 12, PHP 8.3
- **Frontend**: React 19, TypeScript, Inertia.js v2, Tailwind CSS v4, shadcn/ui
- **Database**: MySQL 8
- **Testing**: Pest PHP (via PHPUnit)
- **Code Quality**: Laravel Pint, Larastan (PHPStan level 8), IDE Helper
- **Build**: Vite

## Commands

```bash
# Development
composer dev                  # Start all dev services (server, queue, logs, vite)
php artisan serve             # Laravel dev server only
npm run dev                   # Vite dev server only

# Testing
php artisan test              # Run all tests (Pest)
composer test                 # Alias for php artisan test

# Code Quality
composer format               # Fix PHP code style (Pint)
composer format:test          # Check PHP code style without fixing
composer analyze              # Run static analysis (Larastan level 8)
npm run lint                  # ESLint (with auto-fix)
npm run format                # Prettier (auto-fix)
npm run format:check          # Prettier (check only)

# Build
npm run build                 # Production build
npx tsc --noEmit              # TypeScript type check

# IDE Helpers
composer ide-helper           # Generate all IDE helper files

# Database
php artisan migrate           # Run migrations
php artisan migrate:fresh --seed  # Reset and seed database
```

## Project Structure

```
app/
├── Actions/          # Single-responsibility business logic classes
├── Data/             # DTOs using Spatie Laravel Data
├── Http/
│   ├── Controllers/  # Thin controllers (delegate to Actions/Services)
│   ├── Middleware/
│   └── Requests/     # Form requests for validation
├── Models/           # Eloquent models
├── Repositories/     # Data access layer
├── Services/         # Complex logic orchestrating multiple Actions
└── Providers/

resources/js/
├── components/       # Reusable React components (shadcn/ui based)
├── layouts/          # Page layouts
├── lib/              # Utility functions
├── pages/            # Inertia page components (mapped to routes)
└── types/            # TypeScript type definitions

routes/
├── web.php           # Main web routes
├── auth.php          # Authentication routes
├── settings.php      # Settings routes
└── console.php       # Artisan console commands

tests/
├── Feature/          # Feature/integration tests
└── Unit/             # Unit tests
```

## Conventions

### PHP / Laravel
- Use **Actions** for single-responsibility business logic (one public `execute` or `handle` method)
- Use **Spatie Laravel Data** for DTOs — never pass raw arrays between layers
- Controllers must be thin: validate, call Action/Service, return response
- Follow PSR-12 via Laravel Pint (default Laravel preset)
- All code must pass Larastan level 8
- Write Pest tests for all new features
- Use type hints and return types everywhere

### Frontend / React
- Use TypeScript for all files (`.tsx` / `.ts`)
- Use shadcn/ui components as base — customize via Tailwind CSS v4
- Page components go in `resources/js/pages/`
- Shared components go in `resources/js/components/`
- Use Inertia `router` for navigation, never raw `fetch` for page transitions
- Props from Laravel controllers are typed via generated TypeScript types

### Database
- Use MySQL 8 with `utf8mb4_unicode_ci` collation
- Migrations use descriptive names
- Always use foreign key constraints
- Use `$table->id()` for primary keys (unsigned big integer)

### Git
- Branch from `main`
- Conventional commits: `feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`
- CI must pass before merge (Pint, Larastan, Tests, Build)

## Environment

- `.env` — local config (never commit)
- `.env.example` — template with MySQL defaults
- Database: `point_diesel_services` (local), credentials in `.env`
