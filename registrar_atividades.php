<?php
// Inicie o buffering de saída
ob_start();

// Incluir arquivos necessários
require_once 'include/debug.php';
require_once 'include/db_connect.php';
require_once 'include/functions.php';

// Função para responder em JSON
function respond($success, $message = '', $data = [])
{
    // Limpe o buffer de saída
    ob_clean();

    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// Função para validar tipos de imagem
function validarImagem($file)
{
    if ($file['error'] !== 0 || $file['size'] === 0) {
        return 'Erro ao carregar a imagem. Tente novamente.';
    }
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
    if (!in_array($file['type'], $allowed_types)) {
        return 'Tipo de imagem inválido. Aceitamos apenas JPEG, PNG, GIF, WebP e BMP.';
    }
    $max_size = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $max_size) {
        return 'O tamanho da imagem excede o limite de 10MB.';
    }
    if (is_uploaded_file($file['tmp_name'])) {
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            return 'Arquivo enviado não é uma imagem válida.';
        }
    } else {
        return 'O upload da imagem falhou. Arquivo inválido.';
    }
    return true;
}


// Função para fazer upload e retornar o caminho do arquivo
function uploadImagem($file, $target_dir = "uploads/")
{
    $validation = validarImagem($file);
    if ($validation !== true) {
        return $validation;
    }
    // Criar diretório se não existir
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    $filename = uniqid() . "_" . basename($file['name']);
    $target_file = $target_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $target_file;
    } else {
        return 'Erro ao mover o arquivo para o diretório de destino.';
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = isset($_POST['action']) ? sanitize($_POST['action']) : '';
        $prospector_id = isset($_POST['prospector_id']) ? intval($_POST['prospector_id']) : 0;

        if ($prospector_id <= 0) {
            respond(false, 'ID do prospector inválido.');
        }

        if ($action === 'atividade') {
            $atividade_id = isset($_POST['atividade_id']) ? intval($_POST['atividade_id']) : 0;

            if ($atividade_id <= 0) {
                respond(false, 'ID da atividade inválido.');
            }

            // Obter informações da atividade
            $stmt_atividade = $conn->prepare("SELECT * FROM $table_tipo_atividade WHERE tipo_atividade_id = :atividade_id");
            $stmt_atividade->execute(['atividade_id' => $atividade_id]);
            $atividade = $stmt_atividade->fetch(PDO::FETCH_ASSOC);

            if (!$atividade) {
                respond(false, 'Atividade não encontrada.');
            }

            $tipo_atividade = $atividade['tipo'];
            $nome_atividade = $atividade['nome'];
            $pontuacao = $atividade['pontuacao'];

            $custom = [];
            $custom['atividade'] = $nome_atividade;

            // Verificar se a atividade permite múltiplos registros
            $permitirMultiplo = in_array($atividade_id, [1, 2]); // IDs das atividades que permitem múltiplos registros

            if (!$permitirMultiplo) {
                // Verificar se o prospector já realizou essa atividade
                $stmt_verifica = $conn->prepare("
                    SELECT COUNT(*) FROM $table_atividades
                    WHERE prospector_id = :prospector_id AND tipo_atividade_id = :atividade_id
                ");
                $stmt_verifica->execute([
                    'prospector_id' => $prospector_id,
                    'atividade_id' => $atividade_id
                ]);
                $count = $stmt_verifica->fetchColumn();

                if ($count > 0) {
                    respond(false, 'Você já registrou esta atividade e não pode registrá-la novamente.');
                }
            }

            // Campos específicos com base na atividade
            switch ($nome_atividade) {

                case 'Registrar Agendamento':
                    // Permite múltiplos registros
                    $nome = isset($_POST['nome']) ? sanitize($_POST['nome']) : '';
                    $sobrenome = isset($_POST['sobrenome']) ? sanitize($_POST['sobrenome']) : '';
                    $raw_telefone = isset($_POST['telefone']) ? $_POST['telefone'] : '';
                    $observacao = isset($_POST['observacao']) ? sanitize($_POST['observacao']) : '';
                    $did = isset($_POST['did']) ? sanitize($_POST['did']) : '';
                    $dealer_id = isset($_POST['dealer_id']) ? sanitize($_POST['dealer_id']) : '';
                    $marca = isset($_POST['marca']) ? sanitize($_POST['marca']) : '';
                    $concessionaria_nome = isset($_POST['concessionaria']) ? sanitize($_POST['concessionaria']) : '';
                    $loja = isset($_POST['loja']) ? sanitize($_POST['loja']) : '';
                    $cnpj = isset($_POST['cnpj']) ? sanitize($_POST['cnpj']) : '';

                    if (empty($nome) || empty($sobrenome) || empty($raw_telefone)) {
                        respond(false, 'Por favor, preencha todos os campos obrigatórios.');
                    }

                    $telefone = normalizarTelefone($raw_telefone);

                    // Verificar se já existe um lead com o mesmo telefone e dealer_id
                    $stmt_verifica_lead = $conn->prepare("
                        SELECT lead_id FROM $table_leads
                        WHERE telefone = :telefone AND dealer_id = :dealer_id
                    ");
                    $stmt_verifica_lead->execute([
                        'telefone' => $telefone,
                        'dealer_id' => $dealer_id
                    ]);
                    $lead_existente = $stmt_verifica_lead->fetch(PDO::FETCH_ASSOC);

                    if ($lead_existente) {
                        respond(false, 'Cliente já recebeu o ingresso.');
                    }

                    // Dados adicionais para Leads (sem 'observacao')
                    $custom_leads = [
                        'loja' => $loja,
                        'cnpj' => $cnpj,
                        'did' => $did,
                        'marca' => $marca,
                        'concessionaria_nome' => $concessionaria_nome
                    ];

                    // Inserir lead na tabela Leads
                    $stmt_insert_lead = $conn->prepare("
                        INSERT INTO $table_leads (dealer_id, prospector_id, nome, sobrenome, telefone, typelead, lgpd, custom)
                        VALUES (:dealer_id, :prospector_id, :nome, :sobrenome, :telefone, :typelead, :lgpd, :custom)
                    ");
                    $stmt_insert_lead->execute([
                        'dealer_id' => $did,
                        'prospector_id' => $prospector_id,
                        'nome' => $nome,
                        'sobrenome' => $sobrenome,
                        'telefone' => $telefone,
                        'typelead' => 'LP Game',
                        'lgpd' => 1,
                        'custom' => null
                    ]);
                    $lead_id = $conn->lastInsertId();

                    // Dados adicionais para Atividades (sem 'observacao')
                    $custom_atividades = [
                        'loja' => $loja,
                        'cnpj' => $cnpj,
                        'did' => $did,
                        'marca' => $marca,
                        'concessionaria_nome' => $concessionaria_nome
                    ];

                    // Inserir atividade na tabela Atividades
                    $stmt_insert_atividade = $conn->prepare("
                        INSERT INTO $table_atividades (prospector_id, tipo_atividade_id, lead_id, custom, observacao)
                        VALUES (:prospector_id, :tipo_atividade_id, :lead_id, :custom, :observacao)
                    ");
                    $stmt_insert_atividade->execute([
                        'prospector_id' => $prospector_id,
                        'tipo_atividade_id' => $atividade_id,
                        'lead_id' => $lead_id,
                        'custom' => json_encode($custom_atividades),
                        'observacao' => $observacao
                    ]);

                    // Enviar webhook com os dados do lead
                    $webhook_url = $duotalk; // Substitua pela URL do seu webhook

                    $webhook_data = [
                        'cliente_nome' => $nome . ' ' . $sobrenome,
                        'phone' => $telefone,
                        'link_convite' => 'https://'.$dominioEvento.'/convite/?lead_id='.$lead_id,
                    ];

                    // Enviar dados via webhook usando cURL
                    $ch = curl_init($webhook_url);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhook_data));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $webhook_response = curl_exec($ch);
                    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    // Opcional: Verificar a resposta do webhook
                    if ($http_status != 200) {
                        // Você pode registrar o erro ou tomar alguma ação
                    }

                    respond(true, 'Atividade registrada com sucesso.');
                    break;

                case 'Registrar Venda':
                    // Permite múltiplos registros
                    $nome = isset($_POST['nome']) ? sanitize($_POST['nome']) : '';
                    $sobrenome = isset($_POST['sobrenome']) ? sanitize($_POST['sobrenome']) : '';

                    if (empty($nome) || empty($sobrenome)) {
                        respond(false, 'Por favor, preencha todos os campos obrigatórios.');
                    }

                    $custom['nome'] = $nome;
                    $custom['sobrenome'] = $sobrenome;

                    // Processar upload de imagem, se houver
                    $arquivo = '';
                    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $upload_result = uploadImagem($_FILES['imagem']);
                        if (is_string($upload_result) && strpos($upload_result, 'uploads/') === 0) {
                            $arquivo = $upload_result;
                        } else {
                            respond(false, $upload_result);
                        }
                    }

                    // Inserir atividade na tabela Atividades (removemos a inserção na tabela Leads)
                    $stmt_insert_atividade = $conn->prepare("
        INSERT INTO $table_atividades (prospector_id, tipo_atividade_id, custom, arquivo)
        VALUES (:prospector_id, :tipo_atividade_id, :custom, :arquivo)
    ");
                    $stmt_insert_atividade->execute([
                        'prospector_id'     => $prospector_id,
                        'tipo_atividade_id' => $atividade_id,
                        'custom'            => json_encode($custom),
                        'arquivo'           => $arquivo
                    ]);


                    break;

                case 'Registrar Postagem Instagram / Facebook':
                case 'Dia 1 | Print dos Stories':
                case 'Dia 2 | Print dos Stories':
                case 'Dia 3 | Print dos Stories':
                case 'Print do Video do convidado':
                case 'Evidencia de lista de Transmissão':
                    // Permite apenas um registro (já verificado)

                    // Processar upload de imagem
                    $arquivo = '';
                    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $upload_result = uploadImagem($_FILES['imagem']);
                        if (is_string($upload_result) && strpos($upload_result, 'uploads/') === 0) {
                            $arquivo = $upload_result;
                        } else {
                            respond(false, $upload_result);
                        }
                    } else {
                        respond(false, 'Por favor, envie a imagem solicitada.');
                    }

                    // Inserir atividade na tabela Atividades
                    $stmt_insert_atividade = $conn->prepare("
                        INSERT INTO $table_atividades (prospector_id, tipo_atividade_id, custom, arquivo)
                        VALUES (:prospector_id, :tipo_atividade_id, :custom, :arquivo)
                    ");
                    $stmt_insert_atividade->execute([
                        'prospector_id' => $prospector_id,
                        'tipo_atividade_id' => $atividade_id,
                        'custom' => json_encode($custom),
                        'arquivo' => $arquivo
                    ]);

                    break;

                default:
                    respond(false, 'Atividade não reconhecida.');
            }

            respond(true, 'Atividade registrada com sucesso.');
        } elseif ($action === 'pontuacao_total') {
            $prospector_id = isset($_POST['prospector_id']) ? intval($_POST['prospector_id']) : 0;
            if ($prospector_id > 0) {
                // Calcula a pontuação total do prospector
                $stmt_pontuacao = $conn->prepare("
                    SELECT SUM(ta.pontuacao) as total
                    FROM $table_atividades a
                    JOIN $table_tipo_atividade ta ON a.tipo_atividade_id = ta.tipo_atividade_id
                    WHERE a.prospector_id = :prospector_id
                ");
                $stmt_pontuacao->execute(['prospector_id' => $prospector_id]);
                $result = $stmt_pontuacao->fetch(PDO::FETCH_ASSOC);
                $total_pontuacao = $result['total'] ?? 0;

                // Calcula o total de agendamentos do prospector
                $stmt_agendamentos = $conn->prepare("
                    SELECT COUNT(*) as total_agendamentos
                    FROM $table_atividades a
                    JOIN $table_tipo_atividade ta ON a.tipo_atividade_id = ta.tipo_atividade_id
                    WHERE a.prospector_id = :prospector_id AND ta.nome = 'Registrar Agendamento'
                ");
                $stmt_agendamentos->execute(['prospector_id' => $prospector_id]);
                $result_agendamentos = $stmt_agendamentos->fetch(PDO::FETCH_ASSOC);
                $total_agendamentos = $result_agendamentos['total_agendamentos'] ?? 0;

                // Retorna o total de pontuação e total de agendamentos
                respond(true, '', [
                    'total' => $total_pontuacao,
                    'total_agendamentos' => $total_agendamentos
                ]);
            } else {
                respond(false, 'ID do prospector inválido.');
            }
        } elseif ($action === 'status_atividades') {
            $prospector_id = isset($_POST['prospector_id']) ? intval($_POST['prospector_id']) : 0;
            if ($prospector_id > 0) {
                $stmt_atividades = $conn->prepare("
                    SELECT ta.nome FROM $table_atividades a
                    JOIN $table_tipo_atividade ta ON a.tipo_atividade_id = ta.tipo_atividade_id
                    WHERE a.prospector_id = :prospector_id
                ");
                $stmt_atividades->execute(['prospector_id' => $prospector_id]);
                $atividades_feitas = $stmt_atividades->fetchAll(PDO::FETCH_COLUMN, 0);

                respond(true, '', ['atividades' => $atividades_feitas]);
            } else {
                respond(false, 'ID do prospector inválido.');
            }
        } else {
            respond(false, 'Ação inválida.');
        }
    } else {
        respond(false, 'Método de requisição inválido.');
    }
} catch (Exception $e) {
    // Limpe o buffer e responda com o erro
    ob_clean();
    respond(false, 'Erro inesperado: ' . $e->getMessage());
}
