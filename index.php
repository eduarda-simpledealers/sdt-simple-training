<?php

// Incluir os arquivos necessários
require_once 'include/debug.php';
require_once 'include/db_connect.php';
require_once 'include/functions.php';

// Obter os parâmetros da URL
$params = $_GET;
unset($params['consultor']);

$direct_link = '';
if (!empty($params)) {
    $direct_link = sanitize(array_keys($params)[0]);
}

$consultor = isset($_GET['consultor']) ? sanitize($_GET['consultor']) : '';

// Obter dados da concessionária com base no direct_link
$concessionaria = getConcessionaria($conn, $direct_link);

if (!$concessionaria) {
    die("Concessionária inválida ou não encontrada.");
}

// Obter prospectores (vendedores) da concessionária
$prospectores = getProspectores($conn, $concessionaria['dealer_id']);

// Procurar o prospector selecionado
$prospector_id = 0;
if ($consultor) {
    foreach ($prospectores as $p) {
        if (strtolower(str_replace(' ', '', $p['nome_comercial'])) === strtolower(str_replace(' ', '', $consultor))) {
            $prospector_id = $p['prospector_id'];
            break;
        }
    }
}

// Obter atividades disponíveis
$atividades = getTipoAtividades($conn);

// Obter dados da concessionária
$did = $concessionaria['did'] ?? '';
$dealer_id = $concessionaria['dealer_id'] ?? '';
$marca = $concessionaria['marca'] ?? '';
$concessionaria_nome = $concessionaria['concessionaria'] ?? '';
$loja = $concessionaria['loja'] ?? '';
$cnpj = $concessionaria['cnpj'] ?? '';
$endereco = $concessionaria['endereco'] ?? '';
$objetivo = $concessionaria['objetivo'] ?? '';
$status = $concessionaria['status'] ?? '';
$status_evento = $concessionaria['status_evento'] ?? '';

// Obter configurações de marca
$marca_config = getMarcaConfig($marca);

// Verificar o status da concessionária e do evento
if ($status == 'Desativado') {
    header("Location: https://simpledealers.com.br/#ConcessionariaDesativada");
    exit();
}

if ($status_evento == 'Encerrado') {
    header("Location: https://simpledealers.com.br/#EventoEncerrado");
    exit();
}

// ---------- AO REINICIAR TIRAR A ATIVIDADE E DADO DO VENDEDOR ----------
$currentUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

$parsedUrl = parse_url($currentUrl);
$queryString = $parsedUrl['query'] ?? '';

