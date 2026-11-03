---
paths:
  - '{app/Models/Driver.php,app/Actions/Drivers/**,app/Integrations/Hris/**,app/Http/Requests/Drivers/**,resources/js/pages/drivers/**}'
---

# Drivers

## Keep HRIS employee identity separate from driver details
HRIS owns employee identity and profile/contact snapshots. Driver writes must resolve the selected HRIS employee on the server and must not accept manually submitted HRIS profile fields. License details, active/inactive status, driving approval and alternate contacts belong to VEMS and survive HRIS refreshes. The HRIS API contract is supplied separately; do not infer its endpoint, authentication or raw field mapping from the normalized EmployeeDirectory interface.

## Outsourced driver profiles are locally editable
Drivers with source_type=outsourced have no HRIS ID and their profile fields are maintained in VEMS. Allow manual profile writes only for outsourced records; use the persisted source on updates and reject source switching so HRIS records stay protected. Outsourced drivers cannot sync with HRIS and do not require HRIS presence for eligibility; license, active status and driving approval requirements still apply.
