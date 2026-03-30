## ADDED Requirements

### Requirement: Logic Hook MUST delegate business rules to a Service
For customizations adopting this pattern, the Logic Hook class MUST act as a thin router that delegates business logic to a dedicated Service class.

#### Scenario: Hook delegates to service
- **WHEN** a supported Logic Hook event is triggered for a module
- **THEN** the hook method delegates to a Service method responsible for business rules

### Requirement: Service MUST be unit-testable without installed SuiteCRM
Service classes implementing business rules MUST be unit-testable using PHPUnit + Mockery without requiring an installed SuiteCRM instance (i.e., without depending on `include/entryPoint.php` or DB availability).

#### Scenario: Service unit test runs without SuiteCRM install
- **WHEN** PHPUnit executes a unit test for a Service class
- **THEN** the test can run using mocks/fakes for dependencies and beans without requiring SuiteCRM installation checks

### Requirement: Service MUST use explicit dependencies
Service classes MUST use explicit dependencies (constructor parameters and/or method parameters) rather than relying on implicit global state.

#### Scenario: Service constructed with dependencies
- **WHEN** a Service is instantiated
- **THEN** external collaborators are provided via constructor parameters (or small adapters) and can be mocked in tests

### Requirement: Hook MUST remain minimal and side-effect bounded
Hook classes MUST keep logic minimal (routing, event filtering, and delegation) and MUST NOT embed large procedural business rule implementations.

#### Scenario: Hook performs routing only
- **WHEN** a developer adds new behavior to a hook
- **THEN** the hook change is limited to wiring and validation, and the business rule logic lives in the Service

### Requirement: Service MUST expose a test-friendly API
Services MUST expose APIs that return deterministic results useful for assertions (e.g., counts of updated related records), minimizing reliance on implicit side effects.

#### Scenario: Service returns a deterministic result
- **WHEN** a service performs updates to related records
- **THEN** it returns an integer or other deterministic value that can be asserted in unit tests
