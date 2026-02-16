<?php

return [
    // Warehouse coordinates (default starting point)
    'warehouse_lat' => env('WAREHOUSE_LAT', -3.356837),
    'warehouse_lng' => env('WAREHOUSE_LNG', 114.577059),

    // Geofencing configuration
    'auto_arrive_radius_meters' => env('AUTO_ARRIVE_RADIUS_METERS', 100),

    // Trip configuration
    'service_minutes' => env('SERVICE_MINUTES', 15),
    'traffic_factor' => env('TRAFFIC_FACTOR', 1.30),

    // ORS profile configuration
    'ors_profile' => env('ORS_PROFILE', 'driving-car'),
];
