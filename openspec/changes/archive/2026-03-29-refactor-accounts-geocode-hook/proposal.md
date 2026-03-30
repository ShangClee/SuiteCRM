## Why

SuiteCRM customizations traditionally rely on "Fat Hooks"—putting complex, untestable procedural logic directly into event listener classes like `before_save` logic hooks. 

The `AccountsJjwg_MapsLogicHook.php` is a classic example of this anti-pattern. Operations like syncing map data to related projects or opportunities are buried inside an untestable `after_save` hook. Moving this to a "Fat Service" architecture allows us to unit-test the business logic rapidly without needing a full CRM database setup, improves code reusability, and cleans up the global logic tier.

## What Changes

1. **New Service Class**: A dedicated `AccountGeocodeService` will be created in `custom/lib/Service/` to handle all Geocode-related relationships and updates for Accounts.
2. **Hook Refactor**: The existing `AccountsJjwg_MapsLogicHook.php` will be stripped of its procedural logic and rewired to be a simple router (a "Thin Hook") that delegates to the new Service class.
3. **Unit Tests**: Full unit tests will be introduced for `AccountGeocodeService` bridging the `tests/unit/phpunit/` infrastructure, ensuring that the Geocoding synchronization logic correctly handles edge cases.

## Capabilities

### New Capabilities
- `account-geocode-service`: A testable, dedicated PHP service for handling Accounts map geocoding logic and relationship synchronization.

### Modified Capabilities

## Impact

- `modules/Accounts/AccountsJjwg_MapsLogicHook.php`: Logic will be extracted.
- `custom/lib/Service/AccountGeocodeService.php` (new): Central location for account-related map logic.
- `tests/unit/phpunit/custom/lib/Service/AccountGeocodeServiceTest.php` (new): Unit tests for the new service.
