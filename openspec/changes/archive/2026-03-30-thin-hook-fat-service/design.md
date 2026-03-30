## Context

SuiteCRM Logic Hooks are widely used extension points, but they run inside SuiteCRM’s heavy runtime (SugarBean, globals, and DB-side effects). In practice, custom logic often grows into large procedural hook handlers, making behavior difficult to unit test and risky to change.

This change introduces a consistent “Thin Hook / Fat Service” pattern:
- Logic Hook class is a router that validates the event context and delegates.
- Service class encapsulates business rules and can be unit-tested with PHPUnit + Mockery.

Primary locations:
- Logic hooks: `custom/modules/<Module>/<Module>...LogicHook.php` (or `modules/...` where applicable)
- Services: `custom/lib/Service/*` (namespace `SuiteCRM\Custom\Service`)
- Tests: `tests/unit/phpunit/custom/*`

## Goals / Non-Goals

**Goals:**
- Establish a standard shape for “router” hooks that delegate to services.
- Define dependency injection conventions for services so they are testable without requiring an installed SuiteCRM runtime.
- Provide a reference example that demonstrates the pattern end-to-end (hook → service → related records) without introducing new frameworks.
- Document recommended testing boundaries (unit tests target services; hooks remain thin and are validated via lint/static checks).

**Non-Goals:**
- Rewrite SuiteCRM’s Logic Hook system or core SugarBean framework.
- Require every existing hook to be refactored immediately.
- Introduce new external runtime dependencies or a new DI container.

## Decisions

- **Service location and namespace**
  - Use `SuiteCRM\Custom\Service\*` mapped to `custom/lib/Service` via Composer autoload.
  - Rationale: keeps custom business logic isolated from core modules while still testable and reusable.
  - Alternative considered: placing services under `modules/<Module>/...`; rejected due to cross-module reuse and custom override conventions.

- **Dependency Injection (manual, constructor-based)**
  - Services accept dependencies via constructor parameters (e.g., `jjwg_Maps` instance or an adapter/facade).
  - Hooks instantiate services using the minimal set of dependencies available at runtime, and pass beans as method arguments.
  - Rationale: avoids introducing containers, keeps changes small, and improves testability.

- **Testing boundary**
  - Unit tests focus on service behavior and return values (e.g., “number of related records updated”), using Mockery for collaborators.
  - Hooks are not unit-tested in isolation because they depend on SuiteCRM runtime events; correctness is enforced by keeping them thin and by testing the service they call.
  - Rationale: avoids needing an installed SuiteCRM environment for unit tests.

- **Incremental adoption**
  - Apply the pattern to new customizations and selectively refactor high-risk/high-churn hooks.
  - Rationale: reduces migration risk and avoids large, cross-cutting refactors.

## Risks / Trade-offs

- **[Risk] Hook and service drift** → Mitigation: keep hook methods small, ensure service API is stable and documented in specs.
- **[Risk] Hidden runtime dependencies leak into services** → Mitigation: services should receive dependencies explicitly and avoid global state where possible.
- **[Trade-off] Some SugarBean behaviors are hard to isolate** → Mitigation: wrap SuiteCRM-specific calls behind small adapters or pass only the required data into the service.

