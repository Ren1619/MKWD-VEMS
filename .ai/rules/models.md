---
paths:
  - '{app/Models/FuelLog.php,app/Actions/FuelLogs/**,app/Http/Requests/FuelLogs/**,resources/js/pages/fuel-logs/**,app/Models/OdometerEntry.php}'
---

# Models

## Fuel logs are append-only odometer events
A fuel log records manual liters, total cost, refueling time, and dashboard odometer; creating it appends a refuel odometer-history entry and may only move current mileage forward. Consumption is distance since the immediately previous chronological refuel divided by the current quantity; do not infer trip distance or allow backdated insertion that would invalidate stored calculations.
