# Aggregate and view shape

Model on an existing aggregate of the project.

| Part | Rule |
| --- | --- |
| Class | `final class <Aggregate> extends AggregateRoot` |
| Type | `#[Aggregate('<snake_case>')]` on the class, add `revision: n` when `captureState()` changes. `id()` returns the id |
| Creation | static `add(...)` builds `new self()` and records the "was added" event |
| Behaviour | one public method per fact, each only calls `recordEvent(new <Aggregate>Was<X>(...))` |
| `apply()` | protected, a `match(true)` over `instanceof` that sets the private state |
| `captureState()`, `restoreState()` | required, return and rebuild the state as an array |
| Getters | `assert()` the nullable field is set, then return it |
| Event class | `final readonly`, implements `EventInterface`, carries `#[DomainEvent('<snake_case>.<fact>')]`, promoted private props, accessor methods |
| Event name | `<Aggregate>Was<X>` or `<Aggregate>Were<X>` |
| Event payload | never a secret, a credential or a key |
| Registration | none, events are tagged by the bundle |
| Snapshots | none to wire, the repository snapshots at `eventually.snapshot_frequency` |
| View | mutable `final` class extending `View`, redeclares `id` first in its constructor, no constructor docblock |
| Projection | the projector mutates the view with named methods that `touch` `updatedAt`, rule shared with the aggregate lives in one domain value object |
