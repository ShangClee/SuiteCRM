## 1. Core Dispatcher Service

- [x] 1.1 Create `custom/lib/Service/AsyncLogicHookDispatcher.php`
- [x] 1.2 Implement `isJobQueued($target, $data)` method to check `job_queue` table for existing 'queued' or 'running' jobs with the same target and data.
- [x] 1.3 Implement `dispatch($target, $beanId, $name)` method to queue a new `SchedulersJob` using `SugarJobQueue`, skipping if `isJobQueued` returns true.

## 2. Base Worker Class

- [x] 2.1 Create `custom/lib/Job/BaseAsyncLogicHookWorker.php` implementing `RunnableSchedulerJob`.
- [x] 2.2 Implement `setJob(SchedulersJob $job)` method.
- [x] 2.3 Implement base `run($data)` method that handles retrieving the bean by ID (`$data`).
- [x] 2.4 Add logic to return `true` immediately if the bean no longer exists (graceful deletion handling).
- [x] 2.5 Define abstract method `processBean(SugarBean $bean)` for concrete workers to implement.

## 3. Testing

- [x] 3.1 Create `tests/unit/pure/Custom/Service/AsyncLogicHookDispatcherTest.php` to verify job dispatching and debouncing behavior.
- [x] 3.2 Create `tests/unit/pure/Custom/Job/BaseAsyncLogicHookWorkerTest.php` to verify bean loading and deleted record handling.
- [x] 3.3 Run tests using `vendor/bin/phpunit -c tests/phpunit.unit.xml tests/unit/pure/Custom/` to verify logic.
