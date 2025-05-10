<?php

return [
    'hot' => env('VITE_HOT_FILE', storage_path('vite.hot')),
    'build_path' => public_path('build'),
    'manifest' => public_path('build/.vite/manifest.json'),
    'server' => [
        'host' => '0.0.0.0', // Ubah agar bisa diakses dari mana saja
        'port' => 5173,
    ],
];
