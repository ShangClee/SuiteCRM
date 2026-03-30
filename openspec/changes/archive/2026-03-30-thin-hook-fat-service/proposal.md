## Why

SuiteCRM customizations often place large amounts of procedural business logic directly in Logic Hooks, which makes behavior hard to test and hard to evolve safely. This change establishes a “Thin Hook / Fat Service” pattern so business rules can be unit-tested independently of SuiteCRM’s heavy runtime.

## What Changes

- Define a consistent pattern for extracting Logic Hook business rules into dedicated Service classes under `custom/lib/Service`.
- Provide guidance on dependency injection so services can be unit tested with PHPUnit + Mockery.
- Add a reference example showing a Logic Hook acting as a router/delegator and a Service encapsulating the business logic.
- Add developer documentation on when to use the pattern and how to structure code and tests.

## Capabilities

### New Capabilities
- `thin-hook-fat-service`: Establish rules and conventions for routing hooks to testable services (structure, DI boundaries, and testing approach).

### Modified Capabilities
- (none)

## Impact

- Affected code areas: `custom/modules/*/*LogicHook.php`, `modules/*/*LogicHook.php`, `custom/lib/Service/*`, `tests/unit/phpunit/custom/*`.
- Dependencies: relies on the existing PHPUnit + Mockery toolchain via Composer.
- Installation/migration: no DB migration required; optional incremental adoption per module/hook.
