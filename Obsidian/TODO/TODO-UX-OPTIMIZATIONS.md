# TODO — UX Optimizations

- **Date:** 2026-05-11
- **Scope:** Improving User Experience across the Billing System

---

## 1. Dynamic Dropdown Filtering

Implement dynamic filtering for product/service and DSI Code dropdowns in the New Invoice form. The available options should be filtered based on the selected partner's category.

### Tasks
- [x] Implement dynamic filtering for product/service dropdown (Hotel=HTL, Travel=TVL, Tour Desk=TRD)
- [x] Implement dynamic filtering for DSI Code dropdown based on Partner category
- [x] Ensure the UI provides feedback if a category has no matching items (Strict Filter & Warning)
- [x] Verify that changing partners mid-selection clears or re-filters the dependent dropdowns (Auto-Reset)

### References
- GitHub Issue: [#18](https://github.com/tsanirestya/tsbl-invoice-laravel/issues/18)
- Obsidian Issue: [ISSUE-002-DYNAMIC-DROPDOWN-FILTERING.md](file:///d:/XAMPP%20NEW/htdocs/tsbl-invoice-laravel/Obsidian/Issues/ISSUE-002-DYNAMIC-DROPDOWN-FILTERING.md)

---

## Status Tracker

| Task | Status | Notes |
|------|--------|-------|
| Dynamic Dropdown Filtering | `done` | Filter by HTL/TVL/TRD based on Partner |
