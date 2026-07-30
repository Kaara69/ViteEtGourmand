document.querySelectorAll('.statut-select').forEach(sel => {
    sel.addEventListener('change', async function() {
        const fb = this.parentElement.querySelector('.statut-feedback');
        const fd = new FormData();
        fd.append('ajax_statut','1'); fd.append('id',this.dataset.id); fd.append('statut',this.value);
        const d = await (await fetch('orders.php',{method:'POST',body:fd})).json();
        if (d.ok && fb) { fb.textContent='✓ Sauvegardé'; setTimeout(()=>fb.textContent='',2000); }
    });
});