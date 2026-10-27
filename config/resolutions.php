<?php

return [
    // Only a server administrator configures filesystem roots, never an HTTP request.
    'archive_root' => env('RESOLUTIONS_ARCHIVE_ROOT'),
    'uploads_root' => storage_path('app/resoluciones'),
    'max_upload_kb' => 102400,
    // Last PDF numbers read from D:/AREA DE ARCHIVO/RESOLUCIONES on 2026-10-27.
    // These bound each RDR sequence; they never represent uploaded file counts.
    'expected_year_totals' => [
        2000 => 5035,
        2001 => 6512,
        2002 => 7316,
        2003 => 6059,
        2005 => 5538,
        2006 => 5458,
        2007 => 5622,
        2008 => 1624,
        2009 => 1909,
        2010 => 1900,
        2011 => 2099,
        2012 => 2096,
        2013 => 2263,
        2014 => 2595,
        2015 => 2237,
        2016 => 3782,
        2017 => 3569,
        2018 => 4458,
        2019 => 3674,
        2020 => 1705,
        2021 => 2877,
        2022 => 4130,
        2023 => 4511,
        2024 => 4381,
        2025 => 3942,
        2026 => 2694,
    ],
];
