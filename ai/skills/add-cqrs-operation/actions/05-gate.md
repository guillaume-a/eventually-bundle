# 05 - Gate

Prove the operation passes the project's quality gate.

## Output

A green gate, or the failures left for the user.

## Process

1. **Static.** Run the before-commit commands listed in the project memory (`coding-assertions.md`).
2. **Tests.** Run the project's test suite.
3. **Fix.** Fix each failure and rerun.

## Test

| Case | Pass |
| --- | --- |
| The gate runs | every command exits `0` |
| A command fails | the failure is fixed or reported, never skipped |
