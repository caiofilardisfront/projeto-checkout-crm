<!-- Arquivo: /views/dashboard/main.php -->
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | CRM-CHECKOUT</title>
    
    <!-- Bootstrap 5 via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* Arquitetura Visual: Identidade Executiva Premium */
        :root {
            --deep-blue: #1A3A52;
            --tiffany-blue: #4FD1C5;
            --bg-light: #F4F7F6;
            --text-muted: #6C757D;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            margin: 0;
            overflow-x: hidden;
        }

        /* Sidebar Executiva */
        .sidebar {
            background-color: var(--deep-blue);
            min-height: 100vh;
            color: #FFFFFF;
            box-shadow: 4px 0 15px rgba(26, 58, 82, 0.1);
        }

        .sidebar-brand {
            padding: 25px 20px;
            font-weight: 800;
            font-size: 1.4rem;
            color: #FFFFFF;
            letter-spacing: 1px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-brand span {
            color: var(--tiffany-blue);
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(79, 209, 197, 0.1);
            color: var(--tiffany-blue);
        }

        /* Top Navigation */
        .top-nav {
            background: #FFFFFF;
            padding: 20px 35px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .top-nav h4 {
            color: var(--deep-blue);
            font-weight: 700;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 1.2rem;
        }

        /* Tabela de Leads Otimizada */
        .table-wrapper {
            background: #FFFFFF;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(26, 58, 82, 0.05);
            margin: 30px;
        }

        .table-leads {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-leads thead th {
            background-color: var(--bg-light);
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.8px;
            padding: 15px;
            border: none;
            border-bottom: 2px solid #E2E8F0;
        }

        .table-leads tbody td {
            padding: 18px 15px;
            vertical-align: middle;
            color: var(--deep-blue);
            border-bottom: 1px solid #F1F5F9;
            font-size: 0.95rem;
        }

        .table-leads tbody tr:hover {
            background-color: #F8FAFC;
        }

        /* Badges de Status do Funil */
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-novo { background-color: #E2E8F0; color: #475569; }
        .badge-em_negociacao { background-color: #FEF3C7; color: #D97706; }
        .badge-aguardando_pagamento { background-color: #E0E7FF; color: #4338CA; }
        .badge-fechado { background-color: #D1FAE5; color: #059669; }
        .badge-perdido { background-color: #FEE2E2; color: #DC2626; }

        /* Botão de Ação Principal */
        .btn-action {
            background-color: var(--tiffany-blue);
            color: var(--deep-blue);
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .btn-action:hover {
            background-color: #3bbcb0;
            color: #FFFFFF;
            box-shadow: 0 4px 10px rgba(79, 209, 197, 0.3);
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Roteamento Visual e Controles de View -->
    <div class="sidebar" style="width: 260px;">
        <div class="sidebar-brand">CRM<span>CHECKOUT</span></div>
        <div class="p-3 mt-2">
            <ul class="nav flex-column gap-1">
                <li class="nav-item">
                    <a class="nav-link active" href="/dashboard">Funil de Leads</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/config_pedido">Criar Cobrança</a>
                </li>
                <li class="nav-item mt-5">
                    <button class="btn btn-outline-light w-100 btn-sm" onclick="logout()" style="font-weight: 600; letter-spacing: 1px;">ENCERRAR SESSÃO</button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Interface Dinâmica Carregada via Fetch API -->
    <div class="flex-grow-1">
        <div class="top-nav">
            <h4>Análise de Leads</h4>
            <div style="font-size: 0.9rem; font-weight: 600; color: var(--text-muted);">
                <span id="sessionIndicador">Carregando painel seguro...</span>
            </div>
        </div>

        <div class="table-wrapper">
            <div class="table-responsive">
                <table class="table-leads">
                    <thead>
                        <tr>
                            <th>Lead / Contato</th>
                            <th>Agência</th>
                            <th>Telefone</th>
                            <th>Valor da Proposta</th>
                            <th>Status no Funil</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="leadsContainer">
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                                <span class="ms-2 text-muted fw-semibold">Buscando dados isolados (RLS)...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/dashboard.js"></script>
</body>
</html>