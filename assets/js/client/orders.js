
// Fonction de maj des statuts (polling)
// Mets à jour les badges et barres de suivi de cmd
async function pollStatuts() {
  // Si l’utilisateur n’a aucune cmd, on ne fait rien
  if (!orderIds.length) return;

  try {
    // On demande à l’API les statuts des commandes
    // Exemple d’URL : `../api/order_status.php?ids=1,2,3`
    const url = '../api/order_status.php?ids=' + orderIds.join(',');

    const response = await fetch(url);
    const items = await response.json(); // { id, badge, track }

    // On met à jour chaque commande dans le DOM
    items.forEach(item => {
      const badgeEl = document.getElementById('badge-' + item.id);
      const trackEl = document.getElementById('track-' + item.id);

      // Mettre à jour le badge (état affiché)
      if (badgeEl) {
        badgeEl.innerHTML = item.badge;
      }

      // Mettre à jour le suivi de livraison
      if (trackEl) {
        trackEl.innerHTML = item.track;
      }
    });
  } catch (e) {
    // Si il y a une erreur (réseau, etc.), on l’ignore
    // le site continue de fonctionner
  }
}

// Appel régulier (polling) toutes les 5 secondes 
// On relance cette fonction toutes les 5000 ms
setInterval(pollStatuts, 5000);


// Animation du point “en direct”
let blink = true;

setInterval(() => {
  const dot = document.getElementById('live-dot');

  // Si l’élément existe, on alterne sa couleur
  if (dot) {
    dot.textContent = (blink ? '🔴' : '🔵') + ' Suivi en direct';
  }

  // Pour la prochaine fois, inverser la couleur
  blink = !blink;
}, 1500);