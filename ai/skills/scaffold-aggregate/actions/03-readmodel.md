# 03 - Read model

Write the view, its mapping and its projector.

## Input

The confirmed frame and the written domain.

## Output

The view, its Doctrine mapping, the view repository interface and adapter, the projector, and a migration for the view table.

## Process

1. **View.** Write a mutable `final` class extending `View` with the frame's fields, per [aggregate.md](../references/aggregate.md).
2. **Mapping.** Map it with an XML file beside the project's other view mappings. No ORM attribute in the Application layer.
3. **Repository.** Write the repository interface (`find`, `paginate`, `save`, `clear`) and an adapter extending `AbstractDoctrineViewRepository`.
4. **Projector.** Write the projector implementing `ProjectorInterface`: one `#[ListensTo]` method per event, `find`, mutate, `save`. `reset()` calls `clear()`.
5. **Migration.** Generate the migration for the new table and review it.
6. **Operations.** Run `add-cqrs-operation` once per command and per query of the frame.

## Test

| Case | Pass |
| --- | --- |
| The projector is written | replaying the events through the rebuild command gives the same rows as the live writes |
| The view is written | it carries no field the events do not hold |
| The schema is checked | the schema diff is empty after the migration |
