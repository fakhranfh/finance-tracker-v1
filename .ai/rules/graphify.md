---
paths:
  - '**'
---

# Graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

- Always use graphify to check project structure before browsing or searching files (Glob/Grep/Read), including when planning a change. Do not skip it because the task looks small.
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After every change or addition of ANY file (code, views, JS/CSS, tests, docs, config, rules), always run `graphify update .` and then `/graphify --update` (via the Skill tool) before finishing the task to keep the graph current, including docs/images that the AST-only update skips. Never skip it, even for small edits.

## If graphify is not set up

If graphify-out/ does not exist (graphify not set up in this project), ask the user whether they want to install graphify before continuing. When asking, explain:

- **Benefits:** a persistent knowledge graph of the codebase (god nodes, communities, cross-file relationships). `graphify query/path/explain` return small scoped subgraphs, so codebase questions cost far fewer tokens than raw grep or reading files, and answers are faster and more accurate. Later updates via `graphify update .` are AST-only with no API cost.
- **Cost:** the initial install/build of the graph consumes a lot of tokens, especially on a large codebase.

Do not install it without the user's confirmation.
