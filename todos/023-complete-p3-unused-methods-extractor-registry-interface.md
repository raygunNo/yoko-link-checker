---
status: complete
priority: p3
issue_id: "023"
tags: [code-review, dead-code]
dependencies: []
---

# Unused Methods in ExtractorRegistry/Interface

## Problem Statement
`ExtractorRegistry` has 5 methods that are never called anywhere in the codebase: `unregister()`, `get()`, `all()`, `count()`, `has()`. `ExtractorInterface` defines 2 methods that are never called: `get_name()`, `get_fields()`. Only `register()`, `get_supporting()`, and `extract_from_post()` are actually used. These unused methods expand the API surface unnecessarily and impose implementation requirements on any class implementing the interface.

## Findings
- `ExtractorRegistry` unused methods: `unregister()`, `get()`, `all()`, `count()`, `has()`
- `ExtractorInterface` unused methods: `get_name()`, `get_fields()`
- Only `register()`, `get_supporting()`, and `extract_from_post()` are called in production code paths
- `HtmlExtractor` implements the unused interface methods unnecessarily

## Proposed Solutions

### Option A: Remove Unused Methods from Registry and Interface
- **Approach:** Delete `unregister()`, `get()`, `all()`, `count()`, `has()` from `ExtractorRegistry`. Remove `get_name()` and `get_fields()` from `ExtractorInterface` and their implementations in `HtmlExtractor` (and any other implementors).
- **Effort:** Small

## Technical Details
- **Affected files:**
  - `src/Extractor/ExtractorRegistry.php`
  - `src/Extractor/ExtractorInterface.php`
  - `src/Extractor/HtmlExtractor.php`

## Acceptance Criteria
- [ ] `unregister()`, `get()`, `all()`, `count()`, `has()` are removed from `ExtractorRegistry`
- [ ] `get_name()` and `get_fields()` are removed from `ExtractorInterface`
- [ ] Implementations of removed interface methods are removed from `HtmlExtractor` and any other implementors
- [ ] No references to removed methods remain in the codebase
- [ ] All existing tests pass without modification

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From comprehensive code review |
