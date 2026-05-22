<div class="space-y-6 learning-calendar-native">
    <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <div class="text-sm font-semibold uppercase tracking-wide text-primary">
                    {{ plugin_title }}
                </div>
                <h1 class="mt-1 text-2xl font-semibold text-gray-90">{{ header }}</h1>
                {% if description %}
                    <div class="mt-2 max-w-4xl text-sm text-gray-50">
                        {{ description|raw }}
                    </div>
                {% endif %}
            </div>

            <div class="grid min-w-64 grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-gray-15 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">{{ 'Total hours'|get_lang }}</div>
                    <div class="mt-1 text-xl font-bold text-gray-90">{{ total_hours }}</div>
                </div>
                <div class="rounded-xl bg-gray-15 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">{{ 'Minutes per day'|get_lang }}</div>
                    <div class="mt-1 text-xl font-bold text-gray-90">{{ minutes_per_day }}</div>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-90">{{ 'Calendar'|get_lang }}</h2>
                <p class="mt-1 text-sm text-gray-50">{{ calendar_help }}</p>
            </div>

            <div class="flex flex-wrap gap-3 text-sm">
                {% for event in events %}
                    <div class="flex items-center gap-2 rounded-full border border-gray-25 bg-gray-15 px-3 py-1.5">
                        <span class="learning-calendar-legend-color learning-calendar-legend-color--{{ event.color }}"></span>
                        <span class="font-medium text-gray-70">{{ event.title }}</span>
                    </div>
                {% endfor %}
            </div>
        </div>

        <div class="mb-4 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800">
            <div class="font-semibold">{{ calendar_cycle_help }}</div>
            <div class="mt-1">{{ calendar_range_help }}</div>
        </div>

        <div class="learning-calendar-year-toolbar">
            <button
                type="button"
                id="learning-calendar-previous-year"
                class="learning-calendar-year-button"
                title="{{ 'Previous'|get_lang }}"
                aria-label="{{ 'Previous'|get_lang }}"
            >
                <span class="mdi mdi-chevron-left" aria-hidden="true"></span>
            </button>

            <div id="learning-calendar-current-year" class="learning-calendar-year-label"></div>

            <button
                type="button"
                id="learning-calendar-next-year"
                class="learning-calendar-year-button"
                title="{{ 'Next'|get_lang }}"
                aria-label="{{ 'Next'|get_lang }}"
            >
                <span class="mdi mdi-chevron-right" aria-hidden="true"></span>
            </button>
        </div>

        <div
            id="learning-calendar-grid"
            class="learning-calendar-year-grid"
            data-ajax-url="{{ ajax_url }}"
        ></div>

        <div id="learning-calendar-message" class="learning-calendar-message learning-calendar-message--hidden"></div>
    </section>
</div>

