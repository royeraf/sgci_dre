<?php

return [
    // Only a server administrator configures filesystem roots, never an HTTP request.
    'archive_root' => env('RESOLUTIONS_ARCHIVE_ROOT'),
    'uploads_root' => storage_path('app/resoluciones'),
    'max_upload_kb' => 102400,
    // Expected totals supplied by the archive owner; never represent uploaded files.
    'expected_year_totals' => [2026 => 2694],
];
