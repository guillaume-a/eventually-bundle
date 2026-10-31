# 01 - Frame

Confirm what operation is added and on which aggregate.

## Input

The user's request, or a frame handed over by the aggregate scaffold.

## Output

The confirmed frame: feature, kind (command or query), operation name, creation or mutation, item or collection, read model.

## Process

1. **Detect.** Find the feature's aggregate in the Domain source root and stop when it does not exist.
2. **Ask.** Ask what the request leaves open, one question at a time.
   - Command or query, and for a command whether it creates the aggregate or mutates it.
   - Error case: reuse an existing exception or add one.
   - Query: which view repository serves it, and whether it returns one item or a page.
3. **Confirm.** Show the frame and wait for approval.

## Test

| Case | Pass |
| --- | --- |
| The aggregate does not exist | the run stops and says which path is missing |
| The action runs | no file under the project changed |
| The frame is handed on | the user approved it |
