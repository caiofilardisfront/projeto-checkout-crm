<!-- Arquivo: /views/checkout/success.php -->
<?php
    $paymentId = filter_input(INPUT_GET, 'payment_id', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'PENDENTE';
    $leadId = filter_input(INPUT_GET, 'external_reference', FILTER_VALIDATE_INT) ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Confirmado | CRM-CHECKOUT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --deep-blue: #1A3A52;
            --tiffany-blue: #4FD1C5;
            --bg-light: #F8FAFC;
        }
        body { background-color: var(--bg-light); font-family: 'Segoe UI', system-ui, sans-serif; color: #334155; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .success-card { background: #FFFFFF; border-radius: 12px; box-shadow: 0 10px 30px rgba(26, 58, 82, 0.08); padding: 50px 40px; text-align: center; max-width: 500px; width: 100%; }
        .icon-circle { width: 80px; height: 80px; background-color: rgba(79, 209, 197, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; color: var(--tiffany-blue); }
        .title { font-weight: 800; color: var(--deep-blue); font-size: 1.5rem; margin-bottom: 15px; }
        .protocol-box { background-color: #F1F5F9; padding: 15px; border-radius: 8px; margin: 25px 0; font-weight: 700; color: var(--deep-blue); font-size: 1.1rem; border: 1px dashed #CBD5E1; }
        .protocol-box span { font-weight: 500; font-size: 0.85rem; color: #64748B; display: block; text-transform: uppercase; margin-bottom: 5px; }
        .btn-download { background-color: var(--deep-blue); color: #FFFFFF; font-weight: 700; padding: 14px 20px; border-radius: 8px; text-decoration: none; display: block; transition: 0.3s; }
        .btn-download:hover { background-color: #122b3e; color: #FFFFFF; }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="icon-circle">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" width="40" height="40">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        </div>
        <h2 class="title">Pagamento Recebido com Sucesso!</h2>
        <p>Sua contratação foi confirmada e nosso sistema já foi notificado.</p>
        
        <div class="protocol-box">
            <span>Protocolo da Transação (NSU)</span>
            #<?= $paymentId ?>
        </div>

        <p class="mb-4 text-muted" style="font-size: 0.9rem;">
            <strong>Próximos Passos:</strong> Em até 24h nossa equipe entrará em contato para iniciar a configuração do seu CRM e o treinamento do SDR.
        </p>

        <a href="javascript:window.print()" class="btn-download">
            Salvar Comprovante (PDF)
        </a>
    </div>
</body>
</html>