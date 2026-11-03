---
paths:
  - '{app/Models/OdometerEntry.php,app/Actions/Odometer/**,app/Actions/Trips/**,app/Actions/Vehicles/UpdateVehicleOperationalInformation.php,app/Http/Requests/Odometer/**,app/Http/Requests/Vehicles/UpdateVehicleRequest.php,resources/js/pages/odometer/**,resources/js/pages/vehicles/**}'
---

# Js Pages Vehicles

## Odometer changes are append-only audited events
Treat vehicles.current_mileage as a latest snapshot, never as the only record. Trip departure and return actions must append trip-linked odometer entries in the same transaction as their lifecycle change. A manual mileage change requires a reason, may correct upward or downward, records the manager and prior reading, and is blocked while the vehicle has a dispatched trip. Never edit or delete odometer history.
