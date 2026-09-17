document.addEventListener('DOMContentLoaded', () => {
  if (typeof khLms === 'undefined') return;

  document.querySelectorAll('[data-kh-lms-video]').forEach(async (element) => {
    const lessonId = element.dataset.khLmsVideo;
    if (!lessonId) return;

    try {
      const response = await fetch(`${khLms.api}video-token/${encodeURIComponent(lessonId)}`, {
        method: 'POST',
        headers: { 'X-WP-Nonce': khLms.nonce, 'Content-Type': 'application/json' }
      });
      if (!response.ok) return;
      const data = await response.json();
      if (data.token) element.setAttribute('src', `${khLms.api}video/${encodeURIComponent(data.token)}`);
    } catch (error) {
      // Do not expose token or source details to the page.
      console.warn('Khorshid LMS player initialization failed');
    }
  });
});
