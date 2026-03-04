---
status: complete
priority: p3
issue_id: "074"
tags: [code-review, dead-code, php]
dependencies: []
---

# ~327 LOC Dead Code Across Models and Repositories

## Problem Statement
14 dead methods across Model classes (Link, Scan, Url) and 7 dead methods across repositories (LinkRepository, ScanRepository, UrlRepository) plus 2 dead methods in Activator and 1 in StatusClassifier are never called anywhere. Total ~327 LOC of speculative API surface.

## Findings
**Files:** src/Model/Link.php (5 methods ~40 LOC), src/Model/Scan.php (6 methods ~65 LOC), src/Model/Url.php (3 methods ~30 LOC), src/Repository/UrlRepository.php (4 methods), src/Repository/ScanRepository.php (3 methods), src/Repository/LinkRepository.php (2 methods), src/Activator.php (2 methods), src/Checker/StatusClassifier.php (1 method)
Found by: code-simplicity-reviewer

## Proposed Solutions
### Option A: Remove all dead methods
- **Approach:** Delete all methods with zero callers. Keep only methods used by the actual codebase.
- **Effort:** Medium (many files, but simple deletions)

## Acceptance Criteria
- [ ] All removed methods have zero callers confirmed
- [ ] No runtime errors after removal

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
