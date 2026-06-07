// Arquivo: /assets/js/checkout.js

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formCheckoutForm');
    const passo1 = document.getElementById('passo1_formulario');
    const passo2 = document.getElementById('passo2_pagamento');
    const containerBrick = document.getElementById('paymentBrick_container');

    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // 1. Captura os dados do formulário
            const email = document.getElementById('clienteEmail').value.trim();
            const documento = document.getElementById('clienteDocumento').value.replace(/\D/g, ''); // Apenas números
            
            const urlParams = new URLSearchParams(window.location.search);
            const leadId = urlParams.get('lead_id') || 0;

            // 2. Transição visual (Oculta Passo 1, Mostra Passo 2)
            passo1.style.display = 'none';
            passo2.style.display = 'block';

            // 3. Lê a chave e inicializa o Mercado Pago
            const publicKey = passo2.getAttribute('data-public-key');
            const valorDinamico = parseFloat(passo2.getAttribute('data-amount')) || 0;

            const mp = new MercadoPago(publicKey, { locale: 'pt-BR' });
            const bricksBuilder = mp.bricks();

            const settings = {
                initialization: {
                    amount: valorDinamico, // O valor dinâmico entra aqui!
                    payer: {
                        email: email, 
                        identification: {
                            type: documento.length > 11 ? 'CNPJ' : 'CPF',
                            number: documento 
                        }
                    }
                },
                customization: {
                    visual: {
                        style: { theme: 'bootstrap', customVariables: { textPrimaryColor: '#1A3A52', baseColor: '#4FD1C5' } }
                    },
                    paymentMethods: { creditCard: "all", bankTransfer: "all" }
                },
                callbacks: {
                    onReady: () => { console.log('Checkout Transparente Carregado'); },
                    onSubmit: ({ selectedPaymentMethod, formData }) => {
                        // Anexa o ID do Lead para o backend
                        formData.lead_id = parseInt(leadId);

                        return new Promise((resolve, reject) => {
                            fetch("/api/checkout/processar", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify(formData),
                            })
                            .then((response) => response.json())
                            .then((result) => {
                                if (result.status === 'success') {
                                    resolve();
                                    
                                    // Se a API retornou o Link do Pix (QR Code), redireciona para ele
                                    if (result.ticket_url) {
                                        window.location.href = result.ticket_url;
                                    } else {
                                        // Se for Cartão de Crédito, segue para a sua tela de sucesso normal
                                        window.location.href = '/checkout/sucesso';
                                    }
                                } else {
                                    reject();
                                    alert(result.message || 'Transação recusada.');
                                }
                            })
                            .catch(() => {
                                reject();
                                alert('Erro de comunicação com o banco.');
                            });
                        });
                    },
                    onError: (error) => { console.error('Erro MP:', error); }
                }
            };
            
            // Renderiza o Brick
            containerBrick.innerHTML = ''; 
            window.paymentBrickController = await bricksBuilder.create('payment', 'paymentBrick_container', settings);
        });
    }
});

// ==========================================
// MÁSCARAS DE INPUT (TELEFONE E CPF/CNPJ)
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    const inputTelefone = document.getElementById('clienteTelefone');
    const inputDocumento = document.getElementById('clienteDocumento');

    if (inputTelefone) {
        inputTelefone.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é número
            if (value.length > 11) value = value.substring(0, 11); // Trava em 11 dígitos

            // Máscara: (XX) XXXX-XXXX ou (XX) XXXXX-XXXX
            value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
            value = value.replace(/(\d)(\d{4})$/, '$1-$2');
            e.target.value = value;
        });
    }

    if (inputDocumento) {
        inputDocumento.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é número
            if (value.length > 14) value = value.substring(0, 14); // Trava em 14 dígitos (Máximo do CNPJ)

            if (value.length <= 11) {
                // Máscara de CPF: 000.000.000-00
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else {
                // Máscara de CNPJ: 00.000.000/0000-00
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            }
            e.target.value = value;
        });
    }
});