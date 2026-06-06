// Arquivo: /assets/js/checkout.js

document.addEventListener('DOMContentLoaded', () => {
    const formCheckout = document.getElementById('formCheckout');
    
    if (formCheckout) {
        formCheckout.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('btnProsseguir');
            
            // PREVENÇÃO DE CLIQUE DUPLO: Bloqueio da UI
            btn.disabled = true;
            btn.innerText = 'Processando Ambiente Seguro...';

            // Resgata o ID de origem passado via query string (Ex: /checkout?lead_id=5)
            const urlParams = new URLSearchParams(window.location.search);
            const leadId = urlParams.get('lead_id') || 0;

            const payload = {
                nome: document.getElementById('clienteNome').value.trim(),
                email: document.getElementById('clienteEmail').value.trim(),
                telefone: document.getElementById('clienteTelefone').value.trim(),
                documento: document.getElementById('clienteDocumento').value.trim(),
                lead_id: parseInt(leadId)
            };

            try {
                const response = await fetch('/api/checkout/processar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (response.ok && result.status === 'success' && result.init_point) {
                    // Redireciona o cliente para o ambiente criptografado do Mercado Pago
                    window.location.href = result.init_point;
                } else {
                    alert(result.message || 'Falha ao engatilhar integração de pagamento.');
                    btn.disabled = false;
                    btn.innerText = 'Prosseguir para Pagamento Seguro';
                }
            } catch (error) {
                console.error('Erro de rede:', error);
                alert('Servidor indisponível. Verifique sua conexão e tente novamente.');
                btn.disabled = false;
                btn.innerText = 'Prosseguir para Pagamento Seguro';
            }
        });
    }
});