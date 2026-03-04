---
status: complete
priority: p1
issue_id: "034"
tags: [code-review, php, runtime-error, data-loss]
dependencies: []
---

# Ghost Property $url->redirect_url Causes Silent Data Loss

## Problem Statement

`BatchProcessor::check_internal_url_via_http()` assigns to `$url->redirect_url` at line 509, but the `Url` model has no such property — the correct property is `$url->final_url`. This creates a dynamic property (deprecated in PHP 8.2, fatal in PHP 9.0) that is never persisted to the database because `Url::to_row()` only serializes declared properties.

When an internal URL returns a 3xx redirect, the redirect destination (Location header) is captured into a dynamic property that is silently discarded. The `final_url` column in the database remains NULL for these redirected internal URLs.

## Findings

**File:** `src/Scanner/BatchProcessor.php` line 509

```php
$url->redirect_url = is_array( $headers['location'] ) ? $headers['location'][0] : $headers['location'];
```

**Url model:** `src/Model/Url.php` line 118 declares `public ?string $final_url = null;` — no `redirect_url` property exists.

**Url::to_row():** at line 238 only serializes declared properties, so `redirect_url` is never saved.

Found by: architecture-strategist, call-chain-verifier

## Proposed Solutions

### Option A: Fix property name (Recommended)
- **Approach:** Change `$url->redirect_url` to `$url->final_url` at line 509
- **Pros:** One-line fix, data starts being persisted correctly
- **Cons:** None
- **Effort:** Small
- **Risk:** Low

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:** src/Scanner/BatchProcessor.php (line 509)
- **Affected components:** Internal URL checking, redirect tracking

## Acceptance Criteria
- [ ] `$url->final_url` is set (not `$url->redirect_url`) when internal URLs redirect
- [ ] Redirect destinations for internal 3xx responses are persisted to the database
- [ ] No PHP 8.2+ deprecation warnings for dynamic properties

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
