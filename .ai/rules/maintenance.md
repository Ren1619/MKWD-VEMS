---
paths:
  - '{app/Models/Maintenance*.php,app/Actions/Maintenance/**,app/Http/Requests/Maintenance/**,resources/js/pages/maintenance/**}'
---

# Maintenance

## Maintenance records use an audited lifecycle
Preventive schedules derive due/overdue state from both date and current vehicle mileage. Requests are preserved when converted to work orders. Work orders move open → in progress → completed/cancelled; completion/cancellation fields are immutable history, and vehicle status changes must be transactionally locked.
