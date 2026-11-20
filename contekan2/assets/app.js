const $ = (s, r = document) => r.querySelector(s);

// 1. Tema gelap/terang disimpan di cookie
const setTheme = t => { document.documentElement.setAttribute('data-theme', t); document.cookie = 'theme=' + t + ';path=/;max-age=2592000'; };
$('#themeBtn')?.addEventListener('click', () => setTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark'));

// 2. Pencarian/filter otomatis (debounce)
let timer;
document.querySelectorAll('[data-live]').forEach(el => el.addEventListener('input', () => {
  clearTimeout(timer); timer = setTimeout(() => el.form.submit(), el.tagName === 'SELECT' ? 0 : 450);
}));
const q = $('input[name=q]');
if (q && q.value) { q.focus(); q.setSelectionRange(q.value.length, q.value.length); }

// 3. Panel notifikasi + tandai dibaca (fetch/AJAX)
const nb = $('#notifBtn'), np = $('#notifPanel');
nb?.addEventListener('click', e => {
  e.stopPropagation(); np.classList.toggle('open');
  if (np.classList.contains('open')) fetch(location.pathname, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'notif_read=1' }).then(() => $('#notifBadge')?.remove());
});
document.addEventListener('click', e => { if (np && !np.contains(e.target)) np.classList.remove('open'); });

// 4. Tutup pesan notice
document.querySelectorAll('.notice-close').forEach(b => b.addEventListener('click', () => b.parentElement.remove()));

// 5. Modal konfirmasi hapus menu
document.querySelectorAll('[data-del]').forEach(b => b.addEventListener('click', () => {
  $('#delId').value = b.dataset.del; $('#delName').textContent = b.dataset.name; $('#delModal').classList.add('open');
}));
document.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => $('#delModal').classList.remove('open')));

// 6. Validasi form sebelum dikirim
document.querySelectorAll('form[data-validate]').forEach(f => f.addEventListener('submit', ev => {
  if (!f.checkValidity()) { ev.preventDefault(); f.reportValidity(); }
}));
