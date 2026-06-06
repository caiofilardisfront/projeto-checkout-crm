<?php
// Arquivo: /views/checkout/form.php
require_once __DIR__ . '/../../config/db.php';

use Config\Database;

$mpPublicKey = getenv('MP_PUBLIC_KEY') ?: '';
$leadId = filter_input(INPUT_GET, 'lead_id', FILTER_VALIDATE_INT);

$valorProposta = 0.00;

// Busca o valor atualizado no banco de dados ao carregar a página
if ($leadId) {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT valor_proposta FROM leads WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $leadId]);
    $valorProposta = (float) $stmt->fetchColumn();
}

$valorFormatado = number_format($valorProposta, 2, ',', '.');
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dados de Faturamento | JONES GROUP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <style>
        :root {
            --deep-blue: #1A3A52;
            --tiffany-blue: #4FD1C5;
            --bg-light: #F4F7F6;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', sans-serif;
            padding-bottom: 50px;
        }

        .header-checkout {
            background-color: var(--deep-blue);
            padding: 15px 0;
            color: #fff;
        }

        .checkout-card {
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .btn-action {
            background-color: var(--tiffany-blue);
            color: var(--deep-blue);
            font-weight: 700;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 6px;
            transition: 0.3s;
        }

        .btn-action:hover {
            background-color: #3bbcb0;
            color: #fff;
        }
    </style>
</head>

<body>

    <header class="header-checkout mb-5">
        <div class="container d-flex justify-content-between align-items-center">
            <h4 class="m-0">CHECKOUT - <strong>JONES GROUP</strong></h4>
            <span style="color: var(--tiffany-blue); font-weight: 600;">🔒 AMBIENTE 100% SEGURO</span>
        </div>
    </header>

    <div class="container">
        <div class="row g-4">

            <div class="col-lg-7">
                <!-- PASSO 1 -->
                <div class="checkout-card" id="passo1_formulario">
                    <h5 class="mb-4 fw-bold" style="color: var(--deep-blue);">DADOS DE FATURAMENTO</h5>
                    <form id="formCheckoutForm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">NOME COMPLETO OU RAZÃO SOCIAL</label>
                            <input type="text" class="form-control" id="clienteNome" required placeholder="Digite o nome completo">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">E-MAIL PROFISSIONAL</label>
                                <input type="email" class="form-control" id="clienteEmail" required placeholder="contato@empresa.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">TELEFONE / WHATSAPP</label>
                                <input type="text" class="form-control" id="clienteTelefone" required placeholder="(00) 00000-0000">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">CPF OU CNPJ</label>
                            <input type="text" class="form-control" id="clienteDocumento" required placeholder="000.000.000-00">
                        </div>
                        <button type="submit" class="btn-action" id="btnProsseguir">PROSSEGUIR PARA PAGAMENTO SEGURO</button>
                    </form>
                </div>

                <!-- PASSO 2 (Com Injeção Dinâmica do Valor) -->
                <div class="checkout-card" id="passo2_pagamento" style="display: none;"
                    data-public-key="<?= htmlspecialchars($mpPublicKey) ?>"
                    data-amount="<?= $valorProposta ?>">
                    <h5 class="mb-4 fw-bold" style="color: var(--deep-blue);">ESCOLHA A FORMA DE PAGAMENTO</h5>
                    <div id="paymentBrick_container">
                        <div class="text-center py-5">
                            <div class="spinner-border text-secondary" role="status"></div>
                            <p class="mt-3 text-muted fw-bold">A gerar ambiente criptografado...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Painel Lateral (Resumo Dinâmico) -->
            <!-- Painel Lateral (Resumo com Serviços Fixos e Total Dinâmico) -->
            <div class="col-lg-5">
                <div class="checkout-card h-100">
                    <h5 class="mb-4 fw-bold" style="color: var(--deep-blue);">RESUMO DA CONTRATAÇÃO</h5>

                    <!-- Serviços ancorados visualmente -->
                    <div class="d-flex justify-content-between mb-2 fw-semibold"><span>Sistema CRM Comercial Completo</span><span>R$ 1.200,00</span></div>
                    <div class="d-flex justify-content-between mb-2 fw-semibold"><span>Treinamento SDR Especializado</span><span>R$ 500,00</span></div>
                    <div class="d-flex justify-content-between mb-3 fw-semibold"><span>Manutenção e Hospedagem</span><span>R$ 400,00</span></div>

                    <hr>

                    <!-- Totalizador lendo o banco de dados -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <strong style="color: var(--deep-blue); font-size: 1.2rem;">Total a Pagar</strong>
                        <strong style="color: var(--tiffany-blue); font-size: 1.5rem;">R$ <?= $valorFormatado ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/checkout.js?v=<?= time() ?>"></script>
</body>

</html>