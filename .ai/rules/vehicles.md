---
paths:
  - '{app/Actions/Vehicles/**,app/Http/Controllers/VehicleController.php,resources/js/pages/vehicles/**}'
---

# Vehicles

## IMS is authoritative for vehicle inventory
Vehicle inventory is read from the configurable `services.ims.vehicles_url` endpoint; VEMS does not persist a duplicate vehicle list. Keep upstream HTTP access server-side and preserve a usable page state when IMS is unavailable.

## IMS is authoritative for vehicle inventory
MKWD IMS is authoritative for vehicle master data. Read it server-side through `services.mkwd_ims`, never expose its token, and preserve the last known local snapshot when authentication, transport, or response validation fails.

## Legacy IMS URL configuration is obsolete
Ignore the older `services.ims.vehicles_url` rule. The secure integration uses `services.mkwd_ims.base_url` and `services.mkwd_ims.api_token`; application code must access both through `config()`.
