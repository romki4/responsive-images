<?php

return [
    'destination' => 'cache',
    'mime_types' => [
        'avif',
        'webp',
        'png',
    ],
    'driver' => env('RESPONSIVE_IMAGES_DRIVER', 'public')
];
