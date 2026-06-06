document.addEventListener('DOMContentLoaded', () => {
    const btnCopiar = document.getElementById('btnCopiarLink');
    if (btnCopiar) {
        btnCopiar.addEventListener('click', function() {
            const input = document.getElementById('checkoutUrl');
            input.select();
            input.setSelectionRange(0, 99999); // Mobile
            navigator.clipboard.writeText(input.value).then(() => {
                btnCopiar.innerText = 'LINK COPIADO!';
                btnCopiar.style.backgroundColor = '#1A3A52';
                btnCopiar.style.color = '#FFFFFF';
                setTimeout(() => {
                    btnCopiar.innerText = 'COPIAR LINK PARA WHATSAPP';
                    btnCopiar.style.backgroundColor = 'var(--tiffany-blue)';
                    btnCopiar.style.color = 'var(--deep-blue)';
                }, 3000);
            });
        });
    }
});