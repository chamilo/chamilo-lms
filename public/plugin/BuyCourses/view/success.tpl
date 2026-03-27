{% autoescape false %}
<div class="buycourses-success mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6">
    <style>
        .buycourses-success .buycourses-success-actions form,
        .buycourses-success .buycourses-success-actions .form-inline,
        .buycourses-success .buycourses-success-actions .row,
        .buycourses-success .buycourses-success-actions .button-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }

        .buycourses-success .buycourses-success-actions .form-group,
        .buycourses-success .buycourses-success-actions .col-sm-12,
        .buycourses-success .buycourses-success-actions .col-md-12 {
            margin: 0;
            padding: 0;
            width: auto;
            float: none;
        }

        .buycourses-success .buycourses-success-actions .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border-radius: 0.95rem;
            padding: 0.85rem 1.35rem;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.2s ease-in-out;
            box-shadow: none;
        }

        .buycourses-success .buycourses-success-actions .btn:hover {
            text-decoration: none;
            transform: translateY(-1px);
        }

        .buycourses-success .buycourses-success-actions .btn-success,
        .buycourses-success .buycourses-success-actions .btn-primary {
            background: #2f7db3;
            border-color: #2f7db3;
            color: #ffffff !important;
        }

        .buycourses-success .buycourses-success-actions .btn-default,
        .buycourses-success .buycourses-success-actions .btn-secondary,
        .buycourses-success .buycourses-success-actions .btn:not(.btn-success):not(.btn-primary) {
            background: #ffffff;
            border-color: #d9e1e8;
            color: #27364b !important;
        }

        .buycourses-success .buycourses-success-actions .btn em,
        .buycourses-success .buycourses-success-actions .btn i {
            margin-right: 0.1rem;
        }

        .buycourses-success .buycourses-success-actions label,
        .buycourses-success .buycourses-success-actions legend,
        .buycourses-success .buycourses-success-actions .help-block:empty {
            display: none;
        }
    </style>

    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <span class="inline-flex items-center rounded-full bg-support-2 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-primary">
                    PayPal
                </span>

                <div class="space-y-2">
                    <h1 class="text-3xl font-semibold text-gray-90">
                        {{ title }}
                    </h1>
                    <p class="max-w-3xl text-sm leading-6 text-gray-50">
                        {{ 'PayPalPaymentOKPleaseConfirm'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-primary/15 bg-support-2 px-5 py-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Total'|get_lang }}
                </div>
                <div class="mt-1 text-2xl font-semibold text-gray-90">
                    {% if buying_course %}
                    {{ course.total_price_formatted|default(currency ~ ' ' ~ price) }}
                    {% elseif buying_session %}
                    {{ session.total_price_formatted|default(currency ~ ' ' ~ price) }}
                    {% else %}
                    {{ currency }} {{ price }}
                    {% endif %}
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="grid gap-6 lg:grid-cols-2">
                <article class="rounded-3xl border border-gray-25 bg-white p-5 shadow-sm">
                    <div class="overflow-hidden rounded-3xl bg-support-2 p-4">
                        <div class="flex h-72 items-center justify-center">
                            {% if buying_course %}
                            <img
                                    alt="{{ course.title }}"
                                    class="max-h-full w-full object-contain"
                                    src="{{ course.course_img ? course.course_img : 'session_default.png'|icon() }}"
                            >
                            {% elseif buying_session %}
                            <img
                                    alt="{{ session.title }}"
                                    class="max-h-full w-full object-contain"
                                    src="{{ session.image ? session.image : 'session_default.png'|icon() }}"
                            >
                            {% endif %}
                        </div>
                    </div>

                    <div class="mt-5 flex items-center justify-between gap-3">
                        <span class="inline-flex items-center rounded-full bg-primary px-4 py-1.5 text-sm font-semibold text-white">
                            {% if buying_course %}
                            {{ course.total_price_formatted|default(currency ~ ' ' ~ price) }}
                            {% elseif buying_session %}
                            {{ session.total_price_formatted|default(currency ~ ' ' ~ price) }}
                            {% else %}
                            {{ currency }} {{ price }}
                            {% endif %}
                        </span>
                    </div>
                </article>

                <article class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
                    {% if buying_course %}
                    <div class="space-y-5">
                        <div>
                                <span class="inline-flex items-center rounded-full bg-support-2 px-3 py-1 text-xs font-semibold text-primary">
                                    {{ 'Course'|get_lang }}
                                </span>
                            <h2 class="mt-3 text-2xl font-semibold text-gray-90">
                                {{ course.title }}
                            </h2>
                        </div>

                        {% if course.teachers is defined and course.teachers %}
                        <div class="space-y-3">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'Teachers'|get_lang }}
                            </h3>
                            <ul class="space-y-2">
                                {% for teacher in course.teachers %}
                                <li class="flex items-center gap-2 text-sm text-gray-90">
                                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-support-2 text-primary">
                                                    <em class="fa fa-user" aria-hidden="true"></em>
                                                </span>
                                    <span>{{ teacher.name }}</span>
                                </li>
                                {% endfor %}
                            </ul>
                        </div>
                        {% endif %}

                        <div class="pt-2">
                            <a
                                    class="ajax inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary"
                                    data-title="{{ course.name }}"
                                    href="/main/inc/ajax/course_home.ajax.php?{{ {'a': 'show_course_information', 'code': course.code}|url_encode }}"
                            >
                                <em class="fa fa-file-text-o" aria-hidden="true"></em>
                                {{ 'Description'|get_lang }}
                            </a>
                        </div>
                    </div>
                    {% elseif buying_session %}
                    <div class="space-y-5">
                        <div>
                                <span class="inline-flex items-center rounded-full bg-support-2 px-3 py-1 text-xs font-semibold text-primary">
                                    {{ 'SessionName'|get_lang }}
                                </span>
                            <h2 class="mt-3 text-2xl font-semibold text-gray-90">
                                {{ session.title }}
                            </h2>
                            {% if session.dates is defined and session.dates.display %}
                            <p class="mt-2 text-sm text-gray-50">
                                {{ session.dates.display }}
                            </p>
                            {% endif %}
                        </div>

                        {% if session.courses is defined and session.courses %}
                        <div class="space-y-3">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'Courses'|get_lang }}
                            </h3>

                            <div class="space-y-3">
                                {% for itemCourse in session.courses %}
                                <div class="rounded-2xl border border-gray-25 bg-support-2 px-4 py-3">
                                    <p class="font-semibold text-gray-90">
                                        {{ itemCourse.title }}
                                    </p>

                                    {% if itemCourse.coaches is defined and itemCourse.coaches %}
                                    <ul class="mt-2 space-y-1">
                                        {% for coach in itemCourse.coaches %}
                                        <li class="flex items-center gap-2 text-sm text-gray-50">
                                            <em class="fa fa-user text-primary" aria-hidden="true"></em>
                                            <span>{{ coach.name }}</span>
                                        </li>
                                        {% endfor %}
                                    </ul>
                                    {% endif %}
                                </div>
                                {% endfor %}
                            </div>
                        </div>
                        {% endif %}
                    </div>
                    {% endif %}
                </article>
            </section>

            <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-gray-90">
                    {{ 'UserInformation'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>

                <dl class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl bg-support-2 px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'Name'|get_lang }}
                        </dt>
                        <dd class="mt-1 text-sm font-medium text-gray-90">
                            {{ user.complete_name }}
                        </dd>
                    </div>

                    <div class="rounded-2xl bg-support-2 px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'Username'|get_lang }}
                        </dt>
                        <dd class="mt-1 text-sm font-medium text-gray-90">
                            {{ user.username }}
                        </dd>
                    </div>

                    <div class="rounded-2xl bg-support-2 px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'EmailAddress'|get_lang }}
                        </dt>
                        <dd class="mt-1 break-all text-sm font-medium text-gray-90">
                            {{ user.email }}
                        </dd>
                    </div>
                </dl>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-gray-90">
                    {{ 'ConfirmOrder'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>
                <p class="mt-2 text-sm leading-6 text-gray-50">
                    {{ 'PayPalPaymentOKPleaseConfirm'|get_plugin_lang('BuyCoursesPlugin') }}
                </p>

                <div class="mt-5 rounded-2xl border border-primary/15 bg-support-2 px-4 py-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Total'|get_lang }}
                    </div>
                    <div class="mt-1 text-2xl font-semibold text-gray-90">
                        {% if buying_course %}
                        {{ course.total_price_formatted|default(currency ~ ' ' ~ price) }}
                        {% elseif buying_session %}
                        {{ session.total_price_formatted|default(currency ~ ' ' ~ price) }}
                        {% else %}
                        {{ currency }} {{ price }}
                        {% endif %}
                    </div>
                </div>

                <div class="buycourses-success-actions mt-6">
                    {{ form }}
                </div>
            </section>
        </aside>
    </div>
</div>
{% endautoescape %}
