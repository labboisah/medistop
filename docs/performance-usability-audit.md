# MediStop speed, performance, and usability audit

**Audit date:** 30 October 2026  
**Scope:** Source and configuration review of the Laravel application, its core billing and imaging workflows, and shared list/navigation UI.

## Executive summary

The application already uses database pagination on many operational lists and eager loading in several controllers. The main performance risk is the finance dashboard: it loads every bill in selected date periods into PHP and iterates bills to compute revenue shares. The staff worklist also has a path that returns an unbounded result set. Date and text filtering patterns, limited date indexing, and very large accepted imaging uploads are likely to become more costly as activity and image volume grow.

The most useful first step is to establish production measurements, then move aggregate calculations into SQL and bound every operational list. In parallel, clarify the imaging queue states and make the common list and form interactions easier to operate on small screens and with keyboard/screen reader support. This is a code review, not a measured load test: there are no response-time, query-count, data-volume, device, or production configuration measurements in this report.

## Findings

### High priority

1. **Finance dashboards load full bill collections and calculate shares row by row.** `FinanceController::index()` fetches all bills for today and the selected month with `get()` before summing fields (`app/Http/Controllers/FinanceController.php`, around lines 32 and 52). The user finance helper repeats this pattern and calls `shares()` for each bill (`app/Models/User.php`, around lines 112–130 and 144–161). This increases PHP memory and work with transaction volume and may trigger relation queries inside `shares()`. The month query in `User::finance()` filters only by month number, so it can include matching months from different years; the finance controller instead bounds by a full month range.

   **Approach:** Replace collection sums with database `SUM`/conditional aggregate queries. For revenue shares, aggregate bill item and distribution amounts in SQL, preserving the existing discount and share rules. Use inclusive start/exclusive next-month date bounds so indexes remain usable and the year is constrained. First document the current financial definitions and reconcile results against existing reports before switching the dashboard.

2. **Radiographers find work by manually entering a bill number.** The radiographer branch of `StaffResultController::index()` returns `staff.results.index` without a worklist (`app/Http/Controllers/StaffResultController.php`, around lines 59–89). That view asks the user to enter a fully paid bill number (`resources/views/staff/results/index.blade.php`). The radiologist has a searchable, paginated worklist, while the radiographer must already know the bill number. This adds lookup steps and makes pending work less visible; it is a usability/workflow issue rather than a confirmed unbounded query.

   **Approach:** Make the controller own the list query, add filters that match the actual task (patient/bill, service, workflow state, date), and paginate consistently. Confirm query count stays roughly constant as page size grows.

3. **Imaging uploads permit files up to 500 MiB each.** Validation in `StaffResultController::store()` uses `max:512000` per file (around lines 166–184); multiple files may be submitted together. This is permissive for radiographer workflow, but it can create long requests, memory/temp-disk pressure, and slow delivery if the web server/PHP limits and connection are not sized for DICOM files.

   **Approach:** Keep DICOM support and avoid arbitrary client-side rejection. Check actual PHP/web server limits and typical study size. Prefer direct-to-disk or resumable/chunked upload for large studies, show per-file progress and clear retry feedback, and put explicit configurable total request limits at the infrastructure boundary. Add background thumbnail/preview work only if needed; preserve original diagnostic files.

### Medium priority

4. **Several list filters are not index-friendly at scale.** Search uses `%term%` `LIKE` across bill number, patient name, service, and related names (for example `BillController::index()` and `StaffResultController::index()`). Leading wildcards generally prevent ordinary B-tree indexes from helping. Date filtering often uses `whereDate()` or `whereMonth()` on the column, which may also inhibit index range scans depending on the database. The reviewed bill and bill-result migrations define unique/foreign-key indexes, but do not show dedicated composite indexes for common workflow/date filters.

   **Approach:** Measure slow queries first. Replace date wrapper predicates with timestamp/date ranges. Add only indexes supported by observed query plans, likely combinations around imaging assignment/status/date and operational bill dates. Keep substring matching where users need it; consider prefix search for bill/reference identifiers or database full-text search only if measured need justifies the extra complexity.

5. **The bill list can trigger repeated lazy relationship queries.** `BillController::index()` uses `withCount('results')` but does not eager load bill items/services. The view calls `$bill->shares()` four times per row. `Bill::shares()` iterates `items`, and each `BillItem::shares()` accesses its `service` and `bill` relations (`app/Models/Bill.php`, `app/Models/BillItem.php`, and `resources/views/bills/index.blade.php`). Eloquent may cache each loaded relation on that model, but the first render can still cause relationship queries per bill/item. Several other pages already eager load their displayed relations (`ExpenseController`, `PublicInvestigationBookingController`, radiology history), so this should be fixed on the bill list specifically and checked with query counts.

   **Approach:** Capture query count on representative pages with 15 rows. Eager load exactly the relations needed by each page or move display totals into aggregate queries. Add a development-only guard against lazy loading for these paths if the project can support it.

