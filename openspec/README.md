# OpenSpec in Trae

This repo is configured to use OpenSpec (spec-driven workflow) inside Trae.

## Trae workflow

- Propose a change (creates proposal/design/tasks): run `openspec-propose`
- Explore a change/problem space: run `openspec-explore`
- Implement an approved change: run `openspec-apply-change`
- Archive a completed change: run `openspec-archive-change`

## CLI workflow

Create a new change:

```bash
openspec new change "<kebab-case-name>"
```

Check status and what is required before implementation:

```bash
openspec status --change "<kebab-case-name>"
openspec status --change "<kebab-case-name>" --json
```

Get artifact instructions (used by the Trae skills):

```bash
openspec instructions <artifact-id> --change "<kebab-case-name>" --json
```

## Repo configuration

- OpenSpec config: `openspec/config.yaml`
- Active changes live under: `openspec/changes/<change>/`
- Archived changes live under: `openspec/changes/archive/<date>-<change>/`
