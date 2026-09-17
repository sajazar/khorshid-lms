document.addEventListener('DOMContentLoaded', () => {
  const elements = document.querySelectorAll('[data-kh-lms-video]');
  elements.forEach(async (element) => {
    const lessonId = element.dataset.khLmsVideo;
    if (!lessonId || typeof khLms === 'undefined') return;

    try {
      const response = await fetch(`${khLms.api}video-token/${lessonId}`, {
        method: 'POST',
        headers: {
          'X-WP-Nonce': khLms.nonce,
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) return;
      const data = await response.json();
      if (data.token) {
        element.setAttribute('src', `${khLms.api}video/${encodeURIComponent(data.token)}`);
      }
    } catch (error) {
      console.warn('Khorshid LMS video token request failed', error);
    }
  });
});
