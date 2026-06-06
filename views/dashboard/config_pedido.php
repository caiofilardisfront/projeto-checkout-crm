<?php
// Arquivo: /views/dashboard/config_pedido.php
require_once __DIR__ . '/../../config/db.php';

use Config\Database;

// Captura o ID da URL
$leadId = filter_input(INPUT_GET, 'lead_id', FILTER_VALIDATE_INT);

// Valores padrão
$nomeCliente = 'Lead Não Encontrado';
$valorProposta = 0.00;

// Busca os dados reais no banco
if ($leadId) {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT nome_contato, nome_agencia, valor_proposta FROM leads WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $leadId]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lead) {
        $nomeCliente = htmlspecialchars($lead['nome_agencia'] ? $lead['nome_agencia'] : $lead['nome_contato']);
        $valorProposta = (float) $lead['valor_proposta'];
    }
}

$valorFormatado = number_format($valorProposta, 2, ',', '.');
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Configurar Pedido | CRM-CHECKOUT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --deep-blue: #1A3A52;
            --tiffany-blue: #4FD1C5;
            --bg-light: #F4F7F6;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', sans-serif;
        }

        .config-card {
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body class="p-4">

    <div class="container">
        <h4 class="mb-4" style="color: var(--deep-blue); font-weight: 700;">FATURAMENTO E CHECKOUT</h4>

        <div class="row g-4">
            <!-- Coluna da Esquerda (Contexto) -->
            <div class="col-lg-7">
                <div class="config-card h-100">
                    <p class="text-muted fw-bold mb-3" style="font-size: 0.85rem;">CONTEXTO DO CLIENTE</p>
                    <h5 class="fw-bold" style="color: var(--deep-blue); text-transform: uppercase;"><?= $nomeCliente ?></h5>
                    <p class="text-muted">Acesso via Link de Cobrança Seguro</p>

                    <hr class="my-4">

                    <p class="text-muted fw-bold mb-3" style="font-size: 0.85rem;">COMPOSIÇÃO DO PACOTE (ALTO TICKET)</p>

                    <div class="alert alert-light border mb-2 text-muted">✔️ Sistema CRM Comercial Completo (Integração MP)</div>
                    <div class="alert alert-light border mb-2 text-muted">✔️ Treinamento SDR Especializado (Implantação)</div>
                    <div class="alert alert-light border mb-4 text-muted">✔️ Setup de Manutenção e Hospedagem</div>

                    <!-- BOX DE VALOR AGORA 100% DINÂMICO -->
                    <div style="background-color: var(--deep-blue); border-radius: 8px; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #fff; font-weight: 600; text-transform: uppercase; font-size: 0.9rem;">Valor Negociado da Proposta</span>
                        <strong style="color: var(--tiffany-blue); font-size: 1.8rem;">R$ <?= $valorFormatado ?></strong>
                    </div>
                </div>
            </div>

            <!-- Coluna da Direita (Link) -->
            <div class="col-lg-5">
                <div class="config-card h-100">
                    <p class="text-muted fw-bold mb-3" style="font-size: 0.85rem;">EXPORTAR COBRANÇA</p>
                    <p style="font-size: 0.9rem; color: #555;">A composição deste contrato já está atrelada ao Lead ID #<?= $leadId ?>. Envie o link seguro abaixo para o cliente realizar o pagamento (Cartão/Pix).</p>

                    <div class="mt-4">
                        <input type="text" class="form-control mb-3 text-center" style="background-color: #f8f9fa; font-family: monospace;" readonly value="https://<?= $_SERVER['HTTP_HOST'] ?>/checkout?lead_id=<?= $leadId ?>" id="linkCheckout">
                        <button class="btn w-100" style="background-color: var(--tiffany-blue); color: var(--deep-blue); font-weight: 700; padding: 12px;" onclick="copiarLink()">COPIAR LINK PARA WHATSAPP</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copiarLink() {
            var copyText = document.getElementById("linkCheckout");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);
            alert("Link copiado para a área de transferência!");
        }
    </script>
</body>

</html>