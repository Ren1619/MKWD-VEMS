---
paths:
  - '{app/Actions/Assignments/**,app/Models/VehicleDriverAssignment.php,app/Http/Requests/Assignments/**,resources/js/pages/assignments/**}'
---

# Assignments

## Assignments use inclusive dates and retain history
Primary and temporary assignments both exclude overlapping date intervals for either the same vehicle or driver. Null end dates are open-ended; an assignment ending today remains current through today. Serialize writes by locking vehicle then driver in one transaction before checking overlaps. Ending may only shorten a current assignment and closes it against further edits; never delete assignment history or change AMS/HRIS master data as an assignment side effect.
