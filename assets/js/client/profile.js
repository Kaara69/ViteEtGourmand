const labels = ['Très mauvais','Mauvais','Moyen','Bien','Excellent'];
const stars  = document.querySelectorAll('.star-label');
const noteInput = document.getElementById('note-val');
const noteText  = document.getElementById('note-text');

function setNote(val) {
  noteInput.value = val;
  noteText.textContent = val + '/5 – ' + labels[val-1];
  stars.forEach(s => s.classList.toggle('active', parseInt(s.dataset.val) <= val));
}
stars.forEach(s => {
  s.addEventListener('click',      () => setNote(parseInt(s.dataset.val)));
  s.addEventListener('mouseenter', () => stars.forEach(x => x.classList.toggle('active', parseInt(x.dataset.val) <= parseInt(s.dataset.val))));
  s.addEventListener('mouseleave', () => setNote(parseInt(noteInput.value)));
});
setNote(5);