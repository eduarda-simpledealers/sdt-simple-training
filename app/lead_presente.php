<?php

require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
            FROM `dexp_gwmdahruj_12-2024`.leads AS a
                INNER JOIN `dexp_gwmdahruj_12-2024`.concessionaria AS b
                    ON b.dealer_id = a.dealer_id
                INNER JOIN `dexp_gwmdahruj_12-2024`.prospectores AS c
                    ON a.prospector_id = c.prospector_id
            WHERE a.lead_id = {$lead_id}
            LIMIT 1;
        GET_LEAD_DATA_SQL;
        $stmt = $conn->prepare($get_lead_data_sql);

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
} else {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'message' => 'method_now_allowed'
    ]);
}
