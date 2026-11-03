---
paths:
  - '{app/Models/Vehicle.php,app/Actions/Vehicles/**,app/Http/Requests/Vehicles/**,resources/js/pages/vehicles/**}'
---

# Pages Vehicles

## Separate AMS master data from VEMS operations
AMS owns vehicle master data; VEMS may cache it only through synchronization. User-facing writes may change only mileage, operational status, fuel type, maintenance interval, and operational notes. Missing or disposed AMS assets must be retained; disposed/retired assets remain operationally retired.
