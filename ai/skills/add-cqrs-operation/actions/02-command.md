# 02 - Command

Write the command and its handler.

## Input

The confirmed frame, kind command.

## Output

A command class and its handler in the feature's Application folder, plus the aggregate method and event when they are missing.

## Process

1. **Aggregate.** Add the domain method and its event when the aggregate lacks them, per the aggregate shape of `scaffold-aggregate`.
2. **Command.** Write the command per [command.md](../references/command.md).
3. **Handler.** Write the handler per [command.md](../references/command.md), creation or mutation as the frame says.
4. **Exception.** Write a new exception only when the frame asks for one.

## Test

| Case | Pass |
| --- | --- |
| The command is written | the project's handler pairing check, when it has one, passes |
| The handler is written | it depends on `AggregateRepositoryInterface` only, never on a store port |
| The command is a mutation | an unknown id raises the feature's not-found exception |
