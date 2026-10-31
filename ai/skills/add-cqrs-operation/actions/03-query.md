# 03 - Query

Write the query and its handler.

## Input

The confirmed frame, kind query.

## Output

A query class and its handler in the feature's Application folder, plus the view field the query needs when it is missing.

## Process

1. **Query.** Write the query per [query.md](../references/query.md), item or collection as the frame says.
2. **Handler.** Write the handler per [query.md](../references/query.md), reading through the view repository.
3. **View.** Extend the feature's view and its mapping only when a needed field is absent. A new field needs a projector change and a read model rebuild.

## Test

| Case | Pass |
| --- | --- |
| The query is written | the project's handler pairing check, when it has one, passes |
| The handler returns data | it returns the view, never an aggregate |
