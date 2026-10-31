# Query shapes

Model on an existing query and handler of the project.

| Part | Rule |
| --- | --- |
| Item query | `final readonly class` with a public `string $id` |
| Collection query | `final readonly class` carrying a `PageRequest` or the page and limit |
| Handler | `final readonly class` implementing `QueryHandlerInterface`, one `__invoke(<Query>)` |
| Item handler | `$viewRepository->find($id)`, throw the feature's not-found exception on `null` |
| Collection handler | `$viewRepository->paginate($pageRequest)`, return the `Page` |
| View | the feature's `View` subclass, never an aggregate and never loaded from the event stream |
| Tagging | automatic onto `query.bus` |
| Consistency | views are written in the command transaction, so a read after a command sees it |
