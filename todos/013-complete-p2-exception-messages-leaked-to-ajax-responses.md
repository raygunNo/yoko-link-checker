---
status: complete
priority: p2
issue_id: "013"
tags: [code-review, security, php]
dependencies: []
---

# Exception Messages Leaked to AJAX Responses

## Problem Statement
`AjaxHandler` catch blocks send `$e->getMessage()` directly to the client via `wp_send_json_error()`. Exception messages can contain internal file paths, database table names, SQL query fragments, server configuration details, or PHP version information. This information disclosure helps attackers understand the server environment and identify potential attack vectors.

## Findings
- `AjaxHandler` contains multiple catch blocks that pass `$e->getMessage()` directly to `wp_send_json_error()`.
- Exception messages from database errors may contain table names, column names, and partial SQL queries.
- Exception messages from file operations may contain full server filesystem paths.
- Exception messages from PHP runtime errors may contain class names, method signatures, and internal architecture details.
- There is no sanitization or filtering of exception messages before sending to the client.

## Proposed Solutions

### Option A: Log Full Exception, Return Generic Messages
- **Approach:** In each catch block, log the full exception (including message, trace, and context) using the plugin's `Logger` class. Return a generic, user-friendly error message to the client via `wp_send_json_error()`. Use a mapping of exception types to user-friendly messages.
- **Pros:** No information leakage; proper error logging for debugging; user gets actionable (if generic) feedback
- **Cons:** Harder to debug issues from the client side; need to check server logs instead
- **Effort:** Small
- **Risk:** Low

### Option B: Sanitize Exception Messages Before Sending
- **Approach:** Create a sanitization function that strips file paths, SQL fragments, and other sensitive data from exception messages before sending to the client.
- **Pros:** Preserves some error context for the user; less disruptive change
- **Cons:** Difficult to sanitize all possible sensitive patterns; risk of missing some patterns; fragile approach
- **Effort:** Medium
- **Risk:** High

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `src/Admin/AjaxHandler.php` (multiple catch blocks)

## Acceptance Criteria
- [ ] No exception messages containing internal paths, SQL, or server details are sent to the client
- [ ] All exceptions are logged with full details (message, stack trace, context) via the Logger
- [ ] Client receives user-friendly, generic error messages appropriate to the action attempted
- [ ] Error messages are translatable (use `__()` or `esc_html__()`)
- [ ] Developers can enable verbose error output via `WP_DEBUG` or a plugin-specific constant if needed for debugging
- [ ] AJAX error responses maintain a consistent format

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
