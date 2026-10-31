# Contributing

## Setup

```bash
docker compose up -d --wait
composer install
composer check
```

`composer check` runs `composer validate`, phpstan (level max), php-cs-fixer (`@Symfony`, `@Symfony:risky`, applies fixes), deptrac and phpunit. All must pass. Tests need Postgres: `compose.yaml` starts one on `127.0.0.1:5435`, or set `DATABASE_URL`.

## Running without local PHP

The base `php:8.5-cli-alpine` image lacks `pdo_pgsql`. Build a local image once (the same image as `codr/eventually-doctrine`), start Postgres, then run any gate. The parent directory is mounted because `codr/eventually` and `codr/eventually-doctrine` are path repositories at `../eventually` and `../eventually-doctrine`.

```bash
printf 'FROM php:8.5-cli-alpine\nRUN apk add --no-cache postgresql-dev $PHPIZE_DEPS && docker-php-ext-install pdo_pgsql && apk del $PHPIZE_DEPS\n' | docker build -t eventually-bundle-php -
docker compose up -d --wait
docker run --rm --network host -u "$(id -u):$(id -g)" -v "$PWD/..":/work -w /work/eventually-bundle eventually-bundle-php vendor/bin/phpunit
```

Swap `vendor/bin/phpunit` for `vendor/bin/deptrac analyse` or `vendor/bin/phpstan analyse` to run the other gates. `composer install` runs on the host.

## Rules

- Keep Symfony wiring in the bundle and out of the core and Doctrine packages.
- No prose comments. Names, types and tests carry the intent. PHPDoc type tags that PHPStan needs are fine.
- English only for code, docs and commit messages.
- Commits are short lowercase `type: description`, imperative, no trailing period.
- Add a test with every behavior change. Fixtures live in `tests/Fixture`.
- A public API change follows `UPGRADE.md`: deprecate first, remove in the next major.
- `roave/backward-compatibility-check` cannot be installed next to `symfony/console` 8: run it from a separate checkout or phar before each release.
