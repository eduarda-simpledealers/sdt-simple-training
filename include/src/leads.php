<?php

/**
 * Get lead data by its ID
 *
 * @param PDO $connection
 * @return void
 */
function get_lead_data_by_id(\PDO $connection): void
{
    // Validate JSON
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

    // Get lead data
    try {
        $lead_id = $data['lead_id'];
        $get_lead_data_sql = <<<GET_LEAD_DATA_SQL
            SELECT a.nome ,
                a.sobrenome ,
                b.concessionaria ,
                c.nome_comercial ,
                c.prospector_id
            FROM `dexp_autofest-ab-exclusive_12-2024`.leads AS a
                INNER JOIN `dexp_autofest-ab-exclusive_12-2024`.concessionaria AS b
                    ON b.dealer_id = a.dealer_id
                INNER JOIN `dexp_autofest-ab-exclusive_12-2024`.prospectores AS c
                    ON a.prospector_id = c.prospector_id
            WHERE a.lead_id = {$lead_id}
            LIMIT 1;
        GET_LEAD_DATA_SQL;
        $stmt = $connection->prepare($get_lead_data_sql);

        // Send response
        if (!$stmt->execute()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'message' => 'failed_getting_lead_data'
            ]);
        } else {
            $lead_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'message' => 'success',
                'data' => $lead_data[0]
            ]);
        }
    } catch (\PDOException $ex) {
        error_log($ex->getMessage());
    }
}

/**
 * Make a lead present on the event
 *
 * @param PDO $connection
 * @return void
 */
function activate_lead_presence(\PDO $connection): void
{
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

    // Make lead present
    try {
        $lead_id = $data['lead_id'];
        $prospector_id = $data['prospector_id'];

        $create_new_presence_activity = <<<CREATE_NEW_PRESENCE_ACTIVITY
            INSERT INTO `dexp_autofest-ab-exclusive_12-2024`.atividades (
                prospector_id ,
                tipo_atividade_id ,
                lead_id
            ) VALUES (
                {$prospector_id},
                9,
                {$lead_id}
            );
        CREATE_NEW_PRESENCE_ACTIVITY;
        $stmt = $connection->prepare($create_new_presence_activity);

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
}
