<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leitor QR Code Evento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="favicon.png">
    <!-- Biblioteca Html5Qrcode -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
    <meta name="theme-color" content="#fff">
    <meta property="og:title" content="Leitor de QR Codes" />
    <meta property="og:type" content="website" />
    <meta property="og:image" content="https://gwmdahruj.dexp.online/assets/img/og.png" />
    <meta property="og:url" content="https://gwmdahruj.dexp.online" />

    <style>
        body {
            background: #fff !important;
            color: white;
        }

        .body {
            height: 80vh;
        }

        #reader {
            width: 100%;
            height: auto;
            border-radius: 20px;
            overflow: hidden;
        }

        .bg-secondary {
            background: #eaeaea !important;
        }

        #qrModalBody {
            color: #212121;
        }
    </style>
</head>

<body>
    <header class="bg-secondary text-center py-3">
        <img src="../assets/img/evento/logo-gwmdahruj.svg" width="250">
    </header>
    <div class="container d-flex justify-content-center align-items-center body">
        <div id="reader"></div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="qrModalLabel">QR Code lido com sucesso!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="qrModalBody">
                    <!-- O conteúdo do QR Code será exibido aqui -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-success btn-block" id="confirmButton">Confirmar Presença</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script>
        jQuery(function($) {
            // Verifica se a biblioteca foi carregada
            if (typeof Html5Qrcode === 'undefined') {
                console.error('A biblioteca Html5Qrcode não foi carregada corretamente.');
            } else {
                console.log('Html5Qrcode carregada com sucesso.');

                const readerElement = document.getElementById('reader');
                const modal = new bootstrap.Modal(document.getElementById('qrModal'));
                const modalBody = document.getElementById('qrModalBody');
                const confirmButton = document.getElementById('confirmButton');
                let lead_prospector_id = null;
                let lead_name = null;

                const onScanSuccess = (decodedText) => {
                    // console.log('QR Code Detectado:', decodedText);

                    // Get lead data
                    $.ajax({
                        url: '/app/lead_presente.php',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            lead_id: decodedText
                        }),
                        success: function(response) {
                            // console.log('Success:', response);

                            lead_prospector_id = response.data.prospector_id;
                            lead_name = response.data.nome;

                            // modalBody.innerHTML = `<span>Nome ${response.data.nome}</span>`;
                            modalBody.innerHTML = `
                                <div class="row">
                                    <div class="mb-3 col-6">
                                        <label for="lead-name-input" class="form-label">Nome</label>
                                        <input type="text" class="form-control" id="lead-name-input" placeholder="${response.data.nome}" disabled>
                                    </div>
                                    <div class="mb-3 col-6">
                                        <label for="lead-surname-input" class="form-label">Sobrenome</label>
                                        <input type="text" class="form-control" id="lead-surname-input" placeholder="${response.data.sobrenome}" disabled>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="lead-concessionaire-input" class="form-label">Concessionária</label>
                                    <input type="text" class="form-control" id="lead-concessionaire-input" placeholder="${response.data.concessionaria}" disabled>
                                </div>
                                <div class="mb-3">
                                    <label for="lead-prospector-input" class="form-label">Vendedor</label>
                                    <input type="text" class="form-control" id="lead-prospector-input" placeholder="${response.data.nome_comercial}" disabled>
                                </div>
                            `;
                            modal.show();
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('Error:', textStatus, errorThrown);
                        }
                    });

                    // Confirm lead presence
                    confirmButton.onclick = () => {
                        $.ajax({
                            url: '/app/gera_atividade.php',
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({
                                lead_id: decodedText,
                                prospector_id: lead_prospector_id
                            }),
                            success: function(response) {
                                // console.log(response);
                                if (response.message !== 'success') {
                                    alert(`${response.message}`);
                                } else {
                                    alert(`Presença confirmada para: ${lead_name}`);
                                }
                                
                                modal.hide();
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                // console.error('Error:', textStatus, errorThrown);
                                alert(`Falha ao confirmar presença para: ${lead_name}`);
                                modal.hide();
                            }
                        });
                    };
                };

                const onScanFailure = (error) => {
                    // console.warn('Falha ao ler QR Code:', error);
                };

                const html5QrCode = new Html5Qrcode("reader");
                html5QrCode.start({
                        facingMode: "environment"
                    }, {
                        fps: 10,
                        qrbox: {
                            width: 250,
                            height: 250
                        }
                    },
                    onScanSuccess,
                    onScanFailure
                ).catch((err) => {
                    console.error("Erro ao iniciar o leitor de QR Code:", err);
                    alert("Erro ao acessar a câmera. Verifique as permissões do navegador.");
                });
            }
        });
    </script>
</body>

</html>