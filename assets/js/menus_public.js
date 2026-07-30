
// ÉTAT DES FILTRES
// Cet objet centralise tous les filtres actifs.

let filtres = {
    categorie : 'all',  // 'all' ou nom d'une catégorie
    regimes   : [],     // tableau des régimes cochés
    allergenes: [],     // tableau des allergènes à exclure
    persMin   : 1       // nombre minimum de personnes
};



// FONCTION PRINCIPALE : appliquer les filtres
// Parcours toutes les cartes et les affiche/masque selon filtres

function applyFilter() {
    let nbVisibles = 0;

    document.querySelectorAll('.menu-item-wrap').forEach(carte => {
        // Lit les attributs data-* de la carte
        const allergenesCarte = carte.dataset.allergens.split(',').map(s => s.trim()).filter(Boolean);
        const regimesCarte    = carte.dataset.regimes.split(',').map(s => s.trim()).filter(Boolean);
        const persCarte       = parseInt(carte.dataset.pers) || 1;
        const catCarte        = carte.dataset.cat;

        let afficher = true;

        // Test 1 : catégorie
        if (filtres.categorie !== 'all' && catCarte !== filtres.categorie) {
            afficher = false;
        }

        // Test 2 : nombre de personnes minimum
        if (afficher && persCarte < filtres.persMin) {
            afficher = false;
        }

        // Test 3 : régimes (la carte doit avoir TOUS les régimes cochés)
        for (const regime of filtres.regimes) {
            if (!regimesCarte.includes(regime)) {
                afficher = false;
                break;
            }
        }

        // Test 4 : allergènes (la carte ne doit contenir AUCUN allergène exclu)
        for (const allergene of filtres.allergenes) {
            if (allergenesCarte.includes(allergene)) {
                afficher = false;
                break;
            }
        }

        // Applique ou retire la classe CSS qui masque la carte
        carte.classList.toggle('hidden', !afficher);
        if (afficher) nbVisibles++;
    });

    // Met à jour le compteur de résultats
    const total = document.querySelectorAll('.menu-item-wrap').length;
    const compteur = document.getElementById('results-count');
    compteur.textContent = (nbVisibles === total)
        ? ''
        : `${nbVisibles} plat${nbVisibles > 1 ? 's' : ''} trouvé${nbVisibles > 1 ? 's' : ''}`;

    // Affiche/masque le message "aucun résultat"
    document.getElementById('no-results').style.display  = (nbVisibles === 0) ? '' : 'none';
    document.getElementById('menu-grid').style.display   = (nbVisibles === 0) ? 'none' : '';
}



// GESTION DES DROPDOWNS (ouvrir/fermer)

// Ouvre un dropdown et ferme tous les autres
function toggleDrop(id) {
    const btn   = document.getElementById('btn-'   + id);
    const panel = document.getElementById('panel-' + id);
    const estOuvert = panel.classList.contains('open');

    // Ferme tous les dropdowns
    document.querySelectorAll('.fbar-panel').forEach(p => p.classList.remove('open'));
    document.querySelectorAll('.fbar-btn').forEach(b => b.classList.remove('open'));

    // Ouvre celui-ci uniquement s'il était fermé
    if (!estOuvert) {
        panel.classList.add('open');
        btn.classList.add('open');
    }
}

// Ferme tous les dropdowns si on clique ailleurs sur la page
document.addEventListener('click', e => {
    if (!e.target.closest('.fbar-drop')) {
        document.querySelectorAll('.fbar-panel').forEach(p => p.classList.remove('open'));
        document.querySelectorAll('.fbar-btn').forEach(b => b.classList.remove('open'));
    }
});



// FILTRE CATÉGORIE (boutons radio)

document.querySelectorAll('#panel-cat input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', () => {
        filtres.categorie = radio.value;

        // Met à jour le style visuel des options
        document.querySelectorAll('#panel-cat .fbar-option').forEach(o => o.classList.remove('selected'));
        radio.closest('.fbar-option').classList.add('selected');

        // Met à jour le libellé du bouton
        const texteOption = radio.closest('.fbar-option').textContent.trim();
        document.getElementById('label-cat').textContent = (radio.value === 'all') ? 'Catégorie' : texteOption;
        document.getElementById('btn-cat').classList.toggle('has-selection', radio.value !== 'all');

        applyFilter();
    });
});

