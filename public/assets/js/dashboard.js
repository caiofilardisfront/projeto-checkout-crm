// Arquivo: /assets/js/dashboard.js

document.addEventListener('DOMContentLoaded', () => {
    fetchLeadsAtribuidos();
});

/**
 * Busca estritamente os Leads que o usuário possui permissão (Regra de Ouro RLS)
 */
async function fetchLeadsAtribuidos() {
    try {
        // Aciona o endpoint mapeado para Src\Controllers\LeadController::index()
        const response = await fetch('/api/leads', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        // Middleware de Auth retornará 401/403 caso sessão seja inválida
        if (response.status === 401 || response.status === 403) {
            window.location.href = result.redirect || '/login';
            return;
        }

        if (result.status === 'success') {
            document.getElementById('sessionIndicador').innerText = 'Ambiente Seguro Conectado';
            renderizarTabela(result.data);
        } else {
            exibirErro(result.message);
        }
    } catch (error) {
        console.error('Falha de rede:', error);
        exibirErro('Falha na comunicação com o servidor principal.');
    }
}

/**
 * Processa o JSON injetando no DOM, garantindo tipagem visual baseada no PRD [1]
 */
function renderizarTabela(leads) {
    const tbody = document.getElementById('leadsContainer');
    tbody.innerHTML = '';

    if (!leads || leads.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5 text-muted fw-semibold">
                    Nenhum lead atribuído à sua visualização no momento.
                </td>
            </tr>
        `;
        return;
    }

    leads.forEach(lead => {
        const tr = document.createElement('tr');
        
        // Proteção contra quebra de casas decimais via Intl nativo
        const valorFormatado = parseFloat(lead.valor_proposta).toLocaleString('pt-BR', { 
            style: 'currency', 
            currency: 'BRL' 
        });

        tr.innerHTML = `
            <td class="fw-bold">${lead.nome_contato}</td>
            <td>${lead.nome_agencia}</td>
            <td>${lead.telefone}</td>
            <td class="fw-bold" style="color: var(--tiffany-blue);">${valorFormatado}</td>
            <td><span class="badge-status badge-${lead.status}">${formatarStatusBadge(lead.status)}</span></td>
            <td class="text-end">
                <button class="btn-action" onclick="rotearConfiguracaoPedido(${lead.id})">Configurar Pagamento</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function formatarStatusBadge(status) {
    const statusMap = {
        'novo': 'Novo Lead',
        'em_negociacao': 'Negociação',
        'aguardando_pagamento': 'Checkout Pendente',
        'fechado': 'Fechado (Pago)',
        'perdido': 'Perdido'
    };
    return statusMap[status] || status;
}

function rotearConfiguracaoPedido(idLead) {
    // Roteia para a view 'config_pedido.php' injetando o ID na URL para carregamento do payload [2]
    window.location.href = `/config_pedido?lead_id=${idLead}`;
}

async function logout() {
    await fetch('/api/logout', { method: 'POST', headers: { 'Accept': 'application/json' } });
    window.location.href = '/login';
}