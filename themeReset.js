// Reset dark mode on logout (dipanggil di semua halaman)
function resetDarkMode() {
    document.documentElement.classList.remove('dark');
    localStorage.removeItem('theme');
}