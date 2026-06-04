<?php
// Arquivo: /views/dashboard/config_pedido.php

use Config\Database;
use Src\Models\Lead;

$leadId = filter_input(INPUT_GET, 'lead_id', FILTER_VALIDATE_INT);

// BLINDAGEM RLS: Bloqueia acesso caso o usuário tente forçar um lead_id via URL que não lhe pertence
if (!$leadId || !Lead::checkAcessoLead($leadId, $_SESSION['usuario_id'])) {
    header("Location: /dashboard");
    exit;
}

// Extração dos dados do Lead
$pdo = Database::getConnection();
$stmt = $pdo->prepare("SELECT nome_contato, nome_agencia FROM leads WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $leadId]);
$lead = $stmt->fetch();

// Geração Dinâmica da URL do Checkout Público
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$baseUrl = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000');
$linkCheckout = $baseUrl . '/checkout?lead_id=' . $leadId;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Cobrança | CRM-CHECKOUT</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --deep-blue: #1A3A52;
            --tiffany-blue: #4FD1C5;
            --bg-light: #F4F7F6;
            --text-muted: #6C757D;
        }
        body { background-color: var(--bg-light); font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; overflow-x: hidden; }
        
        /* Sidebar (Mesma do Dashboard) */
        .sidebar { background-color: var(--deep-blue); min-height: 100vh; color: #FFFFFF; box-shadow: 4px 0 15px rgba(26, 58, 82, 0.1); }
        .sidebar-brand { padding: 25px 20px; font-weight: 800; font-size: 1.4rem; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .sidebar-brand span { color: var(--tiffany-blue); }
        .nav-link { color: rgba(255, 255, 255, 0.7); font-weight: 500; padding: 12px 20px; border-radius: 6px; margin-bottom: 5px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background-color: rgba(79, 209, 197, 0.1); color: var(--tiffany-blue); }
        
        /* Top Nav */
        .top-nav { background: #FFFFFF; padding: 20px 35px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03); display: flex; justify-content: space-between; align-items: center; }
        .top-nav h4 { color: var(--deep-blue); font-weight: 700; margin: 0; text-transform: uppercase; font-size: 1.2rem; }
        
        /* Painel de Configuração */
        .config-wrapper { padding: 30px 35px; }
        .config-card { background: #FFFFFF; border-radius: 12px; box-shadow: 0 5px 20px rgba(26, 58, 82, 0.05); padding: 30px; border: none; }
        
        .section-title { font-size: 0.9rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #F1F5F9; padding-bottom: 10px; margin-bottom: 20px; }
        .client-info { font-size: 1.2rem; font-weight: 800; color: var(--deep-blue); }
        
        .item-list { list-style: none; padding: 0; margin: 0; }
        .item-list li { padding: 12px 15px; border: 1px solid #E2E8F0; border-radius: 6px; margin-bottom: 10px; font-weight: 600; color: var(--deep-blue); display: flex; align-items: center; gap: 10px; background-color: #F8FAFC; }
        .item-list input[type="checkbox"] { accent-color: var(--tiffany-blue); width: 18px; height: 18px; }
        
        .total-box { background-color: var(--deep-blue); color: #fff; padding: 20px; border-radius: 8px; margin-top: 20px; text-align: right; }
        .total-box span { font-size: 0.9rem; text-transform: uppercase; opacity: 0.8; font-weight: 600; display: block; }
        .total-box h2 { margin: 0; color: var(--tiffany-blue); font-weight: 800; }

        .link-box { border: 2px dashed #CBD5E1; background: #F8FAFC; border-radius: 8px; padding: 20px; margin-top: 30px; text-align: center; }
        .link-box input { border: 1px solid #E2E8F0; border-radius: 6px; padding: 10px; width: 100%; font-family: monospace; color: var(--text-muted); background: #fff; text-align: center; margin-bottom: 15px; }
        .btn-copy { background-color: var(--tiffany-blue); color: var(--deep-blue); font-weight: 800; text-transform: uppercase; border: none; padding: 12px 25px; border-radius: 6px; width: 100%; transition: 0.3s; }
        .btn-copy:hover { background-color: #3bbcb0; color: #fff; }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar" style="width: 260px;">
        <div class="sidebar-brand">CRM<span>CHECKOUT</span></div>
        <div class="p-3 mt-2">
            <ul class="nav flex-column gap-1">
                <li class="nav-item">
                    <a class="nav-link" href="/dashboard">Funil de Leads</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="#">Criar Cobrança</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Interface Principal -->
    <div class="flex-grow-1">
        <div class="top-nav">
            <h4>Faturamento e Checkout</h4>
            <a href="/dashboard" class="btn btn-outline-secondary btn-sm fw-bold">VOLTAR AO FUNIL</a>
        </div>

        <div class="config-wrapper">
            <div class="row">
                <!-- Configuração dos Itens -->
                <div class="col-lg-7">
                    <div class="config-card h-100">
                        <h5 class="section-title">Contexto do Cliente</h5>
                        <div class="mb-4">
                            <div class="client-info"><?= htmlspecialchars($lead['nome_agencia']) ?></div>
                            <div class="text-muted fw-semibold">Contato: <?= htmlspecialchars($lead['nome_contato']) ?></div>
                        </div>

                        <h5 class="section-title mt-5">Composição do Pacote (Alto Ticket)</h5>
                        <ul class="item-list">
                            <li>
                                <input type="checkbox" checked disabled> 
                                Sistema CRM Comercial Completo (Integração MP)
                            </li>
                            <li>
                                <input type="checkbox" checked disabled> 
                                Treinamento SDR Especializado (Implantação)
                            </li>
                            <li>
                                <input type="checkbox" checked disabled> 
                                Setup de Manutenção e Hospedagem
                            </li>
                        </ul>

                        <div class="total-box">
                            <span>Valor Fixo do Contrato</span>
                            <h2>R$ 2.100,00</h2>
                        </div>
                    </div>
                </div>

                <!-- Geração de Link -->
                <div class="col-lg-5">
                    <div class="config-card h-100">
                        <h5 class="section-title">Exportar Cobrança</h5>
                        <p class="text-muted" style="font-size: 0.9rem;">
                            A composição deste contrato já está atrelada ao Lead ID <strong>#<?= $leadId ?></strong>. 
                            Envie o link seguro abaixo para o cliente realizar o pagamento (Cartão/Pix).
                        </p>

                        <div class="link-box">
                            <input type="text" id="checkoutUrl" value="<?= $linkCheckout ?>" readonly>
                            <button class="btn-copy" onclick="copiarLink()">COPIAR LINK PARA WHATSAPP</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function copiarLink() {
        const input = document.getElementById('checkoutUrl');
        input.select();
        input.setSelectionRange(0, 99999); // Mobile compliance
        navigator.clipboard.writeText(input.value).then(() => {
            const btn = document.querySelector('.btn-copy');
            btn.innerText = 'LINK COPIADO!';
            btn.style.backgroundColor = '#1A3A52';
            btn.style.color = '#FFFFFF';
            setTimeout(() => {
                btn.innerText = 'COPIAR LINK PARA WHATSAPP';
                btn.style.backgroundColor = 'var(--tiffany-blue)';
                btn.style.color = 'var(--deep-blue)';
            }, 3000);
        });
    }
</script>
</body>
</html>