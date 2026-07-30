const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));

document.querySelectorAll('.btn-detail').forEach(btn => {
  btn.addEventListener('click', () => {
    const d = btn.dataset;

    document.getElementById('m-cat').textContent  = d.cat;
    document.getElementById('m-nom').textContent  = d.nom;
    document.getElementById('m-desc').textContent = d.desc;

    // Prix - par personne si applicable
    const prixEl = document.getElementById('m-prix');
    if (d.ppp) {
      prixEl.innerHTML = `
        <span style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--gold);font-weight:700;">${d.ppp} €</span>
        <span style="font-size:.8rem;color:#888;display:block;">par personne &nbsp;•&nbsp; Total ${d.prix} €</span>`;
    } else {
      prixEl.innerHTML = `<span style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--gold);font-weight:700;">${d.prix} €</span>`;
    }

    // Badge minimum personnes
    const pers = parseInt(d.pers) || 1;
    document.getElementById('m-pers').innerHTML = pers > 1
      ? `<span style="display:inline-flex;align-items:center;gap:.3rem;background:linear-gradient(90deg,${d.color},${d.color}cc);color:#fff;border-radius:50px;padding:3px 10px;font-size:.75rem;font-weight:700;">👥 À partir de ${pers} personnes</span>`
      : '';

    // Image
    const iw = document.getElementById('m-img-wrap');
    iw.innerHTML = d.img
      ? `<img src="${d.img}" alt="${d.nom}" style="width:100%;height:100%;min-height:300px;object-fit:cover;border-radius:0;">`
      : `<div style="height:100%;min-height:300px;background:${d.color};display:flex;align-items:center;justify-content:center;font-size:5rem;border-radius:0;">${d.emoji}</div>`;

    // Régimes
    const rgs = d.regimes.split(',').map(s => s.trim()).filter(Boolean);
    document.getElementById('m-regimes').innerHTML = rgs.length
      ? rgs.map(r => { const x = RG[r]||{e:'',l:r}; return `<span style="display:inline-flex;align-items:center;gap:.2rem;background:#E8F5E9;border:1px solid #A5D6A7;color:#1B5E20;border-radius:6px;padding:3px 9px;font-size:.78rem;font-weight:600;margin:2px;">${x.e} ${x.l}</span>`; }).join('')
      : '<span class="text-muted small">Aucune mention spécifique</span>';

    // Allergènes
    const als = d.allergens.split(',').map(s => s.trim()).filter(Boolean);
    document.getElementById('m-allergens').innerHTML = als.length
      ? als.map(a => { const x = AL[a]||{e:'⚠️',l:a}; return `<span style="display:inline-flex;align-items:center;gap:.2rem;background:#FFF3E0;border:1px solid #FFCC80;color:#BF360C;border-radius:6px;padding:3px 9px;font-size:.78rem;font-weight:600;margin:2px;">${x.e} ${x.l}</span>`; }).join('')
      : '<span style="display:inline-flex;align-items:center;gap:.2rem;background:#E8F5E9;border:1px solid #A5D6A7;color:#1B5E20;border-radius:6px;padding:3px 9px;font-size:.78rem;font-weight:600;margin:2px;">✅ Aucun allergène majeur déclaré</span>';

    // CTA - bouton ajouter au panier depuis le modal
    document.getElementById('m-cta').innerHTML = `
      <button class="btn w-100 fw-bold text-white"
              style="background:${d.color};"
              data-bs-dismiss="modal"
              onclick="cartAction({action:'add',menu_id:'${btn.dataset.id ?? ''}'})">
        <i class="bi bi-cart-plus me-2"></i>Ajouter au panier
      </button>`;

    detailModal.show();
  });
});