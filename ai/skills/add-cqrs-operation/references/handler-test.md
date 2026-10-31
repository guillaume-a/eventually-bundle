# Handler test shape

Model on an existing handler test of the project.

| Part | Rule |
| --- | --- |
| Level | a unit test with `Codr\Eventually\Testing\InMemoryAggregateRepository` when the handler only touches the aggregate, a kernel test through the buses when views or the store matter |
| Ids | a fresh UUID per test |
| Setup | run the creation command first for a mutation |
| Event assertion | assert the count and the event class or type of the recorded events |
| Read assertion | ask the query bus, assert the projected field |
| Error case | `expectException` with the feature's not-found exception for an unknown id |
| Validation case | `ValidationFailedException` for an invalid command, kernel test only |
| Cleanup | none when the project wraps each test in a rolled-back transaction |
