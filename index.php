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

        /* Estilo para o botão quando o lead já foi contatado */
        .btn.contacted {
            background-color: #6c757d;
            /* Cor "secondary" do Bootstrap */
            border-color: #6c757d;
            color: #fff;
            /* Cor do texto */
        }

        /* Ajuste para o ícone dentro do botão */
        .btn.contacted i.fa-check {
            margin-right: 5px;
        }

        /* Opcional: se quiser mudar a opacidade */
        .btn.contacted {
            opacity: 1;
            /* Se preferir manter a opacidade normal */
        }
        video {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          object-fit: cover;
          z-index: -1; 
        }
        .dataTables_wrapper{
          padding-top:30px;
        }
        #myTab{
            background: #f3f3f3;
            padding: 5px;
            border-radius: 10px;
        }
        .nav-tabs .nav-item.show .nav-link, .nav-tabs .nav-link.active{
            border-radius: 5px !important;
            border: none;
            padding: 15px;
            width: 100%;
            display: block;
        }
        .nav-tabs .nav-link{
            border-radius: 5px !important;
            border: none;
            padding: 15px;
            width: 100%;
            display: block;
            color: #000;
        }
        li.nav-item{
            width: 50%;
            text-align: center;
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
                <div class="col-lg-4 form-game-container" style="">
                    <div class="row gy-0 gx-3">
                        <div class="col-md-12">
                            <div class="logo-event">
                                <img width="50%" src="assets/img/evento/dexp-icon.png">
                            </div>

                            <div id="painelPontuacao" class="mb-4" style="display: <?= $prospector_id ? 'flex' : 'none' ?>;">
                                <img class="col-12 logo-marca" src="<?php echo htmlspecialchars($marca_config['logo']); ?>?cache">
                                <!--<h2 class="text-white padding-15"><?php echo $concessionaria_nome ?><br></h2>-->
                                <div class="col-4 containerPontuacao">
                                    <div class="gameTitle"><i class="fa fa-gamepad color"></i>Pontuação</div>
                                    <span class="color" id="totalPontuacao">0</span>
                                </div>
                                <div class="text-white col-4 containerPontuacao text-orange">Agendamentos <span style="font-size: 53px;" id="totalAtividades">0</span></div>
                            </div>

                            <div class="row dealer-area" id="dealerArea" style="display: <?= $prospector_id ? 'flex' : 'none' ?>;">
                                <div class="col-6 col-md-6">
                                    <button class="btn btn-primary card text-bg-info btn-acesso-leads" id="btnAcessarLeads">
                                        <span id="novoLeadsCount">0 novos</span>
                                        <div class="card-body card-dealer-area" style="text-align: left; padding-left: 5px;">
                                            <i class="fa fa-users flex-items"></i>
                                            <h2 class="card-title flex-items">Acessar<br> meus leads</h2>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-6 col-md-6">
                                    <button class="btn btn-primary card text-bg-info btn-ranking" id="btnRankingGeral">
                                        <span id="rankingPosicao">--º</span>
                                        <div class="card-body card-dealer-area" style="text-align: left; padding-left: 5px;">
                                            <i class="fa fa-list-ol flex-items"></i>
                                            <h2 class="card-title flex-items">Ranking<br> Geral</h2>
                                        </div>
                                    </button>
                                </div>
                            </div>


                            <div class="titles mt-4">
                                <h5 class="">Concessionária</h5>
                                <h4 class="text-main-color"><?php echo $concessionaria_nome; ?></h4>
                            </div>
                            <div class="form">
                                <select class="form-select custom-select" id="prospectorSelect">
                                    <option value="">Selecione um Prospector</option>
                                    <?php foreach ($prospectores as $prospector):
                                        $simplecard_link = $prospector['link'] ?? '#';
                                    ?>
                                        <option
                                            value="<?= htmlspecialchars($prospector['prospector_id']) ?>"
                                            <?= ($prospector_id == $prospector['prospector_id']) ? 'selected' : '' ?>
                                            data-simplecard-link="<?= htmlspecialchars($simplecard_link) ?>">
                                            <?= htmlspecialchars($prospector['nome_comercial']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div id="alertContainer"></div>

                            <div id="atividades" style="display: <?= $prospector_id ? 'block' : 'none' ?>;">
                                <div class="row gy-3 gx-3">

                                    <div class="col-12 col-md-6">
                                        <button class="btn btn-primary atividade-btn card text-bg-info pulsedealers btn-agendar" style="width: 100%;" data-atividade="Registrar Agendamento" data-atividade-id="1">
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
                                        <a href="#" id="simplecardLink" class="btn btn-primary card text-bg-info" style="color: #ff5600 !important;background: #fff !important;border: solid 3px #ff5600;">
                                            <div class="card-body flex-container" style="text-align: left; padding-left: 5px;">
                                                <h4 class="uptitle flex-items"><i class="fa fa-address-card f20 text-orange"></i> simple card</h4>
                                                <h2 class="card-title flex-items" style=" font-size: 13px;color: #ff5600;"><span> Acessar perfil</span><br>do vendedor</h2>
                                            </div>
                                        </a>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <a href="http://simpledealers.com.br" class="footer-logo">
                                            <img width="250" src="assets/img/power-by-simple-dealers.svg" style="margin: 0 auto;filter: brightness(0.2);" alt="Powered by Simple Dealers">
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

    <div class="modal fade" id="atividadeModal" tabindex="-1" role="dialog" aria-labelledby="atividadeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="atividadeForm" enctype="multipart/form-data">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Registrar Atividade</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div id="modalAlertContainer"></div>
                        <div id="camposAtividade">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" name="prospector_id" id="prospector_id" value="<?= htmlspecialchars($prospector_id) ?>">
                        <input type="hidden" name="atividade_id" id="atividade_id" value="">
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
    <div class="modal fade" id="leadsModal" tabindex="-1" role="dialog" aria-labelledby="leadsModalLabel" aria-hidden="true">
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
                            <a class="nav-link" id="confirmados-tab" data-bs-toggle="tab" href="#confirmados" role="tab" aria-controls="confirmados" aria-selected="false">Confirmados para o evento</a>
                        </li>
                    </ul>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="pendentes" role="tabpanel" aria-labelledby="pendentes-tab">
                            <table id="leadsTable" class="display" style="width:100%;">
                                <!-- O DataTables irá preencher o conteúdo -->
                            </table>
                        </div>
                        <div class="tab-pane fade" id="confirmados" role="tabpanel" aria-labelledby="confirmados-tab">
                            <p>Conteúdo de confirmados</p>
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
    
    
    

    <script>
        let dealerId = '<?= htmlspecialchars($dealer_id) ?>';
        $(document).ready(function() {
            let prospectorId = <?= json_encode($prospector_id) ?>;
            const directLink = '<?= htmlspecialchars($direct_link) ?>';

            function atualizarURL(consultor) {
                const newURL = `${window.location.protocol}//${window.location.host}${window.location.pathname}?${encodeURIComponent(directLink)}&consultor=${encodeURIComponent(consultor)}`;
                window.history.replaceState({}, '', newURL);
            }

            function carregarStatusAtividades(prospectorId) {
                $.ajax({
                    url: 'registrar_atividades.php',
                    method: 'POST',
                    data: {
                        action: 'status_atividades',
                        prospector_id: prospectorId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const atividadesFeitas = response.atividades;
                            $('.atividade-btn').each(function() {
                                const atividadeId = $(this).data('atividade-id');
                                const atividadeNome = $(this).data('atividade');

                                if ([3, 4, 5, 6, 7, 8].includes(parseInt(atividadeId))) {
                                    if (atividadesFeitas.includes(atividadeNome)) {
                                        $(this).prop('disabled', true).removeClass('btn-primary').addClass('btn-success');
                                        $(this).attr('title', 'Atividade já realizada.');
                                        $(this).find('.ponto').text('ponto registrado');
                                    } else {
                                        $(this).prop('disabled', false).removeClass('btn-success').addClass('btn-primary');
                                        $(this).removeAttr('title');
                                        $(this).find('.ponto').text('ponto registrado');
                                    }
                                }
                            });
                        } else {
                            showAlert('Erro ao carregar status das atividades: ' + response.message, 'danger');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        showAlert('Erro na requisição AJAX: ' + textStatus + ' - ' + errorThrown, 'danger');
                    }
                });
            }

            function carregarPontuacao(prospectorId) {
                $.ajax({
                    url: 'registrar_atividades.php',
                    method: 'POST',
                    data: {
                        action: 'pontuacao_total',
                        prospector_id: prospectorId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#totalPontuacao').text(response.total);
                            $('#totalAtividades').text(response.total_agendamentos);
                        } else {
                            showAlert('Erro ao carregar pontuação total: ' + response.message, 'danger');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        showAlert('Erro na requisição AJAX: ' + textStatus + ' - ' + errorThrown, 'danger');
                    }
                });
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

            $('#atividadeModal').on('show.bs.modal', function() {
                $('#modalAlertContainer').html('');
            });

            function atualizarSimplecardLink(prospectorSelect) {
                const selectedOption = prospectorSelect.find('option:selected');
                const simplecardLink = selectedOption.data('simplecard-link') || '#';
                $('#simplecardLink').attr('href', simplecardLink);
            }

            if (prospectorId > 0) {
                carregarStatusAtividades(prospectorId);
                carregarPontuacao(prospectorId);
                const prospectorSelect = $('#prospectorSelect');
                atualizarSimplecardLink(prospectorSelect);
                $('#painelPontuacao').show();
                $('#dealerArea').show();
                carregarRanking(prospectorId);
                carregarNovosLeads(prospectorId);
            }

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
                    carregarStatusAtividades(prospectorId);
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

            $('.atividade-btn').click(function() {
                const atividadeNome = $(this).data('atividade');
                const atividadeId = $(this).data('atividade-id');
                $('#atividade_id').val(atividadeId);
                $('#atividadeModal .modal-title').text('' + atividadeNome);
                let campos = '';

                // Definir campos com base na atividade selecionada
                switch (atividadeNome) {
                    case 'Registrar Agendamento':
                        campos = `
                    <div class="modalSubtitle">Registre as informações do seu agendamento nos campos abaixo.</div>
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome:</label>
                        <input type="text" class="form-control" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="sobrenome" class="form-label">Sobrenome:</label>
                        <input type="text" class="form-control" name="sobrenome" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone:</label>
                        <input type="text" class="form-control" name="telefone" required>
                    </div>
                    <div class="mb-3">
                        <label for="observacao" class="form-label">Observação:</label>
                        <textarea class="form-control" name="observacao" placeholder="Se houver alguma observação sobre o lead, utilize esse campo." rows="3"></textarea>
                    </div>
                    <input type="hidden" name="dealer_id" value="<?= htmlspecialchars($dealer_id) ?>">
                    <input type="hidden" name="did" value="<?= htmlspecialchars($did) ?>">
                    <input type="hidden" name="marca" value="<?= htmlspecialchars($marca) ?>">
                    <input type="hidden" name="concessionaria" value="<?= htmlspecialchars($concessionaria_nome) ?>">
                    <input type="hidden" name="loja" value="<?= htmlspecialchars($loja) ?>">
                    <input type="hidden" name="cnpj" value="<?= htmlspecialchars($cnpj) ?>">
                `;
                        break;
                    case 'Registrar Venda':
                        campos = `
                    <div class="modalSubtitle">Registre suas vendas e brilhe!<br> Preencha as informações abaixo.</div>
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome:</label>
                        <input type="text" class="form-control" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="sobrenome" class="form-label">Sobrenome:</label>
                        <input type="text" class="form-control" name="sobrenome" required>
                    </div>
                    <div class="mb-3">
                        <label for="imagem" class="form-label">Evidência em Imagem:</label>
                        <input type="file" class="form-control-file" name="imagem" accept="image/*" required>
                    </div>
                `;
                        break;
                    default:
                        campos = `
                    <div class="modalSubtitle">Envie a evidência solicitada para registrar esta atividade.</div>
                    <div class="mb-3">
                        <label for="imagem" class="form-label">Upload de Imagem:</label>
                        <input type="file" class="form-control-file" name="imagem" accept="image/*" required>
                    </div>
                `;
                }

                $('#camposAtividade').html(campos);
                $('#atividadeModal').modal('show');
            });

            $('#atividadeForm').submit(function(e) {
                e.preventDefault();
                const atividadeId = $('#atividade_id').val();
                let formData = new FormData(this);

                formData.append('action', 'atividade');
                formData.append('atividade_id', atividadeId);

                const submitButton = $(this).find('button[type="submit"]');
                submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Registrando...');

                $.ajax({
                    url: 'registrar_atividades.php',
                    method: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showAlert(response.message, 'success', '#alertContainer');
                            $('#atividadeModal').modal('hide');
                            carregarStatusAtividades(prospectorId);
                            carregarPontuacao(prospectorId);
                            $('#atividadeForm')[0].reset();
                            $('#camposAtividade').html('');
                            $('#modalAlertContainer').html('');
                        } else {
                            showAlert('Erro: ' + response.message, 'danger', '#modalAlertContainer');
                        }
                        submitButton.prop('disabled', false).html('Registrar');
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        showAlert('Erro na requisição AJAX: ' + textStatus + ' - ' + errorThrown, 'danger', '#modalAlertContainer');
                        submitButton.prop('disabled', false).html('Registrar');
                    }
                });
            });

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
                    carregarStatusAtividades(prospectorId);
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

// Submissão do formulário de senha
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
                carregarLeads(prospectorId);
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

// Função para carregar os leads do prospector
function carregarLeads(prospectorId) {
    const nomeEvento = '<?= htmlspecialchars($nomeEvento) ?>'; // Nome do evento
    const dominioEvento = '<?= htmlspecialchars($dominioEvento) ?>'; // Domínio do evento

    $.ajax({
        url: 'obter_leads.php',
        method: 'POST',
        data: {
            prospector_id: prospectorId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const leads = response.leads;
                let dataSet = [];
                leads.forEach(function(lead) {
                    // Criar a mensagem personalizada
                    const message = `Olá *${lead.nome_completo}*,\n\nVocê foi convidado para participar do Evento *${nomeEvento}*.\n\nEsse é o link do seu convite ➽ https://${dominioEvento}/convite/?lead_id=${lead.lead_id}`;

                    // Codificar a mensagem
                    const encodedMessage = encodeURIComponent(message);

                    // Gerar o link do WhatsApp
                    const whatsappLink = `https://wa.me/${lead.telefone.replace(/\D/g, '')}?text=${encodedMessage}`;

                    // Determinar o texto e o ícone do botão com base no status de contato
                    let buttonLabel;
                    if (lead.contacted) {
                        buttonLabel = '<i class="fa fa-check"></i> Enviado';
                    } else {
                        buttonLabel = 'Reenviar Convite';
                    }

                    const buttonClass = lead.contacted ? 'btn btn-success btn-sm contacted' : 'btn btn-primary btn-sm';

                    
                    // Criar o botão do WhatsApp
                    const telefone = `${lead.telefone}`;
                    
                    // Criar o botão do WhatsApp
                    const whatsappButton = `<a href="${whatsappLink}" target="_blank" class="${buttonClass} whatsapp-button" data-lead-id="${lead.lead_id}">${buttonLabel}</a>`;

                    // Criar os links adicionais para a coluna "Ações"
                    const vai = `<a href="#" class="btn btn-success btn-lg"><i class="fa fa-thumbs-up"></i></a>`;
                    const naoVai = `<a href="#" class="btn btn-danger btn-lg" data-lead-id="${lead.lead_id}"><i class="fa fa-thumbs-down"></i></a>`;

                    // Adicionar os links na coluna "Ações"
                    const actionsColumn = `${vai} ${naoVai}`;

                    dataSet.push([
                        lead.nome_completo,
                        telefone,
                        //whatsappButton,
                        lead.typelead,
                        actionsColumn
                    ]);
                });

                // Inicializar o DataTable
                const dataTable = $('#leadsTable').DataTable({
                    data: dataSet,
                    columns: [
                        { title: "Nome" },
                        { title: "Telefone" },
                        { title: "Origem" },
                        { title: "Confirmação" }
                    ],
                    destroy: true,
                    ordering: false,
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json"
                    }
                });

                // Adicionar o manipulador de eventos para os botões do WhatsApp
                $('#leadsTable').off('click', 'a.whatsapp-button');
                $('#leadsTable').on('click', 'a.whatsapp-button', function(event) {
                    event.preventDefault();

                    const buttonElement = $(this);
                    const lead_id = buttonElement.data('lead-id');
                    const whatsappLink = buttonElement.attr('href');

                    leadContacted(lead_id, whatsappLink, buttonElement);
                });

                // Adicionar o manipulador de eventos para os botões de exclusão
                $('#leadsTable').on('click', '.delete-lead', function(event) {
                    event.preventDefault();
                    const leadId = $(this).data('lead-id');
                    if (confirm('Tem certeza de que deseja excluir este lead?')) {
                        $.ajax({
                            url: 'delete_lead.php',
                            method: 'POST',
                            data: { lead_id: leadId },
                            success: function(response) {
                                if (response.success) {
                                    alert('Lead excluído com sucesso.');
                                    // Recarregar a tabela ou remover a linha correspondente
                                    carregarLeads(prospectorId);
                                } else {
                                    alert('Erro ao excluir o lead: ' + response.message);
                                }
                            },
                            error: function() {
                                alert('Erro ao comunicar com o servidor.');
                            }
                        });
                    }
                });

                $('#leadsModal').modal('show');

                // Atualizar a contagem de novos leads
                carregarNovosLeads(prospectorId);
            } else {
                showAlert(response.message, 'danger', '#leadsModal .modal-body');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            let errorMsg = 'Erro na requisição AJAX: ' + textStatus + ' - ' + errorThrown;
            if (jqXHR.responseText) {
                errorMsg += '<br>Resposta do servidor: ' + jqXHR.responseText;
            }
            showAlert(errorMsg, 'danger', '#leadsModal .modal-body');
        }
    });
}

function leadContacted(lead_id, whatsappLink, buttonElement) {
    $.ajax({
        url: 'lead_contacted.php',
        method: 'POST',
        data: {
            lead_id: lead_id
        },
        success: function(response) {
            if (response.success) {
                buttonElement.addClass('contacted btn-secondary');
                buttonElement.removeClass('btn-success');
                buttonElement.html('<i class="fa fa-check"></i> Enviado');
                window.open(whatsappLink, '_blank');
            } else {
                alert('Erro ao registrar o contato: ' + response.message);
                window.open(whatsappLink, '_blank');
            }
        },
        error: function() {
            alert('Erro ao comunicar com o servidor.');
            window.open(whatsappLink, '_blank');
        }
    });
}


            // Função para carregar o ranking do prospector
            function carregarRanking(prospectorId) {
                $.ajax({
                    url: 'obter_ranking.php',
                    method: 'POST',
                    data: {
                        prospector_id: prospectorId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#rankingPosicao').text(response.rankingGeral + 'º Geral (' + response.rankingConcessionaria + 'º)');
                        } else {
                            $('#rankingPosicao').text('0º');
                        }
                    },
                    error: function() {
                        $('#rankingPosicao').text('0º');
                    }
                });
            }

            // Função para carregar o número de novos leads
            function carregarNovosLeads(prospectorId) {
                $.ajax({
                    url: 'obter_novos_leads.php',
                    method: 'POST',
                    data: {
                        prospector_id: prospectorId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#novoLeadsCount').text(response.novosLeads + ' novos');
                        } else {
                            $('#novoLeadsCount').text('0 novos');
                        }
                    },
                    error: function() {
                        $('#novoLeadsCount').text('0 novos');
                    }
                });
            }


            // // Clique no botão "Ranking Geral"
            // $('#btnRankingGeral').click(function() {
            //     window.open('ranking_geral.php?prospector_id=' + prospectorId, '_blank');
            // });
            // Clique no botão "Ranking Geral"
            $('#btnRankingGeral').click(function() {
                if (dealerId && prospectorId) {
                    window.open('ranking/?dealer_id=' + dealerId + '&prospector_id=' + prospectorId, '_blank');
                } else if (dealerId) {
                    window.open('ranking/?dealer_id=' + dealerId, '_blank');
                } else {
                    window.open('ranking/', '_blank');
                }
            });

        });
    </script>
    <!-- DataTables JS -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
</body>

</html>