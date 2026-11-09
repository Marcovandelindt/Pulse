<?php

declare(strict_types=1);

return [
    'processing' => [
        // Points with worse accuracy than this are excluded from visit/trip detection
        // (but still stored in location_points)
        'max_accuracy_meters' => 100,

        // A group of points counts as a visit if they all stay within this radius
        'visit_radius_meters' => 100,

        // Minimum duration before a stationary cluster is treated as a visit
        'min_visit_duration_minutes' => 10,

        // A new visit is merged into an existing place when within this distance
        'place_merge_radius_meters' => 150,

        // Maximum number of coordinate pairs stored per trip polyline
        'trip_polyline_max_points' => 300,

        // Hours of buffer added around a batch's time range when reprocessing,
        // to catch visits that straddle a batch boundary
        'processing_buffer_hours' => 2,
    ],
];
