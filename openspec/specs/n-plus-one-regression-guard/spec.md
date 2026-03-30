## ADDED Requirements

### Requirement: Query count baseline for targeted flows
The system SHALL support defining a query count baseline for explicitly targeted flows so that regressions in query volume can be detected.

#### Scenario: Baseline is defined for a flow
- **WHEN** a maintainer selects a flow for N+1 remediation
- **THEN** the system allows defining a query count ceiling or baseline for that flow

#### Scenario: Baseline is evaluated during verification
- **WHEN** the targeted flow is executed during verification
- **THEN** the system reports whether the query count stays within the defined ceiling

### Requirement: Detect loop-driven bean retrieval patterns
The system SHALL support detecting common N+1 patterns where per-record bean retrieval is performed within loops in targeted flows.

#### Scenario: Loop-driven retrieval triggers a finding
- **WHEN** a targeted flow executes and repeatedly retrieves beans of the same module within a loop
- **THEN** the system records a finding indicating a potential N+1 pattern

### Requirement: Findings are actionable
The system SHALL present N+1 findings with enough context to enable remediation in code.

#### Scenario: Finding includes a minimal call site identifier
- **WHEN** a finding is recorded for a targeted flow
- **THEN** the finding includes a minimal identifier of the call site sufficient to locate the responsible code path

### Requirement: Guard does not expose sensitive data
The regression guard SHALL NOT log or expose sensitive data such as credentials, tokens, or full record payloads.

#### Scenario: Findings redact sensitive values
- **WHEN** a finding is recorded while the flow processes user or integration data
- **THEN** the finding contains no sensitive values and only contains safe identifiers
