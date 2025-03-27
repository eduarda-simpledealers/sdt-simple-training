<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$uri = $_SERVER['REQUEST_URI'];
$connection = connection(
    $_ENV['DB_HOST'],
    $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);

// Compare routes
switch ($uri) {
    case '/':
        // Index route
        include __DIR__ . '/src/index.php';

        break;

    case '/get-lead':
        // Not allowed method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'message' => 'method_now_allowed'
            ]);
        } else {
            // Run route script
            get_lead_data_by_id($connection);
        }

        break;

    case '/present-lead':
        // Not allowed method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'message' => 'method_now_allowed'
            ]);
        } else {
            // Run route script
            activate_lead_presence($connection);
        }

        break;

    default:
        // Page not found
        http_response_code(404);
        echo 'PAGE NOT FOUND';

        break;
}
