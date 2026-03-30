## ADDED Requirements

Date: 20260330

### Requirement: Detect legacy API usage from HTTP access logs
The program SHALL provide a repeatable process to detect usage of legacy SuiteCRM endpoints from HTTP access logs.

#### Scenario: Detect REST v4 usage
- **WHEN** access logs are analyzed
- **THEN** requests to `/service/v4/rest.php` and `/service/v4_1/rest.php` are extracted with caller attribution data

#### Scenario: Detect SOAP usage
- **WHEN** access logs are analyzed
- **THEN** requests to `/soap.php` and versioned `service/*/soap.php` endpoints are extracted with caller attribution data

### Requirement: Report includes caller attribution and usage characteristics
Each detected legacy API caller SHALL be reported with, at minimum: source identifier (IP, client id, or upstream identity), request path, request volume, and time window.

#### Scenario: Generate a caller usage summary
- **WHEN** detection output is produced for a defined time window
- **THEN** the output contains aggregated counts by caller and endpoint path

### Requirement: Detect legacy API usage from code and configuration search
The program SHALL define a repeatable search approach to identify legacy endpoint references in code and configuration repositories.

#### Scenario: Search finds legacy endpoint strings
- **WHEN** code/config search is performed across known integration repositories
- **THEN** any references to `soap.php` and `service/v4*/rest.php` are collected with repository location context

### Requirement: Detection output feeds the integration inventory
Detection results SHALL be translated into integration inventory records or linked as evidence to existing records.

#### Scenario: Convert detection finding into inventory evidence
- **WHEN** a new caller is detected in logs or code
- **THEN** the finding is attached to a matching inventory record or a new record is created

### Requirement: Detection supports verification of legacy retirement readiness
The program SHALL define a process to assert that legacy endpoints have no remaining consumers for a defined validation period.

#### Scenario: Verify zero legacy usage
- **WHEN** a retirement readiness check is run for a validation period
- **THEN** the output indicates whether any callers still hit legacy endpoints during that period
