## Why

Currently, long-running processes triggered by logic hooks (such as synchronizing data with third-party ERPs via API) run synchronously during the web request. This blocks the UI, creating a poor user experience as they wait for the API call to complete. Moving these operations to an asynchronous background queue ensures the UI remains snappy while the heavy lifting happens safely in the background.

## What Changes

- **Background Dispatch Pattern**: Update logic hooks that perform slow or external operations to instead dispatch a job to SuiteCRM's native `SugarJobQueue` table.
- **Job Payload Minimization**: Pass only the record `id` in the job payload, allowing the worker to fetch the most up-to-date bean state at execution time.
- **Duplicate Job Debouncing**: Implement a mechanism to check for existing pending jobs for a specific record before queuing a new one, preventing duplicate identical jobs if a user rapidly saves a record multiple times.
- **Goals**: Offload slow web request processes to cron jobs, improve UI responsiveness, and utilize native SuiteCRM queuing without adding new infrastructure.
- **Non-Goals**: Replacing the native `SchedulersJob` with external services like Redis/RabbitMQ, or refactoring every single logic hook in the system (only slow/external ones).
- **Risks**: Increased load on the cron processing queue. Potential race conditions if cron runs infrequently.
- **Verification**: Job submission should return instantly. `job_queue` table should correctly reflect pending and processed jobs.

## Capabilities

### New Capabilities
- `async-logic-hooks`: A pattern and implementation for offloading synchronous logic hook execution to the native SuiteCRM `SugarJobQueue`.

### Modified Capabilities

## Impact

- Web UI response times will improve for records with heavy logic hooks.
- SuiteCRM's background cron (`cron.php`) will handle a higher volume of jobs.
- **Installation/Migration Impact**: None. Relies entirely on existing `SchedulersJob` tables and native SuiteCRM architecture.
