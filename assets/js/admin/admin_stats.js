Chart.defaults.font.family = "'Nunito', sans-serif";

// Graphique 1 : Barres (comparaison menus)
const barCtx = document.getElementById('barChart');
let barMode  = 'qty';
let barChart;

function buildBarChart(mode) {
  if (barChart) barChart.destroy();
  barChart = new Chart(barCtx, {
    type: 'bar',
    data: {
      labels: barLabels,
      datasets: [{
        label: mode === 'qty' ? 'Articles vendus' : 'Chiffre d\'affaires (€)',
        data:  mode === 'qty' ? barQty : barCA,
        backgroundColor: barColors,
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => mode === 'ca'
              ? ctx.parsed.y.toFixed(2).replace('.',',') + ' €'
              : ctx.parsed.y + ' article(s)'
          }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { maxRotation: 30 } },
        y: {
          beginAtZero: true,
          ticks: {
            callback: v => mode === 'ca' ? v + ' €' : v
          }
        }
      }
    }
  });
}

buildBarChart('qty');

document.querySelectorAll('#bar-toggle button').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('#bar-toggle button').forEach(b => b.className='btn btn-outline-secondary');
    this.className = 'btn btn-dark active';
    buildBarChart(this.dataset.chart);
  });
});

// Graphique 2 : Camembert (répartition CA)
if (pieLabels.length > 0) {
  new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
      labels: pieLabels,
      datasets: [{ data: pieValues, backgroundColor: pieColors, borderWidth: 2, borderColor: '#fff' }]
    },
    options: {
      cutout: '55%',
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8, font: { size: 11 } } },
        tooltip: {
          callbacks: {
            label: ctx => ' ' + ctx.parsed.toFixed(2).replace('.',',') + ' €'
          }
        }
      }
    }
  });
}

// Graphique 3 : Ligne (évolution temporelle)
const lineCtx = document.getElementById('lineChart');
let lineMode  = 'ca';
let lineChart;

function buildLineChart(mode) {
  if (lineChart) lineChart.destroy();
  if (!lineCtx) return;
  lineChart = new Chart(lineCtx, {
    type: 'line',
    data: {
      labels: lineLabels,
      datasets: [{
        label: mode === 'ca' ? 'CA (€)' : 'Articles vendus',
        data:  mode === 'ca' ? lineCA  : lineQty,
        borderColor: '#C9973D',
        backgroundColor: 'rgba(201,151,61,.1)',
        tension: 0.35,
        fill: true,
        pointBackgroundColor: '#C9973D',
        pointRadius: lineLabels.length > 30 ? 2 : 5,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => mode === 'ca'
              ? ' ' + ctx.parsed.y.toFixed(2).replace('.',',') + ' €'
              : ' ' + ctx.parsed.y + ' article(s)'
          }
        }
      },
      scales: {
        x: { grid: { display: false } },
        y: {
          beginAtZero: true,
          ticks: { callback: v => mode === 'ca' ? v + ' €' : v }
        }
      }
    }
  });
}

if (lineLabels.length > 0) buildLineChart('ca');

document.querySelectorAll('#line-toggle button').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('#line-toggle button').forEach(b => b.className='btn btn-outline-secondary');
    this.className = 'btn btn-dark active';
    buildLineChart(this.dataset.chart);
  });
});