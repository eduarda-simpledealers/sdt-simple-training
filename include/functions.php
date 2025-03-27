<?php

// Função para sanitizar entradas
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Função para obter concessionária via direct_link
function getConcessionaria($conn, $direct_link)
{
    global $table_concessionaria;
    $stmt = $conn->prepare("SELECT * FROM $table_concessionaria WHERE direct_link = :direct_link");
    $stmt->execute(['direct_link' => $direct_link]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Função para obter prospectores de uma concessionária
function getProspectores($conn, $dealer_id)
{
    global $table_prospectores;
    $stmt = $conn->prepare("SELECT * FROM $table_prospectores WHERE dealer_id = :dealer_id");
    $stmt->bindParam(':dealer_id', $dealer_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para obter atividades disponíveis
function getTipoAtividades($conn)
{
    global $table_tipo_atividade;
    $stmt = $conn->prepare("SELECT * FROM $table_tipo_atividade");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para obter atividades realizadas por um prospector
function getAtividadesRealizadas($conn, $prospector_id)
{
    global $table_atividades, $table_tipo_atividade;
    $stmt = $conn->prepare("
        SELECT ta.nome
        FROM $table_atividades a
        JOIN $table_tipo_atividade ta ON a.tipo_atividade_id = ta.tipo_atividade_id
        WHERE a.prospector_id = :prospector_id
    ");
    $stmt->execute(['prospector_id' => $prospector_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}

// Função para calcular a pontuação total de um prospector
function getPontuacaoTotal($conn, $prospector_id)
{
    global $table_atividades, $table_tipo_atividade;
    $stmt = $conn->prepare("
        SELECT SUM(ta.pontuacao) as total
        FROM $table_atividades a
        JOIN $table_tipo_atividade ta ON a.tipo_atividade_id = ta.tipo_atividade_id
        WHERE a.prospector_id = :prospector_id
    ");
    $stmt->execute(['prospector_id' => $prospector_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

// Função para obter o link direto do prospector
function getSimpleCardLink($conn, $prospector_id)
{
    global $table_prospectores;
    $stmt = $conn->prepare("SELECT link FROM $table_prospectores WHERE prospector_id = :prospector_id");
    $stmt->execute(['prospector_id' => $prospector_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['link'] ?? '#'; // Padrão para '#' se não encontrado
}

// Função para sanitizar nomes para uso em pastas
function sanitize_folder_name($name)
{
    // Remove acentos
    $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
    // Remove caracteres especiais e substitui espaços por underscores
    $name = preg_replace('/[^A-Za-z0-9\-]/', '_', $name);
    // Remove múltiplos underscores
    $name = preg_replace('/_+/', '_', $name);
    // Remove underscores no início e no final
    $name = trim($name, '_');
    return $name;
}

// Função para obter o total de agendamentos de um prospector
function getTotalAgendamentos($conn, $prospector_id)
{
    global $table_atividades, $table_tipo_atividade;
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_agendamentos
        FROM $table_atividades a
        JOIN $table_tipo_atividade ta ON a.tipo_atividade_id = ta.tipo_atividade_id
        WHERE a.prospector_id = :prospector_id AND ta.nome = 'Registrar Agendamento'
    ");
    $stmt->execute(['prospector_id' => $prospector_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total_agendamentos'] ?? 0;
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


// Função para obter configurações de marca
function getMarcaConfig($marca)
{
    $configs = [
        'BYD' => [
            'background' => '#000',
            'font_color_light' => '#fff',
            'font_color_dark' => '#000',
            'border_color' => '#82828242',
            'link_font' => 'https://simplecard.online/bc/fonts/peugeot/stylesheet.css',
            'font' => 'Peugeot',
            'background_img' => '../img/evento/background.jpg',
            'color' => '#409EFF',
            'logo'  => 'assets/img/marcas/byd.svg'
        ],
        'Honda' => [
            'background' => '#000',
            'font_color_light' => '#fff',
            'font_color_dark' => '#000',
            'border_color' => '#82828242',
            'link_font' => 'https://simplecard.online/bc/fonts/peugeot/stylesheet.css',
            'font' => 'Peugeot',
            'background_img' => '../img/evento/background.jpg',
            'color' => '#CC0000',
            'logo'  => 'assets/img/marcas/honda.svg'
        ],
        'Nissan' => [
            'background' => '#000',
            'font_color_light' => '#fff',
            'font_color_dark' => '#000',
            'border_color' => '#82828242',
            'link_font' => 'https://simplecard.online/bc/fonts/peugeot/stylesheet.css',
            'font' => 'Peugeot',
            'background_img' => '../img/evento/background.jpg',
            'color' => '#A61922',
            'logo'  => 'assets/img/marcas/nissan.svg'
        ],
        'Volkswagen' => [
            'background' => '#0054a5',
            'font_color_light' => '#fff',
            'font_color_dark' => '#000',
            'border_color' => '#82828242',
            'link_font' => 'https://simplecard.online/bc/fonts/peugeot/stylesheet.css',
            'font' => 'Peugeot',
            'background_img' => '../img/evento/background.jpg',
            'background_video' => 'assets/video/nivus.webm',
            'color' => '#fff',
            'logo'  => 'assets/img/marcas/volkswagen.svg',
            'favicon'  => 'assets/img/marcas/volkswagen.svg'
        ],
        'Chevrolet' => [
            'background' => '#000',
            'font_color_light' => '#fff',
            'font_color_dark' => '#000',
            'border_color' => '#82828242',
            'link_font' => 'https://simplecard.online/bc/fonts/peugeot/stylesheet.css',
            'font' => 'Peugeot',
            'background_img' => '../img/evento/background.jpg',
            'color' => '#CD9834',
            'logo'  => 'assets/img/marcas/chevrolet.svg'
        ],
        'Jeep' => [
            'background' => '#000',
            'font_color_light' => '#fff',
            'font_color_dark' => '#000',
            'border_color' => '#82828242',
            'link_font' => 'https://simplecard.online/bc/fonts/peugeot/stylesheet.css',
            'font' => 'Peugeot',
            'background_img' => '../img/evento/background.jpg',
            'color' => '#ffba00',
            'logo'  => 'assets/img/marcas/jeep.svg'
        ],
        'Fiat' => [
            'background' => '#000934',
            'font_color_light' => '#fff',
            'font_color_dark' => '#000',
            'border_color' => '#82828242',
            'link_font' => 'https://simplecard.online/bc/fonts/peugeot/stylesheet.css',
            'font' => 'Peugeot',
            'background_img' => '../img/evento/bg-capa.jpg?cache',
            'color' => '#F41F2D',
            'logo'  => 'assets/img/marcas/fiat.svg'
        ],
        'RAM' => [
            'background' => '#000',
            'font_color_light' => '#fff',
            'font_color_dark' => '#000',
            'border_color' => '#82828242',
            'link_font' => 'https://simplecard.online/bc/fonts/peugeot/stylesheet.css',
            'font' => 'Peugeot',
            'background_img' => '../img/evento/background.jpg',
            'color' => '#b0a477',
            'logo'  => 'assets/img/marcas/ram.svg'
        ],
        'Renault' => [
            'background' => '#000',
            'font_color_light' => '#fff',
            'font_color_dark' => '#000',
            'border_color' => '#82828242',
            'link_font' => 'https://simplecard.online/bc/fonts/peugeot/stylesheet.css',
            'font' => 'Peugeot',
            'background_img' => '../img/evento/background.jpg',
            'color' => '#EEDE00',
            'logo'  => 'assets/img/marcas/renault.svg'
        ],
        'Mercedes' => [
            'background' => '#000',
            'font_color_light' => '#fff',
            'font_color_dark' => '#000',
            'border_color' => '#82828242',
            'link_font' => 'https://simplecard.online/bc/fonts/peugeot/stylesheet.css',
            'font' => 'Peugeot',
            'background_img' => '../img/evento/bg-mercedes.jpeg',
            'color' => '#fff',
            'logo'  => 'assets/img/evento/ab-mercedes.png',
            'favicon'  => 'assets/img/marcas/favicon-mercedes.jpg'
        ],
        'Volvo' => [
            'background' => '#000',
            'font_color_light' => '#fff',
            'font_color_dark' => '#000',
            'border_color' => '#82828242',
            'link_font' => 'https://simplecard.online/bc/fonts/peugeot/stylesheet.css',
            'font' => 'Peugeot',
            'background_img' => '../img/evento/bg-volvo.jpg',
            'background_video' => 'assets/video/volvo.webm',
            'color' => '#fff',
            'logo'  => 'assets/img/marcas/volvo.svg?ca',
            'favicon'  => 'assets/img/marcas/favicon-volvo.jpg'
        ],
        'GWM' => [
            'background' => '#fff',
            'font_color_light' => '#fff',
            'font_color_dark' => '#000',
            'border_color' => '#82828242',
            'link_font' => 'https://simplecard.online/bc/fonts/peugeot/stylesheet.css',
            'font' => 'Peugeot',
            'background_img' => '../img/evento/bg.jpg',
            'background_video' => 'assets/video/gwm.webm?cache',
            'color' => '#0828fd',
            'logo'  => 'assets/img/marcas/gwm.svg?caches',
            'favicon'  => 'assets/img/marcas/gwm.svg'
        ],
        'Multimarcas' => [
            'background' => '#cc092f',
            'font_color_light' => '#fff',
            'font_color_dark' => '#000',
            'border_color' => '#ffffff38',
            'link_font' => 'https://simplecard.online/bc/fonts/peugeot/stylesheet.css',
            'font' => 'Peugeot',
            'background_img' => '../img/evento/bg.jpg?cache',
            'color' => '#fff',
            'logo'  => '',
            'favicon'  => 'assets/img/evento/favicon.ico'
        ],
        'SimpleDealers' => [
            'background' => '#fff',
            'font_color_light' => '#fff',
            'font_color_dark' => '#000',
            'border_color' => '#82828242',
            'link_font' => 'https://simplecard.online/bc/fonts/peugeot/stylesheet.css',
            'font' => 'Peugeot',
            'background_img' => '../img/evento/bg.jpg',
            'background_video' => 'assets/video/evento.webm?cache',
            'color' => '#ff5600',
            'logo'  => 'assets/img/evento/dexp-icon.png?q',
            'favicon'  => 'assets/img/evento/dexp-icon.png?q'
        ],
    ];

    return $configs[$marca] ?? [
        'background' => '#000',
        'font_color_light' => '#fff',
        'font_color_dark' => '#000',
        'border_color' => '#82828242',
        'link_font' => 'https://simplecard.online/bc/fonts/peugeot/stylesheet.css',
        'font' => 'Peugeot',
        'background_img' => '../img/default.jpg',
        'color' => '#B52C01',
        'logo'  => 'assets/img/marcas/default.svg'
    ];
}

