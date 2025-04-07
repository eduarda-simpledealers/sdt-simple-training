document.addEventListener('DOMContentLoaded', function (){
    const prospectorSelect = document.getElementById('prospectorSelect');

    const tourOne = introJs();
    tourOne.setOptions({
        steps:[
            {
                title:'',
                intro: 'Bem-vindo ao seu Cockpit!',
            },
            {
                element: document.querySelector('.btn-one'),
                intro: 'Selecione o seu nome',
                disableInteraction: false,
            },
            {
                element:document.querySelector('.btn-two'),
                intro: 'Seu perfil',
            },
            {
                element:document.querySelector('.btn-three'),
                intro: 'Acesse seu lead',
            },
            {
                element:document.querySelector('.btn-four'),
                intro: 'Registrar agendamento',
            },
            {
                element:document.querySelector('.btn-five'),
                intro: 'Seu Simple Card',
            },
        ],
        showStepNumbers: false,
        showBullets: false,
        exitOnOverlayClick: false,
        exitOnEsc: false,
        showProgress: true,
    });
    
    tourOne.start();

});