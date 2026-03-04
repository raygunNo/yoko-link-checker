---
status: complete
priority: p1
issue_id: "001"
tags: [code-review, php, runtime-error, broken-feature]
dependencies: []
---

# Ignore/Unignore Feature Causes Runtime TypeError

## Problem Statement
The entire ignore/unignore link feature is broken due to two compounding issues. First, `AjaxHandler::ignore_link()` and `unignore_link()` call `$this->link_repository->update($link_id, array('ignored' => 1))` passing `(int, array)`, but `LinkRepository::update()` expects a single `Link` object. With `declare(strict_types=1)` in effect, this throws a `TypeError` at runtime. Second, even if the method signature were correct, the code targets the wrong database table entirely — the `yoko_lc_links` table has no `ignored` column. The `is_ignored` column exists on the `yoko_lc_urls` table. The same broken pattern exists in `ResultsPage.php` for the GET-based row actions. Users cannot ignore or unignore any link through any UI path.

## Findings
- `AjaxHandler::ignore_link()` at line 335 calls `$this->link_repository->update($link_id, array('ignored' => 1))` — wrong method signature and wrong table.
- `AjaxHandler::unignore_link()` at line 368 calls `$this->link_repository->update($link_id, array('ignored' => 0))` — same issue.
- `ResultsPage.php` line 169 performs the same broken ignore call via GET row action.
- `ResultsPage.php` line 191 performs the same broken unignore call via GET row action.
- `LinkRepository::update()` expects a `Link` object, not `(int, array)`.
- The `is_ignored` column lives on `yoko_lc_urls`, not `yoko_lc_links`.

## Proposed Solutions

### Option A: Fix to use update_by_id() method
- **Approach:** Change calls to use `update_by_id()` which accepts `(int, array)` signature on the link repository.
- **Pros:** Minimal code change; fixes the TypeError immediately.
- **Cons:** Still operates on the wrong table (`yoko_lc_links` instead of `yoko_lc_urls`); would require adding an `ignored` column to the links table or is fundamentally incorrect.
- **Effort:** Small
- **Risk:** High — fixes the crash but the feature still won't work correctly against the wrong table.

### Option B: Rewrite ignore/unignore to use UrlRepository (Recommended)
- **Approach:** Rewrite the ignore/unignore handlers in both `AjaxHandler` and `ResultsPage` to use `UrlRepository::mark_ignored()` / `unmark_ignored()` which operate on the correct `yoko_lc_urls` table with the `is_ignored` column.
- **Pros:** Correct table and column; uses existing repository methods designed for this purpose; semantically correct (ignoring is a URL-level concept).
- **Cons:** Requires injecting `UrlRepository` into `AjaxHandler` and `ResultsPage` if not already available; slightly larger change.
- **Effort:** Medium
- **Risk:** Low — uses established, tested repository methods on the correct table.

## Recommended Action
<!-- To be filled during triage -->

## Technical Details
- **Affected files:**
  - `src/Admin/AjaxHandler.php` (lines 335, 368)
  - `src/Admin/ResultsPage.php` (lines 169, 191)
- **Affected components:** AJAX handler, Results page row actions, Link ignore/unignore feature

## Acceptance Criteria
- [ ] Ignore action works via AJAX endpoint without TypeError
- [ ] Unignore action works via AJAX endpoint without TypeError
- [ ] Ignore action works via GET row action on Results page without TypeError
- [ ] Unignore action works via GET row action on Results page without TypeError
- [ ] Operations target the `is_ignored` column on the `yoko_lc_urls` table
- [ ] Ignored URLs are correctly filtered/displayed in the Reports UI
- [ ] Unit tests cover both ignore and unignore paths

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |

## Resources
- Full codebase review session
