# AGENTS.md

Guidance for AI agents working in `codr/eventually-bundle`.

## Layout

- `src/EventuallyBundle.php`: autoconfiguration (events, upcasters, projectors, handlers, `#[ListensTo]`) and the compiler passes.
- `src/DependencyInjection`: `EventuallyExtension` (prepends Messenger buses, serializer mapping and Doctrine mappings, loads the services), `Configuration`, `EventRegistryPass`, `ListensToPass`.
- `src/Messenger`: buses, middlewares, `CommandMetadataContext`, `ActorIdResolverInterface` (the app overrides the alias to supply the actor).
- `src/Event`, `src/Command`, `src/Serializer`: event publisher, `app:read-models:rebuild`, `eventually:ai:install`, normalizers.
- `ai`: skills and memory docs for AI agents, copied into consumers by `eventually:ai:install`. English only, no app name or namespace.
- `src/Resources/config`: `services.php`, `serializer/View.yaml`.
- `tests`: own test kernel (`tests/Kernel.php`), fixture aggregate in `tests/Fixture`, functional tests on a real Postgres.

## Rules

- Depends on `codr/eventually`, `codr/eventually-doctrine`, Symfony and Doctrine. Deptrac fails on any other vendor namespace, and the bundle never imports app code.
- No Symfony or Doctrine attribute in `Codr\Eventually\Domain` and `Application`: tags go through `EventuallyBundle::build`.
- Table names belong to the Doctrine package. The app owns migrations.
- Public API is everything not `@internal`. Follow `UPGRADE.md` before removing or changing it.
- No prose comments in PHP.

## Gate

Start Postgres with `docker compose up -d --wait`, then run `composer check` before every commit.
