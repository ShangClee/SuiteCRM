# Thin Hook / Fat Service (SuiteCRM)

SuiteCRM Logic Hooks are powerful extension points, but they run inside SuiteCRM’s heavy runtime and are difficult to unit test. This pattern keeps Logic Hooks small and moves business rules into dedicated Service classes that can be tested with PHPUnit + Mockery.

## Pattern

- Logic Hook: minimal routing and event filtering
- Service: business rules, explicit dependencies, deterministic return values

## Recommended structure

- Hooks:
  - `custom/modules/<Module>/<Module>...LogicHook.php` (preferred for customizations)
  - `modules/<Module>/<Module>...LogicHook.php` (core / vendor code)
- Services:
  - `custom/lib/Service/<Name>Service.php`
  - Namespace: `SuiteCRM\Custom\Service`
- Unit tests (pure, no SuiteCRM install required):
  - `tests/unit/pure/Custom/Service/*Test.php`
  - PHPUnit config: `tests/phpunit.unit.xml`

## Example (router hook)

Keep the hook small and delegate to a service:

```php
#[\AllowDynamicProperties]
class AccountsJjwg_MapsLogicHook
{
    private $service;

    public function __construct()
    {
        $this->service = new \SuiteCRM\Custom\Service\AccountGeocodeService();
    }

    public function updateRelatedProjectGeocodeInfo(&$bean, $event, $arguments)
    {
        $this->service->syncRelatedProjectGeocodes($bean);
    }
}
```

## Example (service)

Services accept explicit dependencies and return deterministic results for tests:

```php
namespace SuiteCRM\Custom\Service;

class AccountGeocodeService
{
    public function __construct($mapService = null)
    {
    }

    public function syncRelatedProjectGeocodes($account): int
    {
        return 0;
    }
}
```

## Example (shared service for multiple modules)

If multiple modules share the same integration logic (like `jjwg_Maps` geocoding hooks), use a shared service and keep each module’s hook as a thin delegator:

- Shared service: `custom/lib/Service/JjwgMapsGeocodeService.php`
- Module hooks delegate into it (Contacts/Leads/Prospects/Meetings/Cases/Opportunities/Project).

## Naming and placement rules

- Service class name: `<Domain><Action>Service` (e.g., `AccountGeocodeService`, `InvoiceTaxService`)
- File path MUST match namespace: `custom/lib/Service/AccountGeocodeService.php`
- Hook methods MUST stay small (routing and delegation only)
- Avoid implicit globals inside services; pass dependencies via constructor parameters where possible

## Verification

Lint changed PHP files:

```bash
php -l path/to/file.php
```

Run unit tests that do not require a SuiteCRM install:

```bash
php vendor/bin/phpunit -c tests/phpunit.unit.xml
```
