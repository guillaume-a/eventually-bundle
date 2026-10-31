# codr/eventually-bundle

Symfony bundle that wires `codr/eventually` and `codr/eventually-doctrine` (`Codr\Eventually\Bundle`): Messenger buses, middleware, event publication, autoconfiguration and the rebuild command.

## Configuration

```yaml
eventually:
    snapshot_frequency: 20
    command_retry:
        max_attempts: 3
```

The bundle prepends the Messenger buses, the Doctrine mappings, the property-info extractor and the serializer mapping at the lowest priority, so the application can override them.

## Buses

- `command.bus` and `query.bus`, both synchronous: no transport, no broker.
- `command.bus` middleware order: `CommandMetadataMiddleware`, `RetryOnConcurrencyMiddleware`, `validation`, `doctrine_transaction`. Retry wraps the transaction, so each attempt is a fresh transaction.
- Handlers are tagged by interface (`CommandHandlerInterface`, `QueryHandlerInterface`) onto their bus: no manual service entry.
- Validation runs on the bus. Express constraints for framework-free commands in validator mapping files, not attributes.

## Transactions and retry

- `doctrine_transaction` commits the event rows, the snapshots and the view writes together. A failing command or listener rolls all of them back.
- `RetryOnConcurrencyMiddleware` retries only a `ConcurrencyException` (or a raw `uniq_aggregate_version` violation, which it converts), up to `eventually.command_retry.max_attempts` times with no sleep. Each attempt runs on a clone of the middleware stack, and a closed entity manager is reset first. Nested dispatches are not retried. Once attempts are exhausted the `ConcurrencyException` surfaces: map it to your conflict response.
- `dispatchAfterCommit` uses Messenger's `DispatchAfterCurrentBusStamp`: the command runs after the outer command committed and is dropped when it fails. A failure in it surfaces after the outer command is already committed.

## Metadata

- `CommandMetadataMiddleware` fills `CommandMetadataContext`, which `DoctrineEventStore` reads through `MetadataProviderInterface`.
- `actorId` comes from `ActorIdResolverInterface`. The default `NullActorIdResolver` returns null: override the alias to return the authenticated actor's id, never a username.
- `correlationId` is the `X-Correlation-Id` header of the main request when valid, else the root command id. `causationId` is the id of the command being handled.

## Autoconfiguration

| Implement or declare | Effect |
| -------------------- | ------ |
| `EventInterface` + `#[DomainEvent]` | Tagged, registered in `eventually.event_types`, autowiring off. A missing attribute or a duplicate type fails the container build |
| `UpcasterInterface` | Added to the event store upcaster chain |
| `ProjectorInterface` | Replayed by the rebuild command |
| `CommandHandlerInterface`, `QueryHandlerInterface` | Handler on `command.bus`, `query.bus` |
| `#[ListensTo(Event::class, 'method')]` | `kernel.event_listener` on the `RecordedEvent`. A missing public method fails the container build |

- Events are services with autowiring off: scan them with the usual resource, never exclude them, because the registry pass discovers them through their tag.
- Listeners run inside the command transaction. They must be idempotent.

## Rebuild

- `app:read-models:rebuild` resets every projector and replays all events by `position` in one transaction.
- Snapshots are not involved: a payload or state revision bump is handled by upcasters and `#[Aggregate]` revisions.

## Wiring a consumer

- Register `EventuallyBundle` in `config/bundles.php`.
- `EventPayloadSerializerInterface` is aliased to the bundle's Symfony serializer adapter: override the alias to replace it.
- Keep the Application layer framework-free and wire its scalar and environment arguments in the service configuration, not with `#[Autowire]`.
- Enforce with a layer checker that nothing but the composition root imports `Codr\Eventually\Bundle`.
