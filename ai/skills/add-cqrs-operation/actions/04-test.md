# 04 - Test

Write the test of the new handler.

## Input

The written command or query.

## Output

A handler test next to the feature's other tests.

## Process

1. **Write.** Write `<Operation>HandlerTest` per [handler-test.md](../references/handler-test.md).
2. **Run.** Run the new file with the project's test runner and fix until it passes.

## Test

| Case | Pass |
| --- | --- |
| A command test is written | it asserts the recorded events and the resulting view |
| An unknown id is used | the test expects the not-found exception |
| The file is run | the runner exits `0` |
