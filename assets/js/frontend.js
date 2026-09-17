document.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('kh-curriculum');
  if (!root) return;

  const addChapterBtn = document.getElementById('kh-add-chapter');
  let chapterIndex = root.querySelectorAll('.kh-chapter').length;

  addChapterBtn?.addEventListener('click', () => {
    const chapter = document.createElement('div');
    chapter.className = 'kh-chapter';
    chapter.dataset.order = String(chapterIndex);
    chapter.innerHTML = `
      <div class="kh-chapter-header">
        <input type="text" name="chapters[${chapterIndex}][title]" placeholder="عنوان فصل" required>
      </div>
      <div class="kh-lessons">
        <div class="kh-lesson">
          <input type="text" name="chapters[${chapterIndex}][lessons][0][title]" placeholder="عنوان درس" required>
          <select name="chapters[${chapterIndex}][lessons][0][content_type]">
            <option value="text">متن</option>
            <option value="video">ویدئو</option>
          </select>
          <input type="text" name="chapters[${chapterIndex}][lessons][0][video_url]" placeholder="URL ویدئو">
          <label><input type="checkbox" name="chapters[${chapterIndex}][lessons][0][is_preview]" value="1"> پیش‌نمایش</label>
        </div>
      </div>
      <button type="button" class="button kh-add-lesson">افزودن درس</button>
    `;
    root.appendChild(chapter);
    chapterIndex += 1;
  });

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;

    if (target.classList.contains('kh-add-lesson')) {
      const chapter = target.closest('.kh-chapter');
      if (!chapter) return;

      const lessonsWrap = chapter.querySelector('.kh-lessons');
      const chapterKey = chapter.dataset.order;
      const lessonCount = chapter.querySelectorAll('.kh-lesson').length;

      const lesson = document.createElement('div');
      lesson.className = 'kh-lesson';
      lesson.innerHTML = `
        <input type="text" name="chapters[${chapterKey}][lessons][${lessonCount}][title]" placeholder="عنوان درس" required>
        <select name="chapters[${chapterKey}][lessons][${lessonCount}][content_type]">
          <option value="text">متن</option>
          <option value="video">ویدئو</option>
        </select>
        <input type="text" name="chapters[${chapterKey}][lessons][${lessonCount}][video_url]" placeholder="URL ویدئو">
        <label><input type="checkbox" name="chapters[${chapterKey}][lessons][${lessonCount}][is_preview]" value="1"> پیش‌نمایش</label>
      `;
      lessonsWrap.appendChild(lesson);
    }
  });
});
