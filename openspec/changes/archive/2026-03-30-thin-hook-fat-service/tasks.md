## 1. Documentation and Conventions

- [x] 1.1 Add developer-facing docs for Thin Hook / Fat Service usage
- [x] 1.2 Document naming and placement rules for services and tests
- [x] 1.3 Add verification guidance (lint/unit test commands) to docs

## 2. Service Pattern Reference Implementation

- [x] 2.1 Identify one existing hook to refactor as the reference example
- [x] 2.2 Extract hook business rules into a Service class under custom/lib/Service
- [x] 2.3 Update the hook class to delegate to the Service (thin router)
- [x] 2.4 Add unit tests for the Service using PHPUnit + Mockery

## 3. Verification

- [x] 3.1 Run php -l on changed PHP files
- [x] 3.2 Run targeted PHPUnit tests for the new/updated Service
- [x] 3.3 Confirm no new IDE diagnostics for the updated files
