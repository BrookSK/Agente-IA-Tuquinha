// Lesson Mention Autocomplete - Hierarchical (Course > Lesson)
(function(){
    const textarea = document.getElementById('replyTextarea');
    const dropdown = document.getElementById('lessonMentionDropdown');
    console.log('Lesson mention autocomplete initializing...');
    if (!textarea || !dropdown) {
        console.error('Textarea or dropdown not found!');
        return;
    }
    console.log('Autocomplete initialized successfully');

    let courses = [];
    let currentCourse = null;
    let currentLessons = [];
    let mentionStart = -1;
    let selectedIndex = 0;
    let mode = 'course';

    async function fetchCourses() {
        try {
            console.log('Fetching enrolled courses...');
            const response = await fetch('/api/courses/enrolled');
            console.log('Response status:', response.status);
            if (response.ok) {
                courses = await response.json();
                console.log('Courses loaded:', courses);
            } else {
                console.error('Failed to fetch courses, status:', response.status);
            }
        } catch (e) {
            console.error('Failed to fetch courses:', e);
        }
    }
    fetchCourses();

    async function fetchLessonsForCourse(courseId) {
        try {
            console.log('Fetching lessons for course:', courseId);
            const response = await fetch('/api/courses/' + courseId + '/lessons');
            console.log('Lessons response status:', response.status);
            if (response.ok) {
                const lessons = await response.json();
                console.log('Lessons loaded:', lessons);
                return lessons;
            } else {
                console.error('Failed to fetch lessons, status:', response.status);
            }
        } catch (e) {
            console.error('Failed to fetch lessons:', e);
        }
        return [];
    }

    function getCaretPosition() {
        return textarea.selectionStart;
    }

    function setCaretPosition(pos) {
        textarea.setSelectionRange(pos, pos);
        textarea.focus();
    }

    function getCurrentWord() {
        const pos = getCaretPosition();
        const text = textarea.value;
        let start = pos;
        while (start > 0 && text[start - 1] !== ' ' && text[start - 1] !== '\n') {
            start--;
        }
        return { start, word: text.substring(start, pos) };
    }

    function showCourseDropdown(filteredCourses) {
        if (filteredCourses.length === 0) {
            dropdown.style.display = 'none';
            return;
        }

        mode = 'course';
        selectedIndex = 0;
        dropdown.innerHTML = `<div style="padding: 6px 12px; font-size: 11px; color: #b0b0b0; border-bottom: 1px solid #272727;">Selecione o curso:</div>` +
            filteredCourses.map((course, idx) => 
                `<div class="course-mention-item" data-course-id="${course.id}" data-index="${idx}" style="padding: 8px 12px; cursor: pointer; font-size: 13px; color: #f5f5f5; ${idx === 0 ? 'background: #1a1a24;' : ''}">
                    <div style="font-weight: 600;">${course.title}</div>
                    <div style="font-size: 11px; color: #b0b0b0;">→ Ver aulas</div>
                </div>`
            ).join('');

        const rect = textarea.getBoundingClientRect();
        dropdown.style.top = (rect.height + 4) + 'px';
        dropdown.style.left = '0px';
        dropdown.style.display = 'block';

        attachCourseHandlers(filteredCourses);
    }

    function showLessonDropdown(lessons) {
        if (lessons.length === 0) {
            dropdown.innerHTML = '<div style="padding: 12px; font-size: 12px; color: #b0b0b0;">Nenhuma aula encontrada</div>';
            return;
        }

        mode = 'lesson';
        selectedIndex = 0;
        dropdown.innerHTML = `<div style="padding: 6px 12px; font-size: 11px; color: #b0b0b0; border-bottom: 1px solid #272727; display: flex; justify-content: space-between; align-items: center;">
                <span>Selecione a aula:</span>
                <button onclick="event.stopPropagation(); document.getElementById('lessonMentionDropdown').style.display='none';" style="background: none; border: none; color: #ff6f60; cursor: pointer; font-size: 11px;">← Voltar</button>
            </div>` +
            lessons.map((lesson, idx) => 
                `<div class="lesson-mention-item" data-lesson='${JSON.stringify(lesson)}' data-index="${idx}" style="padding: 8px 12px; cursor: pointer; font-size: 13px; color: #f5f5f5; ${idx === 0 ? 'background: #1a1a24;' : ''}">
                    <div style="font-weight: 600;">${lesson.title}</div>
                </div>`
            ).join('');

        attachLessonHandlers(lessons);
    }

    function attachCourseHandlers(filteredCourses) {
        dropdown.querySelectorAll('.course-mention-item').forEach((item, idx) => {
            item.addEventListener('mouseenter', () => {
                selectedIndex = idx;
                updateSelection();
            });
            item.addEventListener('click', async () => {
                const courseId = item.getAttribute('data-course-id');
                currentCourse = filteredCourses[idx];
                currentLessons = await fetchLessonsForCourse(courseId);
                showLessonDropdown(currentLessons);
            });
        });
    }

    function attachLessonHandlers(lessons) {
        dropdown.querySelectorAll('.lesson-mention-item').forEach((item, idx) => {
            item.addEventListener('mouseenter', () => {
                selectedIndex = idx;
                updateSelection();
            });
            item.addEventListener('click', () => {
                insertMention(lessons[idx]);
            });
        });
    }

    function updateSelection() {
        const selector = mode === 'course' ? '.course-mention-item' : '.lesson-mention-item';
        const items = dropdown.querySelectorAll(selector);
        items.forEach((item, idx) => {
            item.style.background = idx === selectedIndex ? '#1a1a24' : 'transparent';
        });
    }

    function insertMention(lesson) {
        const text = textarea.value;
        const beforeMention = text.substring(0, mentionStart);
        const afterCaret = text.substring(getCaretPosition());
        const mention = `@${lesson.title}`;
        
        textarea.value = beforeMention + mention + ' ' + afterCaret;
        setCaretPosition(beforeMention.length + mention.length + 1);
        
        dropdown.style.display = 'none';
        mentionStart = -1;
    }

    textarea.addEventListener('input', function() {
        const { start, word } = getCurrentWord();
        console.log('Input detected, word:', word, 'courses count:', courses.length);
        
        if (word === '@') {
            console.log('@ detected, showing courses');
            mentionStart = start;
            showCourseDropdown(courses);
        } else if (word.startsWith('@') && word.length > 1) {
            mentionStart = start;
            const query = word.substring(1).toLowerCase();
            const filtered = courses.filter(c => 
                c.title.toLowerCase().includes(query)
            ).slice(0, 8);
            console.log('Filtered courses:', filtered);
            showCourseDropdown(filtered);
        } else {
            dropdown.style.display = 'none';
            mentionStart = -1;
            mode = 'course';
        }
    });

    textarea.addEventListener('keydown', function(e) {
        if (dropdown.style.display === 'none') return;

        const selector = mode === 'course' ? '.course-mention-item' : '.lesson-mention-item';
        const items = dropdown.querySelectorAll(selector);
        if (items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = (selectedIndex + 1) % items.length;
            updateSelection();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = (selectedIndex - 1 + items.length) % items.length;
            updateSelection();
        } else if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (mode === 'course') {
                const courseId = items[selectedIndex].getAttribute('data-course-id');
                const course = courses.find(c => c.id == courseId);
                if (course) {
                    currentCourse = course;
                    fetchLessonsForCourse(courseId).then(lessons => {
                        currentLessons = lessons;
                        showLessonDropdown(lessons);
                    });
                }
            } else {
                const lessonData = JSON.parse(items[selectedIndex].getAttribute('data-lesson') || '{}');
                if (lessonData.title) {
                    insertMention(lessonData);
                }
            }
        } else if (e.key === 'Escape') {
            dropdown.style.display = 'none';
            mentionStart = -1;
            mode = 'course';
        }
    });

    document.addEventListener('click', function(e) {
        if (!textarea.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
            mentionStart = -1;
        }
    });
})();
