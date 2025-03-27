<?php
require_once 'include/db_connect.php';
require_once 'include/functions.php';

$prospector_id = isset($_POST['prospector_id']) ? intval($_POST['prospector_id']) : 0;

if ($prospector_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do prospector inválido.']);
    exit;
}

try {
    // Obter o dealer_id do prospector
    $stmt_dealer = $conn->prepare("SELECT dealer_id FROM $table_prospectores WHERE prospector_id = :prospector_id");
    $stmt_dealer->execute(['prospector_id' => $prospector_id]);
    $dealer = $stmt_dealer->fetch(PDO::FETCH_ASSOC);

    if (!$dealer) {
        echo json_encode(['success' => false, 'message' => 'Prospector não encontrado.']);
        exit;
    }

    $dealer_id = $dealer['dealer_id'];

    // Ranking geral
    $stmt_geral = $conn->prepare("
        SELECT p.prospector_id, p.nome_comercial, SUM(ta.pontuacao) as total_pontos
        FROM $table_atividades a
        JOIN $table_prospectores p ON a.prospector_id = p.prospector_id
        JOIN $table_tipo_atividade ta ON a.tipo_atividade_id = ta.tipo_atividade_id
        GROUP BY p.prospector_id
        ORDER BY total_pontos DESC
    ");
    $stmt_geral->execute();
    $ranking_geral = $stmt_geral->fetchAll(PDO::FETCH_ASSOC);

    // Encontrar a posição do prospector no ranking geral
    $posicao_geral = null;
    foreach ($ranking_geral as $index => $prospector) {
        if ($prospector['prospector_id'] == $prospector_id) {
            $posicao_geral = $index + 1;
            break;
        }
    }

    // Ranking da concessionária
    $stmt_concessionaria = $conn->prepare("
        SELECT p.prospector_id, p.nome_comercial, SUM(ta.pontuacao) as total_pontos
        FROM $table_atividades a
        JOIN $table_prospectores p ON a.prospector_id = p.prospector_id
        JOIN $table_tipo_atividade ta ON a.tipo_atividade_id = ta.tipo_atividade_id
        WHERE p.dealer_id = :dealer_id
        GROUP BY p.prospector_id
        ORDER BY total_pontos DESC
    ");
    $stmt_concessionaria->execute(['dealer_id' => $dealer_id]);
    $ranking_concessionaria = $stmt_concessionaria->fetchAll(PDO::FETCH_ASSOC);

    // Encontrar a posição do prospector no ranking da concessionária
    $posicao_concessionaria = null;
    foreach ($ranking_concessionaria as $index => $prospector) {
        if ($prospector['prospector_id'] == $prospector_id) {
            $posicao_concessionaria = $index + 1;
            break;
        }
    }

    // Se as posições forem null, definir como '-'
    if (is_null($posicao_geral)) {
        $posicao_geral = '-';
    }
    if (is_null($posicao_concessionaria)) {
        $posicao_concessionaria = '-';
    }

    echo json_encode([
        'success' => true,
        'rankingGeral' => $posicao_geral,
        'rankingConcessionaria' => $posicao_concessionaria
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>
