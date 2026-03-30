# Thin Hook / Fat Service refactor (what, why, how)

This document describes the Thin Hook / Fat Service work applied in this repo: what changed, why it was done, and how it is implemented and tested.

## Why

SuiteCRM Logic Hooks execute inside SuiteCRM’s runtime (SugarBean, globals, DB side effects). When business rules are implemented directly inside hook handlers, they become:
- hard to unit test without an installed SuiteCRM instance
- risky to change (tight coupling to event context and globals)
- difficult to reuse across modules

The goal of this refactor is to move business rules into dedicated Service classes with explicit dependencies, leaving hook classes as thin routers/delegators.

## What changed

### 1) Shared pattern documentation

- Pattern doc: `docs/patterns/thin-hook-fat-service.md`
- This refactor doc: `docs/refactors/thin-hook-fat-service.md`

### 2) jjwg_Maps geocoding hooks converted to thin delegators

Modules with `*Jjwg_MapsLogicHook.php` in this repo were refactored to delegate to a shared service:

- `modules/Contacts/ContactsJjwg_MapsLogicHook.php`
- `modules/Leads/LeadsJjwg_MapsLogicHook.php`
- `modules/Prospects/ProspectsJjwg_MapsLogicHook.php`
- `modules/Meetings/MeetingsJjwg_MapsLogicHook.php`
- `modules/Cases/CasesJjwg_MapsLogicHook.php`
- `modules/Opportunities/OpportunitiesJjwg_MapsLogicHook.php`
- `modules/Project/ProjectJjwg_MapsLogicHook.php`

Shared service:
- `custom/lib/Service/JjwgMapsGeocodeService.php` (`SuiteCRM\Custom\Service\JjwgMapsGeocodeService`)

Accounts is already using a specialized service (kept as-is):
- `modules/Accounts/AccountsJjwg_MapsLogicHook.php` → `custom/lib/Service/AccountGeocodeService.php`

### 3) InsideView hook converted to thin delegator

Hook:
- `modules/Connectors/connectors/sources/ext/rest/insideview/InsideViewLogicHook.php`

Service:
- `custom/lib/Service/InsideViewFrameService.php` (`SuiteCRM\Custom\Service\InsideViewFrameService`)

The hook now delegates URL building and HTML rendering to the service.

### 4) AOD_Index hooks converted to thin delegators

Hook:
- `modules/AOD_Index/AOD_LogicHooks.php`

Service:
- `custom/lib/Service/AodIndexSyncService.php` (`SuiteCRM\Custom\Service\AodIndexSyncService`)

The hook now delegates indexing behavior (index/remove/index) to the service.

### 5) Pure unit-test harness added (no SuiteCRM install required)

PHPUnit config for pure unit tests:
- `tests/phpunit.unit.xml` (bootstraps `vendor/autoload.php`, does not load SuiteCRM entryPoint)

Pure unit tests added:
- `tests/unit/pure/Custom/Service/AccountGeocodeServiceTest.php`
- `tests/unit/pure/Custom/Service/JjwgMapsGeocodeServiceTest.php`
- `tests/unit/pure/Custom/Service/AodIndexSyncServiceTest.php`

## How it works

### Service boundary

Services live under:
- `custom/lib/Service/*`
- Namespace prefix: `SuiteCRM\Custom\` (autoloaded by Composer via `custom/lib`)

Services:
- accept dependencies explicitly (constructor arguments), enabling Mockery-based tests
- provide methods that hooks call with the current bean / event context
- return deterministic values when useful (e.g., counts of updated related records)

### Hook boundary

Hook classes are kept small:
- instantiate the service
- validate minimal prerequisites (if any)
- delegate to a service method

This keeps runtime-only concerns in hooks, and business rules in services.

### Verification approach

Lint changed PHP files:

```bash
php -l path/to/file.php
```

Run pure unit tests:

```bash
php vendor/bin/phpunit -c tests/phpunit.unit.xml
```

Notes:
- Vendor libraries may emit PHP 8.4 deprecation output; the test suite still validates behavior.
- Full SuiteCRM integration tests still require an installed SuiteCRM instance (DB + config), so they are intentionally not part of the “pure unit” suite.

## Scope note: “all modules”

This work refactors modules where hook implementations exist in the repo as dedicated hook classes (not every folder under `modules/`). Many SuiteCRM hooks are registered via generated extension files or are not present as standalone hook classes in this checkout. For those, the same pattern applies once the hook entry point file is identified.

