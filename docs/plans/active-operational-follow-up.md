# Post-Meeting Operational Follow-up

## Active Package 1: Reporting Access and Period Filters

- Expose the read-only stock movement listing to HR.
- Add one reusable period button and modal to standalone history and archive listings.
- Include the Director all-requests listing, using the request creation timestamp.
- Weekly is a rolling range from the selected date through seven days later.
- Monthly is a rolling range from the selected date through the same date in the next month, clamped to a valid date.
- Flexible uses an explicit start and end date.
- Both displayed boundaries are inclusive.
- Keep page, preview, PDF, and XLSX results aligned.
- Handle the Director company timeline separately because it merges multiple event sources.
- Apply period filters only to permanent procurement-note history, never to actionable procurement or receipt queues.

Excluded: dashboard summaries, per-request detail timelines, and HR Monitoring Issues.

## Active Package 2: Stock Hard Copy

- Provide role-protected print routes for Warehouse, HR, and Director.
- Print all rows matching the active stock filters, independent of application pagination.
- Use A3 landscape print layout with repeated table column headers.
- Mark the report as a current stock snapshot generated at a specific time.
- Preserve the existing distinction between empty locations and occupied locations with zero stock.

## Review-Gated Package 3: Director View Reduction

- Start only after Packages 1 and 2.
- Review and adjust one Director page at a time.
- Require product feedback before proceeding to the next page.
