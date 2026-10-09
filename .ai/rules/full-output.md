---
paths:
  - '**'
---

# Never Truncate Output

## Read everything in full before acting
When reading a file, rule, log or command output that informs a change, read all of it. Never cut it short with `head`, `tail`, `sed -n 'a,bp'`, `| head -N`, `| tail -N`, `cut`, or a `limit` on the Read tool, and never pipe it through a filter that drops lines, unless the user asked for an excerpt or the file is a generated artifact (lockfile, build output, `storage/api-docs/api-docs.json`).

- Read files with the Read tool, which returns the whole file, instead of `cat file | head`.
- Rule files under `.ai/rules/` must be read to the end. A truncated read of a rule is a skipped rule.
- If the tool says the output was too large or truncated, continue reading the rest (Read with `offset`, or split the command into several calls) until every line has been seen. Do not act on a partial read.
- Narrow with a precise search (`Grep`, a specific path) rather than by cutting the tail off a broad dump.
- A tool result that ends in a truncation notice means the read is incomplete, not finished.

## Report command output in full
When a test, lint or build command fails, show or act on the complete failure output, not just its last lines. Summarising is fine; silently discarding the part you did not read is not.
