<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Seguro | CRM-CHECKOUT</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* Identidade Visual Premium Estrita */
        :root {
            --deep-blue: #1A3A52;
            --tiffany-blue: #4FD1C5;
            --bg-light: #F8FAFC;
            --text-main: #334155;
            --text-muted: #64748B;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--text-main);
            padding-bottom: 60px;
        }

        /* Header de Confiança */
        .header-checkout {
            background-color: var(--deep-blue);
            padding: 20px 0;
            color: #FFFFFF;
            box-shadow: 0 4px 15px rgba(26, 58, 82, 0.15);
            margin-bottom: 40px;
        }

        .brand { font-weight: 800; font-size: 1.6rem; letter-spacing: 1px; margin: 0; }
        .brand span { color: var(--tiffany-blue); }
        
        .secure-badge { 
            font-size: 0.9rem; 
            font-weight: 600; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            color: var(--tiffany-blue); 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Cards do Layout */
        .checkout-card {
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(26, 58, 82, 0.05);
            border: none;
            padding: 35px;
            height: 100%;
        }

        .section-title { 
            font-size: 1.1rem; 
            font-weight: 800; 
            color: var(--deep-blue); 
            margin-bottom: 25px; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            border-bottom: 2px solid #F1F5F9; 
            padding-bottom: 12px; 
        }

        /* Resumo do Pedido [1] */
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 16px; font-weight: 500; font-size: 1rem; }
        .summary-item.sub { font-size: 0.9rem; color: var(--text-muted); margin-top: -10px; margin-bottom: 20px;}
        
        .summary-total { 
            display: flex; 
            justify-content: space-between; 
            margin-top: 30px; 
            padding-top: 20px; 
            border-top: 2px dashed #E2E8F0; 
            font-size: 1.4rem; 
            font-weight: 800; 
            color: var(--deep-blue); 
        }

        /* Formulário Antifraude [1] */
        .form-label { font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { border: 1px solid #CBD5E1; border-radius: 8px; padding: 14px 15px; font-size: 1rem; transition: all 0.2s; background-color: #F8FAFC;}
        .form-control:focus { border-color: var(--tiffany-blue); box-shadow: 0 0 0 3px rgba(79, 209, 197, 0.15); background-color: #FFFFFF;}

        /* CTA Principal */
        .btn-pay { 
            background-color: var(--tiffany-blue); 
            color: var(--deep-blue); 
            font-weight: 800; 
            font-size: 1.1rem; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            padding: 18px; 
            border-radius: 8px; 
            border: none; 
            width: 100%; 
            transition: all 0.3s ease; 
            margin-top: 25px; 
        }
        .btn-pay:hover { background-color: #3bbcb0; color: #FFFFFF; box-shadow: 0 8px 20px rgba(79, 209, 197, 0.3); transform: translateY(-2px); }

        /* Rodapé de Segurança [1] */
        .footer-trust { text-align: center; margin-top: 40px; font-size: 0.85rem; color: var(--text-muted); font-weight: 600; }
        .trust-icons { display: flex; justify-content: center; gap: 20px; margin-top: 15px; opacity: 0.6; }
        .trust-icons svg { height: 24px; }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="header-checkout">
        <div class="container d-flex justify-content-between align-items-center">
            <h1 class="brand"><span>CHECKOUT</span>- JONES GROUP</h1>
            <div class="secure-badge">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
                Ambiente 100% Seguro
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <div class="row g-4">
            
            <!-- Coluna Esquerda: Resumo do Pedido -->
            <div class="col-lg-5 order-lg-2">
                <div class="checkout-card">
                    <h3 class="section-title">Resumo da Contratação</h3>
                    
                    <!-- Dados dinâmicos que serão injetados via PHP/JS posteriormente -->
                    <div class="summary-item">
                        <span>Sistema CRM Comercial Completo</span>
                        <span>R$ 1.200,00</span>
                    </div>
                    
                    <div class="summary-item">
                        <span>Treinamento SDR Especializado</span>
                        <span>R$ 500,00</span>
                    </div>
                    
                    <div class="summary-item">
                        <span>Manutenção e Hospedagem</span>
                        <span>R$ 400,00</span>
                    </div>
                    <div class="summary-item sub">
                        <span>(Cobrança Única)</span>
                    </div>

                    <div class="summary-total">
                        <span>Total a Pagar</span>
                        <span style="color: var(--tiffany-blue);">R$ 2.100,00</span>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita: Identificação do Cliente -->
            <div class="col-lg-7 order-lg-1">
                <div class="checkout-card">
                    <h3 class="section-title">Dados de Faturamento</h3>
                    <p class="text-muted mb-4" style="font-size: 0.9rem;">Preencha os dados abaixo para emissão da cobrança e validação antifraude. Todos os dados são criptografados de ponta a ponta.</p>
                    
                    <form id="formCheckout">
                        <div class="mb-4">
                            <label class="form-label">Nome Completo ou Razão Social</label>
                            <input type="text" class="form-control" id="clienteNome" required placeholder="Digite o nome completo">
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">E-mail Profissional</label>
                                <input type="email" class="form-control" id="clienteEmail" required placeholder="contato@empresa.com">
                            </div>
                            <div class="col-md-6 mt-4 mt-md-0">
                                <label class="form-label">Telefone / WhatsApp</label>
                                <input type="tel" class="form-control" id="clienteTelefone" required placeholder="(00) 00000-0000">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">CPF ou CNPJ</label>
                            <input type="text" class="form-control" id="clienteDocumento" required placeholder="000.000.000-00">
                        </div>

                        <button type="submit" class="btn-pay" id="btnProsseguir">
                            Prosseguir para Pagamento Seguro
                        </button>
                    </form>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="footer-trust">
            <p>Pagamento processado com segurança pelo Mercado Pago. Seus dados estão protegidos.</p>
            <div class="trust-icons">
                <!-- Ícone SSL -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>SSL 256-bit</span>
                <span>|</span>
                <span>PCI Compliance</span>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('formCheckout').addEventListener('submit', async function(e) {
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
    </script>
</body>
</html>