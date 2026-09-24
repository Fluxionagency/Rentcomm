// Rentcom — shared lead-form handling.
// Any <form data-endpoint="..."> is intercepted, POSTed as form data, and on
// success swapped for its sibling ".hidden" thank-you block (the element whose
// id matches the form id with "-form" replaced by "-thanks").
(function () {
  function attach(form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var errorBox = form.querySelector('.form-error');
      if (errorBox) errorBox.classList.add('hidden');

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      var button = form.querySelector('button[type="submit"]');
      var originalLabel = button ? button.textContent : '';
      if (button) { button.disabled = true; button.textContent = 'Sending…'; }

      // Sent as application/x-www-form-urlencoded rather than raw FormData:
      // PHP's $_POST parses both identically for plain text fields, but the
      // Vercel serverless functions (see /api/*.js at the repo root) only
      // auto-parse urlencoded/json/text bodies, not multipart — so this one
      // request shape works unmodified against either backend.
      fetch(form.dataset.endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(new FormData(form)).toString(),
      })
        .then(function (res) { return res.json().catch(function () { return {}; }).then(function (data) { return { ok: res.ok && data.ok, data: data }; }); })
        .then(function (result) {
          if (!result.ok) throw new Error(result.data.error || 'Something went wrong. Please try again.');
          var thanks = document.getElementById(form.id.replace('-form', '-thanks'));
          form.classList.add('hidden');
          if (thanks) thanks.classList.remove('hidden');
          if (typeof form.dataset.onsuccess === 'string' && window[form.dataset.onsuccess]) {
            window[form.dataset.onsuccess]();
          }
        })
        .catch(function (err) {
          if (errorBox) {
            errorBox.textContent = err.message;
            errorBox.classList.remove('hidden');
          }
        })
        .finally(function () {
          if (button) { button.disabled = false; button.textContent = originalLabel; }
        });
    });
  }

  document.querySelectorAll('form[data-endpoint]').forEach(attach);
})();
