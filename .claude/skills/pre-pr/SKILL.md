---
name: pre-pr
description: "Run all CI checks locally before creating a PR: Pint, Larastan, ESLint, Prettier, TypeScript and PHPUnit tests."
user_invocable: true
---

# Pre-PR Validation

Run all quality checks locally to ensure CI will pass before pushing or creating a PR.

## Steps

Execute the following checks **sequentially**, stopping at the first failure:

### 1. PHP Formatting (Pint)

```bash
vendor/bin/pint --dirty --format agent
```

If Pint made changes, stage them and inform the user.

### 2. Frontend Formatting (Prettier)

```bash
npm run format
```

If Prettier made changes, stage them and inform the user.

### 3. Frontend Lint (ESLint)

```bash
npm run lint
```

If ESLint reports errors that `--fix` couldn't resolve, show them and stop.

### 4. TypeScript Check

```bash
npx tsc --noEmit
```

If there are type errors, show them and stop.

### 5. Static Analysis (Larastan)

```bash
vendor/bin/phpstan analyse --memory-limit=512M
```

If there are errors, show them and stop.

### 6. PHP Tests (PHPUnit)

```bash
php artisan test --compact
```

If tests fail, show them and stop.

## On Success

When all checks pass, output a summary:

```
All checks passed:
  - Pint
  - Prettier
  - ESLint
  - TypeScript
  - Larastan
  - PHPUnit
Ready to push/PR.
```

## On Failure

Stop at the first failing check. Show the error output clearly and suggest a fix if possible. Do NOT continue to subsequent checks — fix the failure first.
