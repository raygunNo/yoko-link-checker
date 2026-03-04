---
status: pending
priority: p3
issue_id: "055"
tags: [code-review, architecture, agent-native]
dependencies: []
---

# No REST API or WP-CLI Commands (Agent-Native Gap)

## Problem Statement

0/12 plugin capabilities are accessible via REST API or WP-CLI. All actions require browser-generated nonces (AJAX) or HTML form submissions. The internal architecture (service container, repositories, hooks) is well-designed for programmatic access but lacks an HTTP/CLI surface layer.

## Findings

Found by: agent-native-reviewer

No `register_rest_route` or `WP_CLI` references exist in the codebase.

## Proposed Solutions

### Option A: Add REST API controller
- **Approach:** Create `src/Api/RestController.php` with routes under `yoko-lc/v1` namespace using capability-based `permission_callback`.
- **Effort:** Large

### Option B: Add WP-CLI commands
- **Approach:** Create `src/Cli/ScanCommand.php` wrapping existing service methods.
- **Effort:** Medium

## Acceptance Criteria
- [ ] At minimum: scan start/status, URL list, ignore/unignore available programmatically
- [ ] Authentication via Application Passwords or capability checks (not nonces)

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From follow-up code review |
