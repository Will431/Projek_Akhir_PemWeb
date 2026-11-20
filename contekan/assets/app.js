// 1. Tema gelap/terang disimpan di cookie
const setTheme = t => { document.documentElement.setAttribute('data-bs-theme', t); document.cookie = 'theme=' + t + ';path=/;max-age=2592000'; };
document.getElementById('themeBtn')?.addEventListener('click', () =>
  setTheme(document.documentElement.dataset.bsTheme === 'dark' ? 'light' : 'dark'));

// 2. Pencarian/filter otomatis (debounce)
let timer;
document.querySelectorAll('[data-live]').forEach(el => el.addEventListener('input', () => {
  clearTimeout(timer); timer = setTimeout(() => el.form.submit(), el.tagName === 'SELECT' ? 0 : 450);
}));
const q = document.querySelector('input[name=q]');
if (q && q.value) { q.focus(); q.setSelectionRange(q.value.length, q.value.length); }

// 3. Tandai notifikasi dibaca (fetch/AJAX)
document.getElementById('notifBtn')?.addEventListener('show.bs.dropdown', () =>
  fetch('notif_read.php', { method: 'POST' }).then(() => document.getElementById('notifBadge')?.remove()));

// 4. Modal konfirmasi hapus menu
document.querySelectorAll('[data-del]').forEach(b => b.addEventListener('click', () => {
  document.getElementById('delId').value = b.dataset.del;
  document.getElementById('delName').textContent = b.dataset.name;
  new bootstrap.Modal('#delModal').show();
}));

// 5. Validasi form sederhana
document.querySelectorAll('form[data-validate]').forEach(f => f.addEventListener('submit', ev => {
  if (!f.checkValidity()) { ev.preventDefault(); f.classList.add('was-validated'); }
}));
