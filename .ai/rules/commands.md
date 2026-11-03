---
paths:
  - '{app/Integrations/MkwdIms/**,app/Services/Vehicles/**,app/Actions/Vehicles/**,app/Models/Vehicle*.php,app/Console/Commands/SyncMkwdVehicles.php,config/services.php}'
---

# Commands

## Apply MKWD snapshots atomically
Treat `GET /api/vehicles` as a complete snapshot only after strict validation. Reconcile by source plus remote ID inside one transaction; archive missing records without deleting them, reactivate returning records, and never overwrite locally owned operational fields.
