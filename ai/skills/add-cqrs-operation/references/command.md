# Command shapes

Model on an existing command and handler of the project.

| Part | Rule |
| --- | --- |
| Command | `final readonly class`, public promoted props, `id` first, no getters, no logic |
| Validation | constraints in the validator mapping files when the Application layer is framework-free, optional fields default `null` |
| Handler | `final readonly class` implementing `CommandHandlerInterface`, one `__invoke(<Command>)` |
| Dependency | `AggregateRepositoryInterface` only |
| Tagging | automatic onto `command.bus`, no service entry |
| Creation | `AggregateId::fromString($command->id)`, `<Aggregate>::add(...)`, `$this->repository->save($aggregate)` |
| Mutation | `$this->repository->load(<Aggregate>::class, $id)`, call the domain method, then `save` |
| Not found | catch `AggregateNotFound` and throw the feature's not-found exception |
| Atomicity | `command.bus` runs `doctrine_transaction`: events, snapshots and view writes commit together |
| Secrets | never put a password hash or a key in a command that ends up in an event |
