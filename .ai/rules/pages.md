---
paths:
  - '{app/Http/Controllers/DashboardController.php,app/Http/Requests/Dashboard/**,resources/js/pages/dashboard.tsx}'
---

# Pages

## Dashboard separates current state from period reports
Vehicle availability, operational status, active assignments, and maintenance due/overdue are current-state metrics. Trip and fuel totals use the selected inclusive reporting period. Availability counts AMS-present, non-retired vehicles as operable and only operational_status=available as available.
