<?php
// Incluir arquivos necessários e estabelecer conexão com o banco de dados
require_once 'include/db_connect.php'; // Inclui a conexão com o banco e as variáveis de tabela

try {
    // A conexão com o banco de dados já foi estabelecida em $conn pelo db_connect.php
    // As variáveis de tabela estão disponíveis: $table_concessionaria, $table_prospectores, etc.

    // Receber os dados enviados via POST
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $sobrenome = isset($_POST['sobrenome']) ? trim($_POST['sobrenome']) : '';
    $raw_telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
    $selleremail = isset($_POST['selleremail']) ? trim($_POST['selleremail']) : '';

    // Validar os campos obrigatórios
    if (empty($nome) || empty($sobrenome) || empty($raw_telefone) || empty($selleremail)) {
        die("Por favor, preencha todos os campos obrigatórios.");
    }

    // Função para normalizar o número de telefone
    function normalizarTelefone($raw_telefone)
    {
        $telefone = preg_replace('/\D/', '', $raw_telefone);
        $ddi = "55";
        if (substr($telefone, 0, 2) != '55') {
            $telefone = '55' . $telefone;
        }
        if (substr($telefone, 2, 1) == '0') {
            $ddd = substr($telefone, 3, 2);
            $numero = substr($telefone, 5);
        } else {
            $ddd = substr($telefone, 2, 2);
            $numero = substr($telefone, 4);
        }
        return '+' . $ddi . $ddd . $numero;
    }

    $telefone = normalizarTelefone($raw_telefone);

    // Obter informações do prospector usando o selleremail
    $stmt = $conn->prepare("SELECT prospector_id, dealer_id FROM $table_prospectores WHERE email = :email");
    $stmt->execute(['email' => $selleremail]);
    $prospector = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prospector) {
        die("Prospector não encontrado.");
    }

    $prospector_id = $prospector['prospector_id'];
    $dealer_id = $prospector['dealer_id'];

    // Verificar se já existe um lead com o mesmo telefone e dealer_id
    $stmt = $conn->prepare("SELECT lead_id FROM $table_leads WHERE telefone = :telefone AND dealer_id = :dealer_id");
    $stmt->execute(['telefone' => $telefone, 'dealer_id' => $dealer_id]);
    $existing_lead = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_lead) {
        // Lead já existe, redirecionar para a página de sucesso com o lead_id existente
        $lead_id = $existing_lead['lead_id'];
        header("Location: https://".$dominioEvento."/convite/?lead_id=$lead_id");
        exit;
    }

    // Inserir novo lead na tabela Leads
    $stmt = $conn->prepare("
        INSERT INTO $table_leads (dealer_id, prospector_id, nome, sobrenome, telefone, typelead, lgpd)
        VALUES (:dealer_id, :prospector_id, :nome, :sobrenome, :telefone, :typelead, :lgpd)
    ");
    $stmt->execute([
        'dealer_id' => $dealer_id,
        'prospector_id' => $prospector_id,
        'nome' => $nome,
        'sobrenome' => $sobrenome,
        'telefone' => $telefone,
        'typelead' => 'Card',
        'lgpd' => 1,
    ]);

    $lead_id = $conn->lastInsertId();

    // Opcional: Inserir uma atividade na tabela Atividades
    $tipo_atividade_id = 1; // Substitua pelo ID correto do tipo de atividade

    $stmt = $conn->prepare("
        INSERT INTO $table_atividades (prospector_id, tipo_atividade_id, lead_id)
        VALUES (:prospector_id, :tipo_atividade_id, :lead_id)
    ");
    $stmt->execute([
        'prospector_id' => $prospector_id,
        'tipo_atividade_id' => $tipo_atividade_id,
        'lead_id' => $lead_id,
    ]);

    // Enviar webhook com os dados do lead
    $webhook_url = $duotalk; // Substitua pela URL do seu webhook

    // Obter informações da concessionária para o webhook
    $stmt = $conn->prepare("SELECT marca, concessionaria, loja, endereco FROM $table_concessionaria WHERE dealer_id = :dealer_id");
    $stmt->execute(['dealer_id' => $dealer_id]);
    $concessionaria_info = $stmt->fetch(PDO::FETCH_ASSOC);

    $webhook_data = [
        'cliente_nome' => $nome . ' ' . $sobrenome,
        'phone' => $telefone,
        'link_convite' => 'https://'.$dominioEvento.'/convite/?lead_id='.$lead_id,
    ];

    // Função para enviar o webhook
    function sendWebhook($webhook_url, $webhook_data) {
        $jsonData = json_encode($webhook_data);

        $ch = curl_init($webhook_url);

        // Configurar cURL para realizar uma requisição POST
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

        // Retornar a resposta como string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Definir cabeçalhos da requisição
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ));

        // Executar a requisição
        $response = curl_exec($ch);

        // Capturar informações sobre a requisição
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);

        curl_close($ch);

        if ($response === false) {
            throw new Exception('Erro cURL: ' . $curl_error);
        }

        if ($http_status != 200) {
            throw new Exception('Erro ao enviar webhook. Status HTTP: ' . $http_status . '. Resposta: ' . $response);
        }

        return $response;
    }

    try {
        // Enviar o webhook
        sendWebhook($webhook_url, $webhook_data);
    } catch (Exception $e) {
        // Registrar o erro e continuar
        error_log("Erro ao enviar webhook: " . $e->getMessage());
        // Você pode decidir se deseja interromper a execução ou continuar
    }

    // Redirecionar para a página de sucesso com o lead_id
    header("Location: https://".$dominioEvento."/convite/?lead_id=$lead_id");
    exit;

} catch (Exception $e) {
    // Em caso de erro, registrar o erro e exibir uma mensagem amigável
    error_log("Erro: " . $e->getMessage());
    die("Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente mais tarde.");
}
?>
