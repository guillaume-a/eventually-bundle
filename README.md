# codr/eventually-bundle

Symfony bundle that wires [`codr/eventually`](../eventually) and [`codr/eventually-doctrine`](../eventually-doctrine): Messenger command and query buses, command metadata, optimistic concurrency retry, domain event publication and read model rebuild.

## Install

```bash
composer require codr/eventually-bundle
```

```php
// config/bundles.php
Codr\Eventually\Bundle\EventuallyBundle::class => ['all' => true],
```

The bundle prepends, with the lowest priority so the app can override it:

- `framework.messenger`: `command.bus` (metadata, retry, validation, `doctrine_transaction`) and `query.bus` (validation), `command.bus` as default.
- `doctrine`: the `uuid` DBAL type, the underscore naming strategy, the `StoredEvent` and `StoredSnapshot` entities and the `View` mapped superclass.
- `framework.property_info` with the constructor extractor, and the serializer mapping that hides the `View` bookkeeping fields.

The command bus is synchronous: no transport is configured.

## Configuration

```yaml
eventually:
    snapshot_frequency: 20      # snapshot every n events
    command_retry:
        max_attempts: 3         # attempts on an aggregate version conflict
```

## What you get

| Service | Role |
| ------- | ---- |
| `CommandBusInterface`, `QueryBusInterface` | Messenger-backed buses, `dispatchAfterCommit` supported |
| `AggregateRepositoryInterface` | `EventSourcedAggregateRepository` on the Doctrine store |
| `ActorIdResolverInterface` | Alias to override: returns the actor id stamped in event metadata (null by default) |
| `app:read-models:rebuild` | Truncates every projector's views and replays all events by position |

## Autoconfiguration

| Implement or declare | Effect |
| -------------------- | ------ |
| `EventInterface` + `#[DomainEvent]` | Registered in `eventually.event_types`. A missing attribute or a duplicate type fails the container build |
| `UpcasterInterface` | Added to the event store upcaster chain |
| `ProjectorInterface` | Replayed by the rebuild command |
| `CommandHandlerInterface` / `QueryHandlerInterface` | Messenger handler on `command.bus` / `query.bus` |
| `#[ListensTo(Event::class, 'method')]` | `kernel.event_listener` on the recorded event |

Events are services with autowiring off: scan them with your usual `resource`, do not exclude them.

## AI skills and docs

The codr packages ship memory docs and skills for AI coding agents in their `ai/` directory. Agents do not read `vendor/`, so copy them into the project:

```bash
bin/console eventually:ai:install
```

The command asks which agents to install skills for and where to put the memory docs, then stores the choice in `.eventually-ai.json` (commit it). Without a TTY it reuses that file, and skips with a note when there is none.

| Option | Effect |
| ------ | ------ |
| `--agent=claude`, `--agent=agents` | Known skill layouts: `.claude/skills`, `.agents/skills` (repeatable) |
| `--skills-dir=<dir>` | Any other skills directory, relative to the project (repeatable) |
| `--memory-dir=<dir>` | Memory docs directory, default `aidd_docs/memory/external` |
| `--reconfigure` | Ignore the stored choice and ask again |

| Shipped | Copied to |
| ------- | --------- |
| `ai/skills/**` | each skills directory |
| `ai/memory/*.md` | the memory directory |

Each copied file carries `package`, `version` and `hash` frontmatter. A rerun leaves up-to-date files alone, overwrites unmodified files after an upgrade, and reports and keeps any file you edited. The command never runs on its own. In debug, the bundle prints a hint after `cache:clear` (which `composer install` and `update` trigger through `auto-scripts`) when the files are missing or out of date.

## Quality

```bash
docker compose up -d --wait   # Postgres on 127.0.0.1:5435
composer check
```

## License

MIT, see `LICENSE`.
