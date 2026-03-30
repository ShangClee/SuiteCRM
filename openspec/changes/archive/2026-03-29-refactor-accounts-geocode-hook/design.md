## Context

SuiteCRM uses logic hooks heavily. `AccountsJjwg_MapsLogicHook.php` handles syncing of geocode data after an Account is saved (to Projects, Opportunities, Cases, etc.). This is tightly coupled to the DB save events and hard to single-test.

## Goals / Non-Goals

**Goals:**
- Extract the core logic from `AccountsJjwg_MapsLogicHook.php` into `AccountGeocodeService`.
- Use Dependency Injection for `jjwg_Maps` so it can be mocked in PHPUnit.
- Make the original logic hook a thin wrapper.

**Non-Goals:**
- Rewrite the `jjwg_Maps` core plugin.
- Add new geocoding capabilities.
- Refactor hooks on other modules (only focusing on Accounts).

## Decisions

- **Namespace Selection**: We will use `SuiteCRM\Custom\Service` which maps to `custom/lib/Service` based on `composer.json` PSR-4 configuration.
- **Service API**: `syncRelatedProjectGeocodes(Account $account)` will return an `int` for the number of updated rows to make asserting tests simple and declarative.

## Risks / Trade-offs

- **Risk**: SuiteCRM's `save(false)` behavior inside loops can still be slow if many related records exist. The service extracts it, but doesn't inherently fix `N+1` yet (which is out of scope for this change).
