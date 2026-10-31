# 01 - Frame

Collect the facts that shape the aggregate before any file is written.

## Input

The user's request, or a written spec.

## Output

The confirmed frame: feature name, aggregate type string, facts (events), operations (commands and queries), view fields.

## Process

1. **Read.** Read an existing aggregate of the project and the installed `eventually` memory files.
2. **Ask.** Ask what the request leaves open, one question at a time.
   - Facts: which events the aggregate records, and which of them create it.
   - View: the fields to project and whether a status or type field earns an enum.
   - Sensitive data: anything that must never enter an event.
3. **Confirm.** Show the frame and wait for approval.

## Test

| Case | Pass |
| --- | --- |
| The action runs | no file under the project changed |
| A fact is not stated | the user was asked, never defaulted |
| The frame is handed on | the user approved it |
