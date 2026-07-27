<?php

return [
    'min_confidence' => (float) env('CATEGORIZATION_MIN_CONFIDENCE', 0.7),
    'model_version' => env('CATEGORIZATION_MODEL_VERSION', 'stub-v1'),
];
