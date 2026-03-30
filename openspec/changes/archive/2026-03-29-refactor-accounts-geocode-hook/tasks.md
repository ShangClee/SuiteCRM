## 1. Create Service Framework

- [x] 1.1 Create `custom/lib/Service` directory.
- [x] 1.2 Create `AccountGeocodeService` class with `jjwg_Maps` DI support.

## 2. Migrate Logic

- [x] 2.1 Migrate Project sync logic to service `syncRelatedProjectGeocodes`.
- [x] 2.2 Migrate logic for Opportunities, Cases, and Meetings to service methods.
- [x] 2.3 Refactor `AccountsJjwg_MapsLogicHook.php` to use the new service class.

## 3. Unit Tests

- [x] 3.1 Create `AccountGeocodeServiceTest.php` in the `tests/unit/phpunit/custom/lib/Service` directory.
- [x] 3.2 Add test cases verifying skipping when hooks are disabled.
- [x] 3.3 Add test cases verifying successful project address update.
