<?php
header('Content-Type: application/json');
require_once 'include/db_connect.php';

$lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;

if ($lead_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do lead inválido.']);
    exit;
}

try {
    // Obter o campo 'custom' atual do lead
    $stmt = $conn->prepare("SELECT custom FROM $table_leads WHERE lead_id = :lead_id");
    $stmt->execute(['lead_id' => $lead_id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lead) {
        echo json_encode(['success' => false, 'message' => 'Lead não encontrado.']);
        exit;
    }

    $custom = $lead['custom'] ? json_decode($lead['custom'], true) : [];

    // Atualizar o status de contato no campo 'custom'
    $custom['contacted'] = true;
    $custom['contacted_at'] = date('Y-m-d H:i:s');

    // Salvar as alterações no banco de dados
    $stmt = $conn->prepare("UPDATE $table_leads SET custom = :custom WHERE lead_id = :lead_id");
    $stmt->execute([
        'custom' => json_encode($custom),
        'lead_id' => $lead_id
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Erro ao registrar contato do lead: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar o contato.']);
}
?>
