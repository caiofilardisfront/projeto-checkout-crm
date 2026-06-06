// Arquivo: /assets/js/dashboard.js

document.addEventListener('DOMContentLoaded', () => {
    fetchLeadsAtribuidos();
    // Novo ouvinte seguro para o botão de sair
    const btnSair = document.getElementById('btnSair');
    if (btnSair) {
        btnSair.addEventListener('click', (e) => {
            e.preventDefault();
            logout();
        });
    }
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

        // Correção: Apenas uma tag <td> para as ações, contendo todos os 4 botões!
        tr.innerHTML = `
            <td class="fw-bold">${lead.nome_contato}</td>
            <td>${lead.nome_agencia}</td>
            <td>${lead.telefone}</td>
            <td class="fw-bold" style="color: var(--tiffany-blue);">${valorFormatado}</td>
            <td><span class="badge-status badge-${lead.status}">${formatarStatusBadge(lead.status)}</span></td>
            <td class="text-end" style="white-space: nowrap;">
                <button class="btn btn-sm btn-outline-secondary fw-bold me-1" onclick="abrirModalUpload(${lead.id})" title="Anexar Contrato PDF">📄</button>
                <button class="btn btn-sm btn-outline-info fw-bold me-1" onclick="editarLead(${lead.id}, '${lead.nome_contato}', '${lead.nome_agencia}', '${lead.telefone}', ${lead.valor_proposta}, '${lead.origem}')" title="Editar Lead">✏️</button>
                <button class="btn btn-sm btn-outline-danger fw-bold me-2" onclick="deletarLead(${lead.id})" title="Excluir Lead">🗑️</button>
                <button class="btn-action" onclick="rotearConfiguracaoPedido(${lead.id})">Cobrar</button>
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

// Adiciona o Event Listener para a criação de leads assim que o DOM carregar
document.addEventListener('DOMContentLoaded', () => {
    const formNovoLead = document.getElementById('formNovoLead');
    if (formNovoLead) {
        formNovoLead.addEventListener('submit', salvarNovoLead);
    }
});

function editarLead(id, nome, agencia, telefone, valor, origem) {
    // Injeta os valores atuais da linha nos inputs do Modal
    document.getElementById('leadIdEdicao').value = id;
    document.getElementById('leadNome').value = nome;
    document.getElementById('leadAgencia').value = agencia;
    document.getElementById('leadTelefone').value = telefone;

    // Formatação monetária reversa para o input
    const valorFormatado = (parseFloat(valor)).toFixed(2).replace('.', ',');
    document.getElementById('leadValor').value = 'R$ ' + valorFormatado.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');

    document.getElementById('leadOrigem').value = origem;
    document.getElementById('leadOrigem').disabled = true; // Origem não se altera

    // Altera títulos e comportamento do modal
    document.querySelector('#modalNovoLead .modal-title').innerText = 'Editar Lead Executivo';
    const submitBtn = document.querySelector('#formNovoLead button[type="submit"]');
    submitBtn.innerText = 'ATUALIZAR LEAD';

    const modal = new bootstrap.Modal(document.getElementById('modalNovoLead'));
    modal.show();
}

// Substitua sua função salvarNovoLead() atual por esta (híbrida para Criar/Atualizar):
async function salvarNovoLead(event) {
    event.preventDefault();
    const valorRaw = document.getElementById('leadValor').value.replace(/\D/g, '');
    const idEdicao = document.getElementById('leadIdEdicao') ? document.getElementById('leadIdEdicao').value : '';

    const payload = {
        id: idEdicao ? parseInt(idEdicao) : null,
        nome_contato: document.getElementById('leadNome').value.trim(),
        nome_agencia: document.getElementById('leadAgencia').value.trim(),
        telefone: document.getElementById('leadTelefone').value.trim(),
        valor_proposta: valorRaw ? (parseFloat(valorRaw) / 100) : 0,
        origem: document.getElementById('leadOrigem').value
    };

    const isUpdate = !!idEdicao;
    const endpoint = isUpdate ? '/api/leads/atualizar' : '/api/leads';
    const method = isUpdate ? 'PUT' : 'POST';

    const submitBtn = event.target.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerText = 'PROCESSANDO...';

    try {
        const response = await fetch(endpoint, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if ((response.status === 201 || response.status === 200) && result.status === 'success') {
            const modalElement = document.getElementById('modalNovoLead');
            bootstrap.Modal.getInstance(modalElement).hide();

            event.target.reset();
            if (document.getElementById('leadIdEdicao')) document.getElementById('leadIdEdicao').value = '';
            document.querySelector('#modalNovoLead .modal-title').innerText = 'Adicionar Novo Lead';
            document.getElementById('leadOrigem').disabled = false;

            fetchLeadsAtribuidos();
        } else {
            alert(result.message || 'Erro ao processar lead.');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Falha na comunicação com o servidor.');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerText = 'SALVAR LEAD';
    }
}

async function deletarLead(idLead) {
    if (!confirm("Ação destrutiva. Tem certeza que deseja excluir este Lead do CRM?")) return;

    try {
        const response = await fetch('/api/leads/deletar', {
            method: 'POST', // ou DELETE dependendo do roteador
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ id: idLead })
        });

        const result = await response.json();
        if (result.status === 'success') {
            fetchLeadsAtribuidos(); // Recarrega a tabela imediatamente via RLS
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error("Erro ao deletar lead:", error);
    }
}

// Funcionalidade de Upload de Contratos
function abrirModalUpload(idLead) {
    document.getElementById('uploadIdLead').value = idLead;
    document.getElementById('formUploadContrato').reset();
    const modal = new bootstrap.Modal(document.getElementById('modalUploadContrato'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', () => {
    const formUpload = document.getElementById('formUploadContrato');
    if (formUpload) {
        formUpload.addEventListener('submit', realizarUploadContrato);
    }
});

async function realizarUploadContrato(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');

    submitBtn.disabled = true;
    submitBtn.innerText = 'ENVIANDO...';

    try {
        // Para arquivos, a Fetch API calcula o Content-Type: multipart/form-data automaticamente
        const response = await fetch('/api/contratos/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (response.status === 201 && result.status === 'success') {
            const modalElement = document.getElementById('modalUploadContrato');
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            modalInstance.hide();
            alert('Contrato salvo no cofre protegido com sucesso.');
        } else {
            alert(result.message || 'Erro ao enviar arquivo.');
        }
    } catch (error) {
        console.error('Erro de upload:', error);
        alert('Falha na comunicação com o servidor durante o envio.');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerText = 'ENVIAR CONTRATO';
    }
}



// ==========================================
// MÁSCARAS DE INPUT (TELEFONE E MOEDA)
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    const inputTelefone = document.getElementById('leadTelefone');
    const inputValor = document.getElementById('leadValor');

    if (inputTelefone) {
        inputTelefone.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é número

            // Máscara dinâmica para 10 ou 11 dígitos: (XX) XXXX-XXXX ou (XX) XXXXX-XXXX
            value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
            value = value.replace(/(\d)(\d{4})$/, '$1-$2');

            e.target.value = value;
        });
    }

    if (inputValor) {
        inputValor.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove letras e caracteres

            if (value === '') {
                e.target.value = '';
                return;
            }

            // Converte para centavos e aplica a formatação BRL
            value = (parseInt(value, 10) / 100).toFixed(2) + '';
            value = value.replace('.', ',');
            value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');

            e.target.value = 'R$ ' + value;
        });
    }
});