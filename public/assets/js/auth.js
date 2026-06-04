// Arquivo: /assets/js/auth.js

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', realizarLogin);
    }
});

async function realizarLogin(event) {
    event.preventDefault();

    const email = document.getElementById('email').value.trim();
    const senha = document.getElementById('senha').value;
    const alertBox = document.getElementById('loginAlert');
    const btnSubmit = document.getElementById('btnSubmit');
    const btnText = document.getElementById('btnText');
    const btnLoader = document.getElementById('btnLoader');

    // UI Loading state
    alertBox.style.display = 'none';
    btnSubmit.disabled = true;
    btnText.classList.add('d-none');
    btnLoader.classList.remove('d-none');

    try {
        const response = await fetch('/api/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email, senha })
        });

        const result = await response.json();

        if (response.ok && result.status === 'success') {
            window.location.href = result.redirect;
        } else {
            exibirErro(result.message || 'Falha na autenticação.');
        }
    } catch (error) {
        console.error('Erro de rede/servidor:', error);
        exibirErro('Servidor indisponível. Tente novamente mais tarde.');
    } finally {
        // UI Reset state
        btnSubmit.disabled = false;
        btnText.classList.remove('d-none');
        btnLoader.classList.add('d-none');
    }

    function exibirErro(mensagem) {
        alertBox.innerText = mensagem;
        alertBox.style.display = 'block';
    }
}