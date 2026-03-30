## ADDED Requirements

### Requirement: Asynchronous Hook Dispatcher
The system SHALL provide a mechanism to dispatch logic hook operations to the native `job_queue` table instead of executing them synchronously during the web request.

#### Scenario: Dispatching a job
- **WHEN** a logic hook calls the async dispatcher with a record ID and a target worker class
- **THEN** a new `SchedulersJob` record is created in the database with status `queued`
- **THEN** the web request completes instantly without waiting for the job to finish

### Requirement: Debouncing duplicate jobs
The system SHALL prevent queueing duplicate identical jobs if a matching job is already pending or running.

#### Scenario: Submitting a duplicate job
- **WHEN** the dispatcher is called to queue a job for a record ID and target class
- **WHEN** a job with the exact same `data` (record ID) and `target` (class) exists in the `job_queue` with status `queued` or `running`
- **THEN** the dispatcher SHALL NOT create a new `SchedulersJob` record
- **THEN** the dispatcher SHALL return the ID of the existing pending job

### Requirement: Minimal Job Payload
The system SHALL pass only the record ID to the background job, requiring the worker to fetch the latest state at execution time.

#### Scenario: Worker execution
- **WHEN** the `cron.php` scheduler picks up the job
- **THEN** the worker class receives only the record ID in its `run()` method
- **THEN** the worker fetches the latest bean state from the database before performing external API syncs

### Requirement: Graceful handling of deleted records
The system SHALL fail gracefully and mark the job as complete if the target record was deleted before the job executed.

#### Scenario: Executing a job for a deleted record
- **WHEN** the worker attempts to fetch the bean using the provided ID
- **WHEN** the bean does not exist (deleted after the job was queued)
- **THEN** the worker SHALL return `true` to avoid endless retries and mark the job as successfully handled
