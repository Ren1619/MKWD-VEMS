---
paths:
  - '{app/Models/Trip.php,app/Actions/Trips/**,app/Http/Requests/Trips/**,resources/js/pages/trips/**}'
---

# Trips

## Trip dispatch is an audited lifecycle
Trips move only pending → approved/rejected → dispatched → completed. Approval assigns both resources, blocks overlapping approved/dispatched trips, and rejects conflicts with dated vehicle-driver assignments; use vehicle-then-driver locks for availability decisions. Dispatch and return record immutable actor/time/odometer history. Departure sets the local vehicle status to in_use; return updates mileage and changes status to available only if it is still in_use, preserving intervening maintenance/out-of-service decisions.
