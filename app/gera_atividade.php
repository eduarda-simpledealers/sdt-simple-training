<?php

require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        $error = json_last_error_msg();

        http_response_code(400);
        echo json_encode([
            'message' => 'invalid_json'
        ]);

        exit();
    }

    // Check if user is already present
    try {
        $lead_id = $data['lead_id'];

        $user_is_already_present = <<<USER_IS_ALREADY_PRESENT
            SELECT *
            FROM `dexp_gwmdahruj_12-2024`.atividades
            WHERE tipo_atividade_id = 9
                AND lead_id = {$lead_id};
        USER_IS_ALREADY_PRESENT;
        $stmt = $conn->prepare($user_is_already_present);

        // Send response
        if (!$stmt->execute()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'message' => 'failed_confirming_lead'
            ]);
        } else {
            $lead_id = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($lead_id)) {
                http_response_code(200);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'message' => 'Este convidade ja esta presente.'
                ]);

                exit();
            }
        }
    } catch (\PDOException $ex) {
        error_log($ex->getMessage());

        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'message' => 'failed_confirming_lead'
        ]);
    }

    // Make lead present
    try {
        $lead_id = $data['lead_id'];
        $prospector_id = $data['prospector_id'];

        $create_new_presence_activity = <<<CREATE_NEW_PRESENCE_ACTIVITY
            INSERT INTO `dexp_gwmdahruj_12-2024`.atividades (
                prospector_id ,
                tipo_atividade_id ,
                lead_id
            ) VALUES (
                {$prospector_id},
                9,
                {$lead_id}
            );
        CREATE_NEW_PRESENCE_ACTIVITY;
        $stmt = $conn->prepare($create_new_presence_activity);

        // Send response
        if (!$stmt->execute()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'message' => 'failed_confirming_lead'
            ]);
        } else {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'message' => 'success'
            ]);
        }
    } catch (\PDOException $ex) {
        error_log($ex->getMessage());

        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'message' => 'failed_confirming_lead'
        ]);
    }
} else {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'message' => 'method_now_allowed'
    ]);
}