if (strpos($queryString, '&') !== false) {
    $cleanQuery = strtok($queryString, '&');
    $cleanUrl = "{$parsedUrl['scheme']}://{$parsedUrl['host']}{$parsedUrl['path']}?{$cleanQuery}";

    if ($currentUrl !== $cleanUrl) {
        header("Location: $cleanUrl");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeEvento; ?> <?php echo $marca; ?> | <?php echo $concessionaria_nome . " " . $loja;  ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo $marca_config['link_font']; ?>">
    <link rel="stylesheet" href="assets/css/estilo.css?<?php echo date('h:m:s'); ?>">
    <link href="https://cdn.jsdelivr.net/gh/hung1001/font-awesome-pro@4cac1a6/css/all.css" rel="stylesheet" type="text/css" />
    <meta name="theme-color" content="#ff5600">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link href="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/minified/introjs.min.css" rel="stylesheet">

    <link rel="stylesheet" href="./assets/css/introJS.css">

    <meta property="og:title" content="<?php echo $marca; ?> | <?php echo $concessionaria_nome . " " . $loja;  ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:image" content="https://<?php echo $dominioEvento ?>/assets/img/og.png?<?php echo date('h:m:s'); ?>" />
    <meta property="og:url" content="<?php echo $dominioEvento ?>" />

    <style>
        :root {
            --main-color: <?php echo $marca_config['color']; ?> !important;
            --main-background: <?php echo $marca_config['background']; ?> !important;
            --main-font: <?php echo $marca_config['font']; ?> !important;
            --main-font-color-light: <?php echo $marca_config['font_color_light']; ?> !important;
            --main-font-color-dark: <?php echo $marca_config['font_color_dark']; ?> !important;
            --main-border-color: <?php echo $marca_config['border_color']; ?> !important;
            --main-background-img: url("<?php echo $marca_config['background_img']; ?>");
        }
    </style>
</head>

<body>
    <main>
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 logo-cover">
                    <img class="cover-img" src="assets/dealers/logo-dealers.svg">
                </div>
                <div class="col-lg-8 cover-event">
                    <div class="cover-bg">
                        <video autoplay loop muted>
                            <source src="<?php echo $marca_config['background_video']; ?>" type="video/webm">
                            Seu navegador não suporta o formato de vídeo.
                        </video>
                    </div>
                </div>
                <div class="col-lg-4 form-game-container py-4 px-4">
                    <div class="row gy-0 gx-3">
                        <div class="col-md-12 btn-two">
                            <div class="form prospector-selector-container d-flex bx-shdw px-4 py-3 btn-one">
                            <div class="col-2 box-img-seller d-flex justify-content-center">
                                    <img class="img-seller rounded-circle" src="assets/dealers/user.png">
                                </div>
                                <div class="col-10 px-2">
                                    <select class="form-select custom-select" id="prospectorSelect">
                                        <option value="">Selecione um Prospector</option>
                                        <?php foreach ($prospectores as $prospector): $simplecard_link = $prospector['link'] ?? '#';?>
                                            <option
                                            value="<?= htmlspecialchars($prospector['prospector_id']) ?>"
                                            <?= ($prospector_id == $prospector['prospector_id']) ? 'selected' : '' ?>
                                            data-simplecard-link="<?= htmlspecialchars($simplecard_link) ?>">
                                            <?= htmlspecialchars($prospector['nome_comercial']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <h6 class="concession-name "><?php echo $concessionaria_nome; ?></h6>
                                </div>
                            </div>

                            <div id="painelPontuacao" class="mb-3" style="display: <?= $prospector_id ? 'flex' : 'none' ?>;">
                                <div class="col-4 containerPontuacao d-flex flex-column align-items-center justify-content-between">
                                    <span class=" score text-orange" id="totalPontuacao">0</span>
                                    <div class="gameTitle txt-pdr">Pontuação</div>

                                </div>
                                <div class="col-4 containerPontuacao border-dashed d-flex flex-column align-items-center justify-content-between txt-pdr"><span class="score text-orange" id="totalAtividades">0</span> Agendamentos </div>
                                <div class="col-4 containerPontuacao border-dashed d-flex flex-column align-items-center justify-content-between txt-pdr" id="btnRankingGeral"><span class="score text-orange mb-2" id="rankingPosicao">0º</span>Ranking</div>
                            </div>

                            <div class="row dealer-area" id="dealerArea" style="display: <?= $prospector_id ? 'flex' : 'none' ?>;">
                                <div class="col-6 col-md-6">
                                    <button class="btn btn-primary card text-bg-info btn-acesso-leads bx-shdw btn-three" id="btnAcessarLeads">
                                        <span id="novoLeadsCount">0 novos</span>
                                        <div class="card-body card-dealer-area" style="text-align: left; padding-left: 5px;">
                                            <h2 class="card-title flex-items text-orange">Acessar<br> meus leads</h2>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-6 col-md-6">
                                    <button class="btn btn-primary card text-bg-info btn-ranking bx-shdw btn-six" id="btnRankingGeral">
                                        <span id=""><i class="fa-regular fa-copy"></i></span>
                                        <div class="card-body card-dealer-area" style="text-align: left; padding-left: 5px;">
                                            <h2 class="card-title flex-items text-orange">Mensagem<br> Estruturada</h2>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div id="alertContainer"></div>

                            <div id="atividades" style="display: <?= $prospector_id ? 'block' : 'none' ?>;">
                                <div class="row gy-3 gx-3">

                                    <div class="col-12 col-md-6">
                                        <button type="button" id="btnAgendar" class="btn btn-primary atividade-btn card text-bg-info btn-agendar btn-four" style="width: 100%;" data-bs-toggle="modal" data-bs-target="#registrarModal">
                                            <div class="card-body flex-container" style="text-align: left; padding-left: 5px;">
                                                <i class="fa fa-user-plus flex-items" style="color:#fff !important;"></i>
                                                <h2 class="card-title flex-items">Registrar<br> Agendamento</h2>
                                            </div>
                                            <div class="w100">
                                                <h4 class="uptitle flex-items check-ponto"><i class="fa fa-check"></i></h4>
                                                <h2 class="card-title flex-items ponto">ponto registrado</h2>
                                            </div>
                                        </button>
                                    </div>

                                    <div class="col-12 col-md-6 simplecard-button">
                                        <a href="#" id="simplecardLink" class="btn btn-primary card text-bg-info bx-shdw btn-five" style="color: #ff5600 !important;background: #fff !important;border: none !important;">
                                            <div class="card-body flex-container" style="text-align: left; padding-left: 5px;">
                                                <h4 class="uptitle flex-items"><i class="fa fa-address-card f20 text-orange"></i> simple card</h4>
                                                <h2 class="card-title flex-items" style=" font-size: 13px;color: #ff5600;"><span> Acessar perfil</span><br>do vendedor</h2>
                                            </div>
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Registrar Agendamento -->
    <div class="modal fade" id="registrarModal" tabindex="-1" role="dialog" aria-labelledby="registrarModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="atividadeForm" enctype="multipart/form-data">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Registrar Agendamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div id="modalAlertContainer"></div>
                        <div id="camposAtividade">
                            <div class="modalSubtitle">Registre as informações do seu agendamento nos campos abaixo.</div>
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome:</label>
                                <input type="text" class="form-control" name="nome" required="">
                            </div>
                            <div class="mb-3">
                                <label for="sobrenome" class="form-label">Sobrenome:</label>
                                <input type="text" class="form-control" name="sobrenome" required="">
                            </div>
                            <div class="mb-3">
                                <label for="telefone" class="form-label">Telefone:</label>
                                <input type="text" class="form-control" name="telefone" required="">
                            </div>
                            <div class="mb-3">
                                <label for="observacao" class="form-label">Observação:</label>
                                <textarea class="form-control" name="observacao" placeholder="Se houver alguma observação sobre o lead, utilize esse campo." rows="3"></textarea>
                            </div>
                            <input type="hidden" name="dealer_id" value="1">
                            <input type="hidden" name="did" value="1">
                            <input type="hidden" name="marca" value="SimpleDealers">
                            <input type="hidden" name="concessionaria" value="Simple Dealers">
                            <input type="hidden" name="loja" value="Loja">
                            <input type="hidden" name="cnpj" value="123.65456.45/000">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" name="prospector_id" id="prospector_id" value="1">
                        <input type="hidden" name="atividade_id" id="atividade_id" value="1">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary button-action">Registrar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para senha -->
    <div class="modal fade" id="senhaModal" tabindex="-1" role="dialog" aria-labelledby="senhaModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="senhaForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Digite sua senha</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div id="senhaAlertContainer"></div>
                        <div class="mb-3">
                            <label for="senhaInput" class="form-label">Senha:</label>
                            <input type="password" class="form-control" id="senhaInput" name="senha" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" name="prospector_id" id="senhaProspectorId" value="<?= htmlspecialchars($prospector_id) ?>">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary button-action">Acessar Leads</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para exibir os leads -->
    <div class="modal fade show" id="leadsModal" tabindex="-1" role="dialog" aria-labelledby="leadsModalLabel" aria-modal="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Meus Leads</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="pendentes-tab" data-bs-toggle="tab" href="#pendentes" role="tab" aria-controls="pendentes" aria-selected="true">Pendentes de confirmação</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="confirmados-tab" data-bs-toggle="tab" href="#confirmados" role="tab" aria-controls="confirmados" aria-selected="false" tabindex="-1">Status confirmação</a>
                        </li>
                    </ul>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade active show" id="pendentes" role="tabpanel" aria-labelledby="pendentes-tab">
                            <div id="leadsTable_wrapper" class="dataTables_wrapper no-footer">
                                <div class="dataTables_length" id="leadsTable_length"><label>Exibir <select name="leadsTable_length" aria-controls="leadsTable" class="">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select> resultados por página</label></div>
                                <div id="leadsTable_filter" class="dataTables_filter">
                                    <label>Pesquisar<input type="search" class="" placeholder="Buscar registros" aria-controls="leadsTable">
                                    </label>
                                </div>
                                <table id="leadsTable" class="display dataTable no-footer" style="width: 100%;" aria-describedby="leadsTable_info">
                                    <!-- O DataTables irá preencher o conteúdo -->
                                    <thead>
                                        <tr>
                                            <th class="sorting_disabled" rowspan="1" colspan="1" style="width: 504px;">Nome</th>
                                            <th class="sorting_disabled" rowspan="1" colspan="1" style="width: 234px;">Telefone</th>
                                            <th class="sorting_disabled" rowspan="1" colspan="1" style="width: 199px;">Origem</th>
                                            <th class="sorting_disabled" rowspan="1" colspan="1" style="width: 93px;">Presença</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="even">
                                            <td>Cliente Nome</td>
                                            <td>
                                                <a href="#" target="_blank" class="btn btn-primary btn-sm whatsapp-button" data-lead-id="267989">
                                                    <i class="fa-brands fa-whatsapp"></i>
                                                </a> 
                                                <a href="tel:5519991234567" class="btn btn-primary btn-sm telephone-button" data-lead-id="267989">
                                                    <i class="fa fa-phone"></i> +55 (19) 99123-4567
                                                </a>
                                            </td>
                                            <td>Fluxo de Loja</td>
                                            <td>
                                                <a href="#" class="btn btn-success btn-sm thumbs_up" data-lead-id="267989">
                                                    <i class="fa fa-thumbs-up"></i>
                                                </a> 

                                                <a href="#" class="btn btn-danger btn-sm thumbs_down" data-lead-id="267989">
                                                    <i class="fa fa-thumbs-down"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="dataTables_info" id="leadsTable_info" role="status" aria-live="polite">Mostrando de 1 até 10 de 1 registros</div>
                                <div class="dataTables_paginate paging_simple_numbers" id="leadsTable_paginate">
                                    <a class="paginate_button previous disabled" aria-controls="leadsTable" aria-disabled="true" aria-role="link" data-dt-idx="previous" tabindex="-1" id="leadsTable_previous" href="#">Anterior</a>
                                    <span>
                                        <a class="paginate_button current" aria-controls="leadsTable" aria-role="link" aria-current="page" data-dt-idx="0" tabindex="0" href="#">1</a>
                                    </span>
                                    <a class="paginate_button next disabled" aria-controls="leadsTable" aria-role="link" data-dt-idx="next" tabindex="0" id="leadsTable_next" href="#">Próximo</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/intro.min.js"></script>
    <script src="/assets/js/introJs.js"></script>
    
    <script>
        let dealerId = '<?= htmlspecialchars($dealer_id) ?>';
        $(document).ready(function() {
            let prospectorId = <?= json_encode($prospector_id) ?>;
            const directLink = '<?= htmlspecialchars($direct_link) ?>';

            function atualizarURL(consultor) {
                const newURL = `${window.location.protocol}//${window.location.host}${window.location.pathname}?${encodeURIComponent(directLink)}&consultor=${encodeURIComponent(consultor)}`;
                window.history.replaceState({}, '', newURL);
            }

            function showAlert(message, type = 'success', container = '#alertContainer') {
                const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        `;
                $(container).html(alertHtml);
            }


            // Atualizar a exibição dos botões adicionais quando um prospector é selecionado
            $('#prospectorSelect').change(function() {
                const selectedId = $(this).val();
                if (selectedId) {
                    const selectedOption = $(this).find('option:selected').text();
                    const consultor = selectedOption.replace(/\s+/g, '');
                    atualizarURL(consultor);

                    prospectorId = selectedId;
                    $('#prospector_id').val(prospectorId);
                    $('#atividades').show();
                    $('#painelPontuacao').show();
                    $('#dealerArea').show();
                    carregarPontuacao(prospectorId);
                    carregarRanking(prospectorId);
                    carregarNovosLeads(prospectorId);

                    atualizarSimplecardLink($(this));
                } else {
                    $('#atividades').hide();
                    $('#painelPontuacao').hide();
                    $('#dealerArea').hide();
                    $('#totalPontuacao').text('0');
                    $('#totalAtividades').text('0');
                    $('.atividade-btn').prop('disabled', false).removeClass('btn-success').addClass('btn-primary').removeAttr('title');
                    $('#simplecardLink').attr('href', '#');
                }
            });

            // Clique no botão "Acessar meus leads"
            $('#btnAcessarLeads').click(function() {
                $('#senhaModal').modal('show');
            });

            $('#senhaForm').submit(function(e) {
                e.preventDefault();
                const senha = $('#senhaInput').val();

                $.ajax({
                    url: 'validar_senha.php',
                    method: 'POST',
                    data: {
                        prospector_id: prospectorId,
                        senha: senha
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#senhaModal').modal('hide');
                            $('#senhaForm')[0].reset();

                            $('#leadsModal').modal('show');

                        } else {
                            showAlert(response.message, 'danger', '#senhaAlertContainer');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        showAlert('Erro na requisição AJAX: ' + textStatus + ' - ' + errorThrown, 'danger', '#senhaAlertContainer');
                    }
                });
            });
        });
    </script>
    <!-- DataTables JS -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
</body>

</html>