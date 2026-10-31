# 02 - Domain

Write the aggregate, its events and its enums.

## Input

The confirmed frame from `01-frame`.

## Output

The files of the feature in the Domain source root and an aggregate unit test.

## Process

1. **Aggregate.** Write the aggregate per [aggregate.md](../references/aggregate.md), including its state capture and restore.
2. **Events.** Write one event class per fact, per [aggregate.md](../references/aggregate.md).
   - A closed status or type field earns an enum.
3. **Test.** Write the aggregate unit test: record, replay and state capture round trip.
4. **Pause.** Show the files written and wait for the user before going on.

## Test

| Case | Pass |
| --- | --- |
| The aggregate is written | the layer checker reports no violation for the Domain layer |
| An event is written | its `#[DomainEvent]` type is unique across the project |
| The unit test runs | it passes |
