<?php

return [
    'debug' => $_ENV['APP_DEBUG'] ?? false,
    'view_cache' => $_ENV['VIEW_CACHE'] ?? false,
    'log_errors' => true,
    'log_error_details' => true,
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
];