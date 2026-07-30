document.querySelectorAll('.toggle-ferme').forEach(cb => {
  cb.addEventListener('change', function() {
    const row = this.closest('tr');
    row.querySelectorAll('input[type="time"]').forEach(i => i.disabled = this.checked);
  });
});