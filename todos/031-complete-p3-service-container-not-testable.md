---
status: complete
priority: p3
issue_id: "031"
tags: [code-review, architecture, testing]
dependencies: []
---

# Service Container Not Testable

## Problem Statement
`Plugin.php` acts as a service container but provides no mechanism to override or inject mock dependencies. There are no interfaces for services. Integration tests cannot inject mock dependencies without using reflection to bypass access controls. This is the largest testability gap in the codebase and makes it difficult to write isolated tests for components that depend on services from the container.

## Findings
- `Plugin.php` service container has no `set_service()` or override mechanism
- Services are created inline with no interfaces defined
- Integration tests would need reflection to inject mock dependencies
- This affects testability of all components that retrieve services from the container

## Proposed Solutions

### Option A: Add set_service() Method
- **Approach:** Add a `set_service(string $key, object $instance)` method to `Plugin.php` that allows overriding any service in the container. This is the minimal change needed to enable test dependency injection. Optionally, extract repository interfaces to allow type-safe mocking.
- **Effort:** Small

### Option B: Extract Interfaces and Add Full DI Support
- **Approach:** Define interfaces for key services (repositories, HTTP client, etc.) and update `Plugin.php` to support constructor injection or a `set_service()` method. This provides stronger type safety and clearer contracts but requires more refactoring.
- **Effort:** Large

## Technical Details
- **Affected files:**
  - `src/Plugin.php`

## Acceptance Criteria
- [ ] A `set_service()` or equivalent override method exists on the service container
- [ ] Tests can inject mock dependencies without reflection
- [ ] Existing service resolution behavior is unchanged for production code
- [ ] At least one example test demonstrates dependency injection via the new mechanism
- [ ] All existing tests pass without modification

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |
