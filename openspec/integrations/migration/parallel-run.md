## Parallel-Run Comparison Procedure

Date: 20260330

Goal: compare legacy and V8 outcomes for the same workflow before cutover, where dual-run is feasible.

## Preconditions

- The workflow can be executed safely without causing duplicate side effects, or side effects are isolated.
- The integration can be run in a mode that targets legacy and V8 paths separately.

## Procedure

1) Select a workflow and define a comparable input set.

2) Run the workflow through the legacy path and capture outputs:

- Requests made (endpoints, payload shapes)
- Responses received (key fields the integration depends on)
- Side effects (records created/updated, relationships changed)

3) Run the same workflow through the V8 path and capture the same outputs.

4) Compare results:

- Record-level outcomes are equivalent for the integration’s needs.
- Permissions and error behaviors are acceptable.
- Data integrity checks pass.

5) If discrepancies exist:

- Create a parity gap record.
- Choose remediation approach:
  - adapt integration behavior
  - change V8 behavior
  - introduce a narrowly-scoped custom V8 route

6) Repeat until acceptance criteria are satisfied.

## Evidence

Store evidence references in:

- The integration inventory record
- The acceptance criteria record
