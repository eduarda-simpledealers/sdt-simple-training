<?php
// Defina o ambiente: 'production', 'sandbox', ou 'localhost'
define('ENVIRONMENT', 'production');
define('BASE_PATH', dirname(__DIR__));

$config = [];

if (ENVIRONMENT === 'sandbox') {
    $config['DB_HOST'] = '35.194.32.21';
    $config['DB_USER'] = 'master';
    $config['DB_PASSWORD'] = 'adYiDReRRChxfZAD';
    $config['DB_NAME'] = 'dexp_boa_terra_11_2024';

    // Nomes das tabelas
    $config['table_usuarios']= 'usuarios';
    $config['table_concessionaria']= 'concessionaria';
    $config['table_prospectores']= 'prospectores';
    $config['table_tipo_atividade']= 'tipo_atividade';
    $config['table_atividades']= 'atividades';
    $config['table_leads']= 'leads';
    $config['table_usuario_concessionaria']= 'usuario_Concessionaria';
    $config['table_base_leads']= 'base_leads';
    $config['table_historico_status_base_leads']= 'historico_status_base_leads';


} elseif (ENVIRONMENT === 'production') {
    $config['DB_HOST'] = '34.151.226.187';
    $config['DB_USER'] = 'app_dexp';
    $config['DB_PASSWORD'] = 'e>t12K93R2&?j]Xn';
    $config['DB_NAME'] = 'dexp_simpledealers';

    $config['table_usuarios']= 'usuarios';
    $config['table_concessionaria']= 'concessionaria';
    $config['table_prospectores']= 'prospectores';
    $config['table_tipo_atividade']= 'tipo_atividade';
    $config['table_atividades']= 'atividades';
    $config['table_leads']= 'leads';
    $config['table_usuario_concessionaria']= 'usuario_concessionaria';
    $config['table_base_leads']= 'base_leads';
    $config['table_historico_status_base_leads']= 'Historico_Status_Base_Leads';



} elseif (ENVIRONMENT === 'localhost') {
    $config['DB_HOST'] = 'localhost';
    $config['DB_USER'] = 'dealers';
    $config['DB_PASSWORD'] = 'dealers';
    $config['DB_NAME'] = 'dexp_game';

    $config['table_usuarios']= 'Usuarios';
    $config['table_concessionaria']= 'Concessionaria';
    $config['table_prospectores']= 'Prospectores';
    $config['table_tipo_atividade']= 'Tipo_Atividade';
    $config['table_atividades']= 'Atividades';
    $config['table_leads']= 'Leads';
    $config['table_usuario_concessionaria']= 'Usuario_Concessionaria';
    $config['table_base_leads']= 'Base_Leads';
    $config['table_historico_status_base_leads']= 'Historico_Status_Base_Leads';


}

return $config;
?>