6. **Operational filter behavior and responsive tables deserve a focused usability pass.** Shared filters debounce-submit after 400 ms and preserve scalar query parameters (`resources/views/components/table-filters.blade.php`). Shared pagination reports result ranges and retains query strings (`resources/views/components/table-pagination.blade.php`). This is a solid baseline. However, the filter row is deliberately non-wrapping with horizontal overflow, so date controls may require horizontal scrolling on narrow screens; the form has no explicit submit button, which makes the auto-submit behavior less discoverable and can be inconvenient for keyboard users. Long, wide clinical tables should also be checked at phone and tablet widths.

   **Approach:** Keep desktop filters in one row as requested, while allowing a deliberate stacked or scrollable small-screen layout with visible cues. Add accessible labels/instructions and a clear reset action; preserve filter values across pagination. Check tables for horizontal-scroll affordance, sticky headings only where they help, and avoid making the full page wider than the viewport.

7. **Imaging queue states should be visible and consistent across staff roles.** The workflow has distinct radiographer upload, radiologist reporting, and radiographer print actions. The worklist should make “awaiting upload”, “uploaded/awaiting report”, and “report submitted” immediately scannable and provide a clear next action for each role. Existing list/search pagination is helpful, but this audit did not verify every state label and transition in the UI.

   **Approach:** Map each stored state to a concise label, color plus text (not color alone), and one primary action. Keep status, assignee, received time, and last-updated time visible. Validate this with radiographers and radiologists using a small set of representative cases.

### Lower priority / confirm with measurement

8. **Production optimization and infrastructure cannot be confirmed from repository defaults.** `.env.example` is a local development template (`APP_ENV=local`, `APP_DEBUG=true`, SQLite, database-backed session/cache/queue). This is normal for a template and does not prove production is misconfigured. The actual production environment was not inspected. Composer's development script runs the queue listener, Pail, and Vite, which is for local development rather than a production process manager.

   **Approach:** Verify production uses debug off, config/route/view caches as appropriate, built Vite assets, a production-grade database, and a supervised queue worker. Use Redis for cache/queue/session only if operations can support it and measurement shows benefit. Do not expose environment secrets in audit artifacts.

9. **PDF and spreadsheet export cost is unmeasured.** `ReportController` retrieves date-range bills/expenses with related data and generates PDF/Excel output. For large date ranges this can consume substantial memory and hold a request open. The period is user-selected, and no production volume or export timing data was available.

   **Approach:** Record export duration, row count, memory, and timeout rate. For large exports, queue generation and provide a status/download link; stream CSV where appropriate. Keep small operational exports synchronous if they remain fast.

## Improvement approach

### Phase 1 — Establish a baseline (first)

- Record endpoint p50/p95/p99 response time, HTTP error rate, CPU/memory, database query duration/count, and queue depth for dashboard, bill list, radiology worklist, booking list, and report exports.
- Capture production dataset sizes (counts only; do not use patient-identifiable samples) and representative upload sizes.
- Reproduce the same measurements in a staging environment with comparable database and storage setup.
- Agree on initial targets: p95 under 1 second for ordinary list/dashboard reads, upload feedback visible immediately with completion/progress, and no unbounded collection on operational list pages. Adjust targets against real network and machine constraints.

### Phase 2 — Low-risk query improvements

- Move dashboard and finance totals into SQL aggregates and correct date boundaries; verify financial totals against known report periods before release.
- Ensure every operational list has server-side pagination and stable ordering.
- Replace date wrappers with range predicates and add indexes only after reviewing query plans on the production database engine.
- Check the top list pages for N+1 queries and select only needed columns/relations.
- Compare baseline and post-change response time, query count, and memory for identical data and filters.

### Phase 3 — Imaging and report workflow

- Review upload limits end to end (browser, PHP, web server/proxy, storage) and add progress/retry behavior for large studies.
- Measure image delivery time and storage growth. Use private object storage/CDN or local disk optimization only with access controls and deployment support in place.
- Make role-specific next actions and statuses consistent, and verify image viewing/downloading remains authorized.
- Move slow, repeatable report exports to queued jobs when measurement supports it.

### Phase 4 — Usability validation and ongoing monitoring

- Run short task walkthroughs with a radiographer, radiologist, cashier/center user, and administrator: find a booking, accept/confirm payment, locate an assigned study, upload/review/report, print, and recover from an invalid form.
- Check keyboard-only operation, screen-reader names for controls, focus after validation errors, contrast, and 320–390 px mobile layouts.
- Monitor response percentiles, slow queries, failed jobs, upload failures, and export failures after release. Revisit indexes and caching from evidence rather than adding broad caches prematurely.

## Audit limits

- This was a source/configuration review of the local checkout. No production environment values, patient data, live request traces, representative data volume, browser/device usability session, or load test was examined.
- No test suite or benchmark was run. Conclusions about impact are risks inferred from the inspected code paths, not measured response-time claims.
- Priority should be confirmed using Phase 1 metrics, especially for database indexes, image upload architecture, and asynchronous exports.
