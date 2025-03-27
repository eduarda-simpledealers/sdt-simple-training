<?php
header('Content-Type: application/json');
require_once 'include/db_connect.php';
require_once 'include/functions.php';

$prospector_id = isset($_POST['prospector_id']) ? intval($_POST['prospector_id']) : 0;

if ($prospector_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do prospector inválido.']);
    exit;
}

// Obter os leads do prospector
$stmt = $conn->prepare("
    SELECT 
        lead_id,
        CONCAT(nome, ' ', sobrenome) AS nome_completo, 
        telefone, 
        typelead,
        custom,
        registro_data
    FROM $table_leads 
    WHERE prospector_id = :prospector_id
");
$stmt->execute(['prospector_id' => $prospector_id]);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar os leads para incluir o status de contato
foreach ($leads as &$lead) {
    $custom = $lead['custom'] ? json_decode($lead['custom'], true) : [];
    $lead['contacted'] = isset($custom['contacted']) && $custom['contacted'] ? 1 : 0; // 1 para contatado, 0 para não contatado
    unset($lead['custom']); // Remover o campo 'custom' se não for necessário
}

// Ordenar os leads no PHP
usort($leads, function($a, $b) {
    if ($a['contacted'] == $b['contacted']) {
        // Se ambos têm o mesmo status de contato, ordenar por data (mais recentes primeiro)
        return strtotime($b['registro_data']) - strtotime($a['registro_data']);
    }
    // Leads não contatados (contacted = 0) antes dos contatados (contacted = 1)
    return $a['contacted'] - $b['contacted'];
});

// Formatar a data para exibição
foreach ($leads as &$lead) {
    $lead['data'] = date('d/m/Y H:i', strtotime($lead['registro_data']));
    unset($lead['registro_data']); // Remover o campo 'registro_data' se não for necessário
}

// Atualizar os leads para marcar como visualizados
$update_stmt = $conn->prepare("
    UPDATE $table_leads
    SET visualizado = 1
    WHERE prospector_id = :prospector_id AND visualizado = 0
");
$update_stmt->execute(['prospector_id' => $prospector_id]);

echo json_encode(['success' => true, 'leads' => $leads]);
?>