// Sélection visuelle par défaut : "Toutes catégories"
document.querySelector('#panel-cat .fbar-option').classList.add('selected');



// FILTRE RÉGIME (cases à cocher)

document.querySelectorAll('#panel-regime input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', () => {
        const valeur = checkbox.dataset.value;

        checkbox.closest('.fbar-option').classList.toggle('selected', checkbox.checked);

        // Ajoute ou retire la valeur du tableau filtres.regimes
        if (checkbox.checked) {
            filtres.regimes.push(valeur);
        } else {
            filtres.regimes = filtres.regimes.filter(r => r !== valeur);
        }

        // Met à jour le libellé du bouton
        const nb = filtres.regimes.length;
        document.getElementById('label-regime').textContent = nb ? `Régime (${nb})` : 'Régime';
        document.getElementById('btn-regime').classList.toggle('has-selection', nb > 0);

        applyFilter();
    });
});



// FILTRE ALLERGÈNES (cases à cocher)

document.querySelectorAll('#panel-allergen input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', () => {
        const valeur = checkbox.dataset.value;

        checkbox.closest('.fbar-option').classList.toggle('selected', checkbox.checked);

        if (checkbox.checked) {
            filtres.allergenes.push(valeur);
        } else {
            filtres.allergenes = filtres.allergenes.filter(a => a !== valeur);
        }

        const nb = filtres.allergenes.length;
        document.getElementById('label-allergen').textContent = nb ? `Allergènes (${nb})` : 'Allergènes';
        document.getElementById('btn-allergen').classList.toggle('has-selection', nb > 0);

        applyFilter();
    });
});


//
// FILTRE PERSONNES (slider)

const slider      = document.getElementById('pers-slider');
const affichagePers = document.getElementById('pers-display');

if (slider) {
    slider.addEventListener('input', () => {
        const valeur = parseInt(slider.value);
        filtres.persMin = valeur;

        affichagePers.textContent = (valeur === 1) ? '1+ pers.' : valeur + '+ pers.';
        document.getElementById('label-pers').textContent = (valeur === 1) ? '👥 Personnes' : `👥 ${valeur}+ pers.`;
        document.getElementById('btn-pers').classList.toggle('has-selection', valeur > 1);

        applyFilter();
    });
}



// RÉINITIALISATION DES FILTRES
// Remet tout à zéro : état JS + interface visuelle

function reinitialiserFiltres() {
    // Remet l'objet filtres à son état initial
    filtres = { categorie: 'all', regimes: [], allergenes: [], persMin: 1 };

    // Remet le radio "Toutes catégories" coché
    const radioAll = document.querySelector('#panel-cat input[value="all"]');
    if (radioAll) radioAll.checked = true;
    document.querySelectorAll('#panel-cat .fbar-option').forEach(o => o.classList.remove('selected'));
    if (radioAll) radioAll.closest('.fbar-option').classList.add('selected');
    document.getElementById('label-cat').textContent = 'Catégorie';
    document.getElementById('btn-cat').classList.remove('has-selection');

    // Décoche toutes les cases régime
    document.querySelectorAll('#panel-regime input').forEach(cb => {
        cb.checked = false;
        cb.closest('.fbar-option').classList.remove('selected');
    });
    document.getElementById('label-regime').textContent = 'Régime';
    document.getElementById('btn-regime').classList.remove('has-selection');

    // Décoche toutes les cases allergènes
    document.querySelectorAll('#panel-allergen input').forEach(cb => {
        cb.checked = false;
        cb.closest('.fbar-option').classList.remove('selected');
    });
    document.getElementById('label-allergen').textContent = 'Allergènes';
    document.getElementById('btn-allergen').classList.remove('has-selection');

    // Remet le slider à 1
    if (slider) {
        slider.value = 1;
        affichagePers.textContent = '1+ pers.';
    }
    const labelPers = document.getElementById('label-pers');
    const btnPers   = document.getElementById('btn-pers');
    if (labelPers) labelPers.textContent = '👥 Personnes';
    if (btnPers)   btnPers.classList.remove('has-selection');

    applyFilter();
}

