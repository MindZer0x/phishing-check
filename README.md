# Phishing-Check

Helps employees judge whether an email they received is a phishing attempt, and teaches them which warning signs to look for. See [GLOSSARY.md](GLOSSARY.md) and [docs/adr/](docs/adr/).

Built on the Laravel 13 Vue starter kit (Inertia, Vue 3, TypeScript, Tailwind, Vite) with the authentication scaffolding removed ([ADR-0002](docs/adr/0002-stateless-checks.md)).

## Requirements

PHP 8.4+, Composer, Node 22 with npm 10+.

## Local setup

```bash
composer setup   # install dependencies, create .env and the SQLite database, build assets
composer dev     # start the app on http://localhost:8000 with Vite hot reload
```

## Checks

```bash
composer ci:check   # everything CI runs: ESLint, Prettier, vue-tsc, Pint, Larastan, Pest
composer test       # PHP only: Pint, Larastan, Pest
npm run check:fix   # fix ESLint and Prettier issues
composer lint       # fix Pint issues
```

GitHub Actions runs `composer setup` and `composer ci:check` on every push and pull request.