<style>
    .learning-calendar-native,
    .learning-calendar-native * {
        box-sizing: border-box;
    }

    .learning-calendar-legend-color {
        display: inline-block;
        width: 24px;
        height: 10px;
        border-radius: 999px;
    }

    .learning-calendar-legend-color--red {
        background: #ef4444;
    }

    .learning-calendar-legend-color--yellow {
        background: #facc15;
    }

    .learning-calendar-legend-color--green {
        background: #16a34a;
    }

    .learning-calendar-year-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 16px;
        padding: 12px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #f9fafb;
    }

    .learning-calendar-year-label {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
    }

    .learning-calendar-year-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        background: #ffffff;
        color: #2563eb;
        cursor: pointer;
    }

    .learning-calendar-year-button:hover,
    .learning-calendar-year-button:focus {
        border-color: #2563eb;
        outline: none;
    }

    .learning-calendar-year-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
    }

    @media (min-width: 900px) {
        .learning-calendar-year-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1280px) {
        .learning-calendar-year-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    .learning-calendar-month {
        min-width: 0;
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .learning-calendar-month-title {
        margin: 0 0 12px;
        font-size: 16px;
        font-weight: 700;
        color: #111827;
    }

    .learning-calendar-weekdays,
    .learning-calendar-days {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 4px;
    }

    .learning-calendar-weekdays {
        margin-bottom: 6px;
        text-align: center;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #6b7280;
    }

    .learning-calendar-weekday {
        min-width: 0;
        padding: 4px 0;
    }

    .learning-calendar-empty-day {
        min-height: 34px;
    }

    .learning-calendar-day {
        width: 100%;
        min-width: 0;
        min-height: 34px;
        padding: 0;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #ffffff;
        color: #374151;
        font-size: 13px;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease, color 0.15s ease, transform 0.15s ease;
    }

    .learning-calendar-day:hover,
    .learning-calendar-day:focus {
        border-color: #2563eb;
        color: #2563eb;
        outline: none;
        transform: translateY(-1px);
    }

    .learning-calendar-day--busy {
        border-color: #fecaca;
        background: #fee2e2;
        color: #991b1b;
    }

    .learning-calendar-day--exam {
        border-color: #fde68a;
        background: #fef3c7;
        color: #92400e;
    }

    .learning-calendar-day--free {
        border-color: #bbf7d0;
        background: #dcfce7;
        color: #166534;
    }

    .learning-calendar-day--saving {
        opacity: 0.55;
        pointer-events: none;
    }

    .learning-calendar-message {
        margin-top: 16px;
        padding: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f9fafb;
        color: #374151;
        font-size: 14px;
    }

    .learning-calendar-message--error {
        border-color: #fecaca;
        background: #fef2f2;
        color: #991b1b;
    }

    .learning-calendar-message--hidden {
        display: none;
    }
</style>

<script>
(function () {
    var grid = document.getElementById('learning-calendar-grid');
    var message = document.getElementById('learning-calendar-message');
    var yearLabel = document.getElementById('learning-calendar-current-year');
    var previousButton = document.getElementById('learning-calendar-previous-year');
    var nextButton = document.getElementById('learning-calendar-next-year');

    if (!grid || !yearLabel || !previousButton || !nextButton) {
        return;
    }

    var ajaxUrl = grid.getAttribute('data-ajax-url');
    var currentYear = new Date().getFullYear();
    var eventsByDate = {};
    var lastSelectedDate = null;
    var monthNames = [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December'
    ];
    var weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    function showMessage(text, isError) {
        if (!message) {
            return;
        }

        message.textContent = text;
        message.classList.remove('learning-calendar-message--hidden');

        if (isError) {
            message.classList.add('learning-calendar-message--error');
            return;
        }

        message.classList.remove('learning-calendar-message--error');
    }

    function hideMessage() {
        if (!message) {
            return;
        }

        message.classList.add('learning-calendar-message--hidden');
        message.classList.remove('learning-calendar-message--error');
        message.textContent = '';
    }

    function parseIsoDate(value) {
        var parts = String(value).split('-');

        return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
    }

    function formatIsoDate(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');

        return year + '-' + month + '-' + day;
    }

    function getDaysInMonth(year, monthIndex) {
        return new Date(year, monthIndex + 1, 0).getDate();
    }

    function getMondayBasedStartOffset(year, monthIndex) {
        var day = new Date(year, monthIndex, 1).getDay();

        return (day + 6) % 7;
    }

    function getDayClass(event) {
        if (!event) {
            return '';
        }

        if (1 === parseInt(event.type, 10) || 'red' === event.color) {
            return ' learning-calendar-day--busy';
        }

        if (2 === parseInt(event.type, 10) || 'yellow' === event.color) {
            return ' learning-calendar-day--exam';
        }

        if (3 === parseInt(event.type, 10) || 'green' === event.color) {
            return ' learning-calendar-day--free';
        }

        return '';
    }

    function getEventLabel(event) {
        if (!event) {
            return '';
        }

        if (event.title) {
            return event.title;
        }

        if (1 === parseInt(event.type, 10) || 'red' === event.color) {
            return 'Busy';
        }

        if (2 === parseInt(event.type, 10) || 'yellow' === event.color) {
            return 'Exam';
        }

        if (3 === parseInt(event.type, 10) || 'green' === event.color) {
            return 'Free';
        }

        return '';
    }

    function buildEventMap(events) {
        eventsByDate = {};

        events.forEach(function (event) {
            if (!event.start_date) {
                return;
            }

            var startDate = parseIsoDate(event.start_date);
            var endDate = event.end_date ? parseIsoDate(event.end_date) : parseIsoDate(event.start_date);
            var cursor = new Date(startDate.getTime());

            while (cursor <= endDate) {
                eventsByDate[formatIsoDate(cursor)] = event;
                cursor.setDate(cursor.getDate() + 1);
            }
        });
    }

    function renderCalendar() {
        yearLabel.textContent = String(currentYear);
        grid.innerHTML = '';

        for (var monthIndex = 0; monthIndex < 12; monthIndex++) {
            var monthCard = document.createElement('section');
            monthCard.className = 'learning-calendar-month';

            var monthTitle = document.createElement('h3');
            monthTitle.className = 'learning-calendar-month-title';
            monthTitle.textContent = monthNames[monthIndex];
            monthCard.appendChild(monthTitle);

            var weekHeader = document.createElement('div');
            weekHeader.className = 'learning-calendar-weekdays';

            weekDays.forEach(function (label) {
                var dayLabel = document.createElement('div');
                dayLabel.className = 'learning-calendar-weekday';
                dayLabel.textContent = label;
                weekHeader.appendChild(dayLabel);
            });

            monthCard.appendChild(weekHeader);

            var daysGrid = document.createElement('div');
            daysGrid.className = 'learning-calendar-days';

            var offset = getMondayBasedStartOffset(currentYear, monthIndex);
            for (var emptyIndex = 0; emptyIndex < offset; emptyIndex++) {
                var empty = document.createElement('div');
                empty.className = 'learning-calendar-empty-day';
                daysGrid.appendChild(empty);
            }

            var daysInMonth = getDaysInMonth(currentYear, monthIndex);
            for (var day = 1; day <= daysInMonth; day++) {
                var date = new Date(currentYear, monthIndex, day);
                var isoDate = formatIsoDate(date);
                var event = eventsByDate[isoDate];
                var eventLabel = getEventLabel(event);

                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'learning-calendar-day' + getDayClass(event);
                button.dataset.date = isoDate;
                button.textContent = String(day);

                if (eventLabel) {
                    button.title = isoDate + ' - ' + eventLabel;
                    button.setAttribute('aria-label', isoDate + ' - ' + eventLabel);
                } else {
                    button.title = isoDate;
                    button.setAttribute('aria-label', isoDate);
                }

                button.addEventListener('click', onDayClick);
                daysGrid.appendChild(button);
            }

            monthCard.appendChild(daysGrid);
            grid.appendChild(monthCard);
        }
    }

    function getRangeStartEnd(dateA, dateB) {
        if (dateA <= dateB) {
            return {
                start: dateA,
                end: dateB
            };
        }

        return {
            start: dateB,
            end: dateA
        };
    }

    function setSavingState(startDate, endDate, saving) {
        var range = getRangeStartEnd(startDate, endDate);
        var buttons = grid.querySelectorAll('.learning-calendar-day');

        buttons.forEach(function (button) {
            var date = button.dataset.date;

            if (date >= range.start && date <= range.end) {
                button.classList.toggle('learning-calendar-day--saving', saving);
            }
        });
    }

    function toggleRange(startDate, endDate) {
        hideMessage();
        setSavingState(startDate, endDate, true);

        return fetch(
            ajaxUrl + '&a=toggle_day&start_date=' + encodeURIComponent(startDate) + '&end_date=' + encodeURIComponent(endDate),
            {
                credentials: 'same-origin',
                method: 'GET'
            }
        ).then(function (response) {
            if (!response.ok) {
                throw new Error('Unable to update the selected days.');
            }

            return loadEvents();
        }).catch(function (error) {
            showMessage(error.message, true);
        }).finally(function () {
            setSavingState(startDate, endDate, false);
        });
    }

    function onDayClick(event) {
        var selectedDate = event.currentTarget.dataset.date;
        var startDate = selectedDate;
        var endDate = selectedDate;

        if (event.shiftKey && lastSelectedDate) {
            var range = getRangeStartEnd(lastSelectedDate, selectedDate);
            startDate = range.start;
            endDate = range.end;
        }

        lastSelectedDate = selectedDate;
        toggleRange(startDate, endDate);
    }

    function loadEvents() {
        return fetch(ajaxUrl + '&a=get_events', {
            credentials: 'same-origin',
            method: 'GET'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Unable to load calendar events.');
            }

            return response.json();
        }).then(function (events) {
            buildEventMap(Array.isArray(events) ? events : []);
            renderCalendar();
        }).catch(function (error) {
            showMessage(error.message, true);
            renderCalendar();
        });
    }

    previousButton.addEventListener('click', function () {
        currentYear--;
        loadEvents();
    });

    nextButton.addEventListener('click', function () {
        currentYear++;
        loadEvents();
    });

    renderCalendar();
    loadEvents();
})();
</script>
