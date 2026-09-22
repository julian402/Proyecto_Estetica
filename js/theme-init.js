/**
 * Aplica el tema guardado antes de pintar, para evitar el parpadeo claro/oscuro.
 *
 * Va en un archivo aparte porque la política de seguridad del sitio
 * (Content-Security-Policy: script-src 'self') no ejecuta scripts en línea.
 */
if (localStorage.getItem('admin_theme') === 'dark') {
  document.documentElement.classList.add('dark-mode');
}
