## Context

In SuiteCRM, operations like `after_save` logic hooks execute synchronously. When a user saves a record, if a logic hook fires to synchronize that data to a third-party ERP, the user interface remains blocked until the external API request completes. This leads to long page load times and timeout errors if the remote service is slow. 

SuiteCRM natively supports a background queue system through `SugarJobQueue` and the `job_queue` database table, which is processed by the system's cron scheduler (`cron.php`).

## Goals / Non-Goals

**Goals:**
- Provide a standardized, reusable architecture for offloading logic hooks into background tasks.
- Eliminate UI blocking for external API integrations triggered by record saves.
- Automatically retry failed synchronization tasks using the native `SchedulersJob` retry mechanisms.
- Prevent duplicate identical background tasks when a user repeatedly saves a record.

**Non-Goals:**
- Introducing external message brokers like Redis, RabbitMQ, or Beanstalkd.
- Refactoring core module logic hooks that perform rapid, internal database operations.
- Real-time (sub-second) execution, since we rely on the minute-level cron execution.

## Decisions

1. **Use Native `SugarJobQueue`**
   - **Rationale**: Utilizing SuiteCRM's built-in `job_queue` infrastructure requires zero additional server dependencies or infrastructure management. 
   - **Alternative Considered**: Redis + Laravel Horizon / Resque worker. While faster, it adds heavy infrastructure requirements which violate the simplicity goal for a standard SuiteCRM deployment.

2. **Job Payload Contains Only the Record ID**
   - **Rationale**: Instead of serializing the entire changed bean into the job `data` field, the hook will pass only the record's `id` (e.g., `Account ID`). The worker (`RunnableSchedulerJob`) will instantiate the latest state of the Bean (`BeanFactory::getBean()`) when it executes. This naturally handles race conditions where a user updates a record multiple times before the cron runs.
   - **Alternative Considered**: Passing a delta of changed fields. However, this could result in stale data being pushed if the record is updated again before the job executes.

3. **Debouncing Strategy via Database Lookup**
   - **Rationale**: Before dispatching a new job to the queue, the dispatcher will query the `job_queue` table to see if an identical job (`status IN ('queued', 'running')` and matching `target` and `data`) already exists. If one exists, it skips creating a duplicate. This prevents flooding the queue on rapid sequential saves.

## Risks / Trade-offs

- **Risk: Sync Latency** → **Mitigation:** Relying on the `cron` means a minimum 1-minute delay before external systems are updated. This is usually acceptable for ERP integrations, but users must be educated that the sync is not instantaneous.
- **Risk: Queue Bloat** → **Mitigation:** Rely on SuiteCRM's existing out-of-the-box "Clean Jobs Queue" scheduler to automatically prune successful jobs over time, ensuring the `job_queue` table doesn't grow infinitely.
- **Risk: Bean Deletion** → **Mitigation:** The worker class must handle cases where `BeanFactory::getBean()` returns null or empty, indicating the record was deleted between the logic hook firing and the job executing. The job should return `true` or handle it gracefully to avoid endless failures.
