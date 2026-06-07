// Arquivo: /assets/js/dashboard.js

document.addEventListener('DOMContentLoaded', () => {
    fetchLeadsAtribuidos();
    fetchMetricasDashboard();

    // Novo ouvinte seguro para o botão de sair
    const btnSair = document.getElementById('btnSair');
    if (btnSair) {
        btnSair.addEventListener('click', (e) => {
            e.preventDefault();
            logout();
        });
    }

    // INJETE ESTAS LINHAS: Ouvinte do Modal de Agendamento
    const formAgendamento = document.getElementById('formAgendamento');
    if (formAgendamento) {
        formAgendamento.addEventListener('submit', salvarAgendamento);
    }

    // (Pode haver outros ouvintes aqui embaixo, como o do formNovoLead...)
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

// ==========================================
// MOTOR DE MÉTRICAS EXECUTIVAS (KPIs)
// ==========================================
async function fetchMetricasDashboard() {
    try {
        const response = await fetch('/api/dashboard/metricas', {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        });

        const result = await response.json();

        if (result.status === 'success') {
            const data = result.data;

            // 1. Injeção Direta de KPIs
            document.getElementById('kpiFaturamento').innerText = data.faturamento.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            document.getElementById('kpiContratos').innerText = data.contratos;
            document.getElementById('kpiTempo').innerText = Math.round(data.tempo_medio) + ' dias';

            // 2. Lógica Algorítmica do Funil
            const funil = data.funil;
            const volNovo = funil.novo || 0;
            const volNego = funil.em_negociacao || 0;
            const volAgua = funil.aguardando_pagamento || 0;
            const volFech = funil.fechado || 0;

            const totalLeads = volNovo + volNego + volAgua + volFech;
            document.getElementById('kpiTotalLeads').innerText = totalLeads;

            // 3. Renderização Fluída via CSS View
            if (totalLeads > 0) {
                document.getElementById('barNovo').style.width = (volNovo / totalLeads * 100) + '%';
                document.getElementById('barNovo').innerText = volNovo > 0 ? volNovo : '';

                document.getElementById('barNegociacao').style.width = (volNego / totalLeads * 100) + '%';
                document.getElementById('barNegociacao').innerText = volNego > 0 ? volNego : '';

                document.getElementById('barAguardando').style.width = (volAgua / totalLeads * 100) + '%';
                document.getElementById('barAguardando').innerText = volAgua > 0 ? volAgua : '';

                document.getElementById('barFechado').style.width = (volFech / totalLeads * 100) + '%';
                document.getElementById('barFechado').innerText = volFech > 0 ? volFech : '';
            }
        }
    } catch (error) {
        console.error("Falha ao montar painel de métricas:", error);
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

        // Regra condicional de renderização do botão de Auditoria
        const btnAuditoria = lead.status === 'fechado' 
            ? `<button class="btn btn-sm fw-bold me-1" style="background-color: var(--tiffany-blue); color: var(--deep-blue);" onclick="abrirModalAuditoria(${lead.id})" title="Auditoria de Serviços">🔍</button>` 
            : '';

        tr.innerHTML = `
            <td class="fw-bold">${lead.nome_contato}</td>
            <td>${lead.nome_agencia}</td>
            <td>${lead.telefone}</td>
            <td class="fw-bold" style="color: var(--tiffany-blue);">${valorFormatado}</td>
            <td><span class="badge-status badge-${lead.status}">${formatarStatusBadge(lead.status)}</span></td>
            <td class="text-end" style="white-space: nowrap;">
                ${btnAuditoria}
                <button class="btn btn-sm btn-outline-primary fw-bold me-1" onclick="abrirModalAgendamento(${lead.id}, '${lead.nome_contato}')" title="Agendar Treinamento">🗓️</button>
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


// ==========================================
// MÓDULO DE AGENDAMENTO (GOOGLE AGENDA)
// ==========================================

// 1. Função que injeta dados do Lead selecionado e abre o visual do Modal
function abrirModalAgendamento(idLead, nomeAgencia) {
    // Insere o ID do Lead no input oculto para enviarmos ao back-end depois
    document.getElementById('agendaIdLead').value = idLead;

    // Monta o título padrão bloqueado
    document.getElementById('agendaTitulo').value = 'Treinamento SDR - ' + nomeAgencia;

    // Limpa a data de agendamentos anteriores
    document.getElementById('agendaDataHora').value = '';

    // Invoca a API do Bootstrap 5 para exibir o modal
    const modal = new bootstrap.Modal(document.getElementById('modalAgendamento'));
    modal.show();
}

// 2. Função que captura o clique de salvar e dispara para o Banco de Dados
async function salvarAgendamento(event) {
    event.preventDefault(); // Impede a página de recarregar

    const form = event.target;
    const btnSubmit = form.querySelector('button[type="submit"]');

    // Trava de segurança UX (Evita Duplo Clique / Spam)
    btnSubmit.disabled = true;
    btnSubmit.innerText = 'PROCESSANDO...';

    // Monta a estrutura JSON lendo os inputs do formulário
    const payload = {
        id_lead: parseInt(document.getElementById('agendaIdLead').value),
        titulo: document.getElementById('agendaTitulo').value,
        data_hora: document.getElementById('agendaDataHora').value
    };

    try {
        // Dispara a requisição Fetch para o Controlador (API)
        const response = await fetch('/api/agenda/agendar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        // Valida se o Status HTTP retornado pelo Controller foi 201 (Created)
        if (response.status === 201 && result.status === 'success') {

            // Oculta o modal nativamente utilizando a instância do Bootstrap
            const modalElement = document.getElementById('modalAgendamento');
            bootstrap.Modal.getInstance(modalElement).hide();

            alert('Treinamento agendado com sucesso!');
            form.reset();

        } else {
            // Exibe mensagem de erro barrada pelo RLS ou validação
            alert(result.message || 'Erro ao tentar agendar o treinamento.');
        }

    } catch (error) {
        console.error('Falha na requisição assíncrona:', error);
        alert('Erro de rede: Falha na comunicação com o servidor.');
    } finally {
        // Restaura o botão estritamente no bloco finally (roda dando erro ou sucesso)
        btnSubmit.disabled = false;
        btnSubmit.innerText = 'SALVAR AGENDAMENTO';
    }
}

// ==========================================
// MÓDULO DE AUDITORIA DE SERVIÇOS
// ==========================================
async function abrirModalAuditoria(idLead) {
    try {
        const response = await fetch(`/api/leads/servicos?id_lead=${idLead}`, {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        });
        
        const result = await response.json();
        const lista = document.getElementById('listaServicosAuditoria');
        lista.innerHTML = ''; // Limpa cache de aberturas anteriores

        if (response.status === 200 && result.status === 'success') {
            if (result.data.length === 0) {
                lista.innerHTML = '<li class="list-group-item text-muted">Nenhum serviço fragmentado atrelado a este Lead.</li>';
            } else {
                result.data.forEach(servico => {
                    lista.innerHTML += `
                        <li class="list-group-item p-3 border-0 border-bottom">
                            <strong style="color: var(--deep-blue); font-size: 0.95rem;">${servico.nome_servico}</strong><br>
                            <small class="text-muted" style="font-size: 0.8rem;">${servico.descricao}</small>
                        </li>`;
                });
            }
            
            const modal = new bootstrap.Modal(document.getElementById('modalAuditoriaServicos'));
            modal.show();
        } else {
            alert(result.message || 'Falha de segurança ao extrair itens.');
        }
    } catch (error) {
        console.error('Falha de comunicação RLS:', error);
        alert('Erro de rede: Falha na comunicação com o servidor.');
    }
}