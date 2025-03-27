<?php
header('Content-Type: application/json');

require_once 'include/db_connect.php';
require_once 'include/functions.php';

try {
    $prospector_id = isset($_POST['prospector_id']) ? intval($_POST['prospector_id']) : 0;
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';

    if ($prospector_id <= 0 || empty($senha)) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
        exit;
    }

    // Obter a senha do prospector
    $stmt = $conn->prepare("SELECT senha FROM $table_prospectores WHERE prospector_id = :prospector_id");
    $stmt->execute(['prospector_id' => $prospector_id]);
    $prospector = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prospector && password_verify($senha, $prospector['senha'])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Senha incorreta.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