document.getElementById('btn-reset').addEventListener('click', reinitialiserFiltres);
document.getElementById('btn-reset2').addEventListener('click', reinitialiserFiltres);



// MODAL DÉTAIL
// Quand on clique sur "Détails", on lit les data-* du bouton
// et on remplit le modal avec ces infos.

const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));

document.querySelectorAll('.btn-detail').forEach(btn => {
    btn.addEventListener('click', () => {
        const d = btn.dataset; // raccourci vers tous les data-*

        // Nom et catégorie
        document.getElementById('m-nom').textContent = d.nom;
        document.getElementById('m-cat').textContent = d.cat;
        document.getElementById('m-desc').textContent = d.desc;

        // Prix (avec ou sans "par personne")
        const nbPers  = parseInt(d.pers) || 1;
        const zoneKrix = document.getElementById('m-prix');
        if (d.ppp) {
            // Formule : prix par personne + total
            zoneKrix.innerHTML = `
                <span style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--gold);font-weight:700;">${d.ppp} €</span>
                <span style="font-size:.8rem;color:#888;font-weight:400;display:block;">par personne &nbsp;•&nbsp; Total ${d.prix} €</span>
            `;
        } else {
            zoneKrix.innerHTML = `
                <span style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--gold);font-weight:700;">${d.prix} €</span>
            `;
        }

        // Badge "À partir de X personnes"
        document.getElementById('m-pers').innerHTML = (nbPers > 1)
            ? `<span class="pers-tag" style="background:linear-gradient(90deg,${d.color},${d.color}cc);color:#fff;border:none;font-size:.8rem;padding:5px 12px;">
                   👥 À partir de ${nbPers} personnes
               </span>`
            : `<span class="pers-tag">👤 Par personne</span>`;

        // Image ou placeholder coloré
        const zoneImage = document.getElementById('m-img-wrap');
        if (d.img) {
            zoneImage.innerHTML = `<img src="${d.img}" alt="${d.nom}" style="width:100%;height:100%;min-height:300px;object-fit:cover;">`;
        } else {
            zoneImage.innerHTML = `<div style="height:100%;min-height:300px;background:${d.color};display:flex;align-items:center;justify-content:center;font-size:5rem;">${d.emoji}</div>`;
        }

        // Régimes alimentaires
        const regimes = d.regimes.split(',').map(s => s.trim()).filter(Boolean);
        document.getElementById('m-regimes').innerHTML = regimes.length
            ? regimes.map(r => {
                const info = REGIMES_INFO[r] || { e: '', l: r };
                return `<span class="regime-tag">${info.e} ${info.l}</span>`;
              }).join('')
            : '<span class="text-muted small">Aucune mention spécifique</span>';

        // Allergènes
        const allergenes = d.allergens.split(',').map(s => s.trim()).filter(Boolean);
        document.getElementById('m-allergens').innerHTML = allergenes.length
            ? allergenes.map(a => {
                const info = ALLERGENS_INFO[a] || { e: '⚠️', l: a };
                return `<span class="allergen-tag">${info.e} ${info.l}</span>`;
              }).join('')
            : '<span class="regime-tag">✅ Aucun allergène majeur déclaré</span>';

        // Bouton d'action selon état de connexion
        document.getElementById('m-cta').innerHTML = EST_CONNECTE
            ? `<a href="user/menu.php" class="btn btn-gold btn-lg w-100">
                   <i class="bi bi-cart-plus me-2"></i>Commander ce plat
               </a>`
            : `<a href="login.php" class="btn btn-lg w-100 fw-bold" style="border:2px solid var(--dark);color:var(--dark);">
                   <i class="bi bi-lock me-2"></i>Se connecter pour commander
               </a>
               <p class="text-center small text-muted mt-2">
                   Pas de compte ? <a href="register.php" style="color:var(--gold);">S'inscrire gratuitement</a>
               </p>`;

        detailModal.show();
    });
});

applyFilter();