<?php

return [
    // Warehouse coordinates (default starting point)
    'warehouse_lat' => env('WAREHOUSE_LAT', -3.356837),
    'warehouse_lng' => env('WAREHOUSE_LNG', 114.577059),

    // Geofencing configuration
    'arrival_radius_meters' => env('ARRIVAL_RADIUS_METERS', 30),
    'departure_radius_meters' => env('DEPARTURE_RADIUS_METERS', 150),
    'dwell_time_seconds' => env('DWELL_TIME_SECONDS', 15), // Minimun stay before "Arrived"

    // Trip configuration
    'service_minutes' => env('SERVICE_MINUTES', 15),
    'traffic_factor' => env('TRAFFIC_FACTOR', 1.30),

    // Fuel Estimation configuration (Manusiawi)
    'fuel_price_per_liter' => env('FUEL_PRICE_PER_LITER', 13000), // Pertalite/Solar approx
    'fuel_km_per_liter' => env('FUEL_KM_PER_LITER', 10), // Box truck avg
    'fuel_safety_factor' => env('FUEL_SAFETY_FACTOR', 1.20), // +20% for traffic/idling

    // ORS profile configuration
    'ors_profile' => env('ORS_PROFILE', 'driving-car'),

    // System Maintenance
    'location_retention_days' => env('LOCATION_RETENTION_DAYS', 30),
];
