<?php
// Definir o tipo de conteúdo como JSON
header('Content-Type: application/json');

// Incluir arquivos necessários
require_once 'include/db_connect.php';
require_once 'include/functions.php';

// Obter o ID do prospector a partir do POST
$prospector_id = isset($_POST['prospector_id']) ? intval($_POST['prospector_id']) : 0;

if ($prospector_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do prospector inválido.']);
    exit;
}

// Verificar se o campo 'visualizado' existe na tabela 'Leads'
// Caso não exista, você precisa adicionar este campo à tabela
// ALTER TABLE Leads ADD COLUMN visualizado TINYINT(1) DEFAULT 0;

// Preparar a consulta SQL para contar os novos leads
$stmt = $conn->prepare("
    SELECT COUNT(*) as novosLeads
    FROM $table_leads
    WHERE prospector_id = :prospector_id AND visualizado = 0
");
$stmt->execute(['prospector_id' => $prospector_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    $novosLeads = $result['novosLeads'];
    echo json_encode(['success' => true, 'novosLeads' => $novosLeads]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao obter novos leads.']);
}
?>
