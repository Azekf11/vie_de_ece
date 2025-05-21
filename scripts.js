document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('commentForm');
  if (!form) return;

  const commentsList = document.getElementById('commentsList');
  const errorBox     = document.getElementById('commentError');

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    errorBox.textContent = '';
    errorBox.classList.add('d-none');

    fetch(form.action, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: new FormData(form)
    })
    .then(res => res.json())
    .then(json => {
      if (json.error) {
        errorBox.textContent = json.error;
        errorBox.classList.remove('d-none');
      } else if (json.html) {
        commentsList.insertAdjacentHTML('beforeend', json.html);
        form.reset();
      }
    })
    .catch(() => {
      errorBox.textContent = 'Erreur lors de l’envoi.';
      errorBox.classList.remove('d-none');
    });
  });
});
