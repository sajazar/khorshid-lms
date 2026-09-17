document.addEventListener('DOMContentLoaded', function () {
  var root = document.getElementById('kh-curriculum');
  if (!root) return;

  var addChapter = document.getElementById('kh-add-chapter');
  var chapterIndex = root.querySelectorAll('.kh-chapter').length;

  function lessonMarkup(chapterKey, lessonKey) {
    return '<input type="text" name="chapters[' + chapterKey + '][lessons][' + lessonKey + '][title]" placeholder="عنوان درس" required>' +
      '<select name="chapters[' + chapterKey + '][lessons][' + lessonKey + '][content_type]"><option value="text">متن</option><option value="video">ویدئو</option></select>' +
      '<input type="url" name="chapters[' + chapterKey + '][lessons][' + lessonKey + '][video_url]" placeholder="URL ویدئو">' +
      '<label><input type="checkbox" name="chapters[' + chapterKey + '][lessons][' + lessonKey + '][is_preview]" value="1"> پیش‌نمایش</label>';
  }

  function addLesson(chapter) {
    var lessons = chapter.querySelector('.kh-lessons');
    if (!lessons) return;
    var chapterKey = chapter.dataset.order;
    var lessonKey = lessons.querySelectorAll('.kh-lesson').length;
    var row = document.createElement('div');
    row.className = 'kh-lesson';
    row.innerHTML = lessonMarkup(chapterKey, lessonKey);
    lessons.appendChild(row);
  }

  addChapter && addChapter.addEventListener('click', function () {
    var chapter = document.createElement('div');
    chapter.className = 'kh-chapter';
    chapter.dataset.order = String(chapterIndex++);
    chapter.innerHTML = '<div class="kh-chapter-header"><input type="text" name="chapters[' + chapter.dataset.order + '][title]" placeholder="عنوان فصل" required></div><div class="kh-lessons"></div><button type="button" class="button kh-add-lesson">افزودن درس</button>';
    root.appendChild(chapter);
    addLesson(chapter);
  });

  root.addEventListener('click', function (event) {
    var target = event.target;
    if (target && target.classList.contains('kh-add-lesson')) {
      var chapter = target.closest('.kh-chapter');
      if (chapter) addLesson(chapter);
    }
  });

  // Re-index names before submit. This prevents deleted/reordered DOM indexes
  // from producing chapters without lessons in PHP's nested POST array.
  var form = root.closest('form');
  if (form) {
    form.addEventListener('submit', function () {
      root.querySelectorAll('.kh-chapter').forEach(function (chapter, chapterKey) {
        chapter.dataset.order = String(chapterKey);
        var chapterTitle = chapter.querySelector(':scope > .kh-chapter-header input');
        if (chapterTitle) chapterTitle.name = 'chapters[' + chapterKey + '][title]';
        chapter.querySelectorAll('.kh-lesson').forEach(function (lesson, lessonKey) {
          lesson.querySelectorAll('input, select, textarea').forEach(function (field) {
            var match = field.name.match(/\[([^\]]+)\]$/);
            var key = match ? match[1] : '';
            if (key) field.name = 'chapters[' + chapterKey + '][lessons][' + lessonKey + '][' + key + ']';
          });
        });
      });
    });
  }
});
