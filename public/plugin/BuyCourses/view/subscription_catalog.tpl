{% autoescape false %}
<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <div class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                    {{ 'BuyCourses'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>

                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-90 sm:text-3xl">
                        {{ page_title }}
                    </h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-50">
                        {{ 'SubscriptionCatalogIntro'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <a
                        href="{{ back_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                >
                    <em class="fa fa-arrow-left fa-fw"></em>
                    {{ 'Back'|get_lang }}
                </a>
            </div>
        </div>
    </section>

    {% if sessions_are_included %}
    <nav class="overflow-x-auto">
        <div class="inline-flex min-w-full rounded-2xl border border-gray-25 bg-white p-1 shadow-sm sm:min-w-0">
            {% if coursesExist %}
            <a
                    href="subscription_course_catalog.php"
                    class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ showing_courses ? 'bg-primary text-white shadow-sm' : 'text-gray-90 hover:bg-support-2 hover:text-primary' }}"
            >
                {{ 'Courses'|get_lang }}
            </a>
            {% endif %}

            {% if sessionExist %}
            <a
                    href="subscription_session_catalog.php"
                    class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ showing_sessions ? 'bg-primary text-white shadow-sm' : 'text-gray-90 hover:bg-support-2 hover:text-primary' }}"
            >
                {{ 'Sessions'|get_lang }}
            </a>
            {% endif %}
        </div>
    </nav>
    {% endif %}

    <div class="grid gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
        <aside class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
            <div class="space-y-2">
                <h2 class="text-xl font-semibold text-gray-90">
                    {{ 'SearchFilter'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>
                <p class="text-sm leading-6 text-gray-50">
                    {{ 'SubscriptionCatalogSearchHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                </p>
            </div>

            <form method="get" action="" class="mt-6 space-y-5">
                <div class="space-y-2">
                    <label for="subscription_name" class="block text-sm font-semibold text-gray-90">
                        {{ showing_courses ? 'CourseName'|get_lang : 'SessionName'|get_lang }}
                    </label>
                    <input
                            id="subscription_name"
                            name="name"
                            type="text"
                            value="{{ name_filter_value|default('') }}"
                            placeholder="{{ 'EnterNameToSearch'|get_plugin_lang('BuyCoursesPlugin') }}"
                            class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm placeholder:text-gray-50 focus:border-primary focus:ring-primary"
                    >
                </div>

                <div class="flex flex-col gap-3">
                    <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                    >
                        <em class="fa fa-filter fa-fw"></em>
                        {{ 'Search'|get_lang }}
                    </button>

                    <a
                            href="{{ pagination_base_path|default('subscription_course_catalog.php') }}"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                    >
                        <em class="fa fa-eraser fa-fw"></em>
                        {{ 'Reset'|get_lang }}
                    </a>
                </div>
            </form>
        </aside>

        <section class="space-y-6">
            <div class="flex items-center justify-between rounded-2xl border border-gray-25 bg-white px-5 py-4 shadow-sm">
                <div>
                    <h2 class="text-lg font-semibold text-gray-90">
                        {{ showing_courses ? 'Courses'|get_lang : 'Sessions'|get_lang }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-50">
                        {{ pagination_total_items }} result{{ pagination_total_items == 1 ? '' : 's' }}
                    </p>
                </div>

                <span class="inline-flex items-center rounded-full bg-primary px-3 py-1 text-xs font-semibold text-white">
                    {{ 'Subscriptions'|get_plugin_lang('BuyCoursesPlugin') }}
                </span>
            </div>

            {% if showing_courses %}
            {% if courses %}
            <div class="grid gap-6 sm:grid-cols-2 2xl:grid-cols-3">
                {% for course in courses %}
                {% set has_course_description = course.description is defined and course.description|striptags|trim is not empty %}
                {% set course_description_url = url('index') ~ 'plugin/BuyCourses/src/course_information.php?course_id=' ~ course.id %}

                <article class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                    <div class="aspect-[16/9] overflow-hidden bg-support-2">
                        <img
                                alt="{{ course.title }}"
                                class="h-full w-full object-cover"
                                src="{{ course.course_img ? course.course_img : 'session_default.png'|icon() }}"
                        >
                    </div>

                    <div class="space-y-4 p-5">
                        <div class="space-y-2">
                            <h3 class="text-lg font-semibold text-gray-90">
                                {% if has_course_description %}
                                <a
                                        class="ajax transition hover:text-primary"
                                        href="{{ course_description_url }}"
                                        data-title="{{ course.title }}"
                                >
                                    {{ course.title }}
                                </a>
                                {% else %}
                                <span>{{ course.title }}</span>
                                {% endif %}
                            </h3>

                            {% if course.code is defined and course.code %}
                            <div class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                                {{ course.code }}
                            </div>
                            {% endif %}
                        </div>

                        <div class="space-y-2 text-sm text-gray-50">
                            {% if course.teachers is defined and course.teachers %}
                            {% for teacher in course.teachers %}
                            <div class="flex items-center gap-2">
                                <em class="fa fa-user text-primary"></em>
                                <span>{{ teacher }}</span>
                            </div>
                            {% endfor %}
                            {% else %}
                            <div class="flex items-center gap-2">
                                <em class="fa fa-user text-primary"></em>
                                <span>{{ 'NoTeacherInformationAvailable'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                            </div>
                            {% endif %}
                        </div>

                        {% if course.item is defined and course.item.total_price_formatted is defined %}
                        <div class="flex items-center justify-between gap-4">
                                            <span class="inline-flex items-center rounded-full bg-primary px-3 py-1 text-sm font-semibold text-white">
                                                {{ course.item.total_price_formatted }}
                                            </span>
                        </div>
                        {% endif %}

                        {% if course.enrolled == 'YES' %}
                        <div class="rounded-2xl border border-success/20 bg-success/10 px-4 py-3 text-sm text-gray-90">
                            <em class="fa fa-check-square-o fa-fw"></em>
                            {{ 'TheUserIsAlreadyRegisteredInTheCourse'|get_plugin_lang('BuyCoursesPlugin') }}
                        </div>
                        {% elseif course.enrolled == 'TMP' %}
                        <div class="rounded-2xl border border-info/20 bg-support-2 px-4 py-3 text-sm text-gray-90">
                            {{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}
                        </div>
                        {% else %}
                        <div class="flex flex-col gap-3">
                            {% if has_course_description %}
                            <a
                                    class="ajax inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                                    href="{{ course_description_url }}"
                                    data-title="{{ course.title }}"
                            >
                                <em class="fa fa-file-text fa-fw"></em>
                                {{ 'SeeDescription'|get_plugin_lang('BuyCoursesPlugin') }}
                            </a>
                            {% endif %}

                            <a
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-success px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2"
                                    href="{{ url('index') ~ 'plugin/BuyCourses/src/subscription_process.php?' ~ {'i': course.id, 't': 1}|url_encode() }}"
                            >
                                <em class="fa fa-shopping-cart fa-fw"></em>
                                {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                            </a>
                        </div>
                        {% endif %}
                    </div>
                </article>
                {% endfor %}
            </div>
            {% else %}
            <div class="rounded-3xl border border-gray-25 bg-white p-10 text-center shadow-sm">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-support-2 text-primary">
                    <em class="fa fa-search text-xl"></em>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-90">
                    {{ 'NoSubscriptionsFound'|get_plugin_lang('BuyCoursesPlugin') }}
                </h3>
                <p class="mt-2 text-sm text-gray-50">
                    {{ 'TryChangingSearchFilter'|get_plugin_lang('BuyCoursesPlugin') }}
                </p>
            </div>
            {% endif %}
            {% endif %}

            {% if showing_sessions %}
            {% if sessions %}
            <div class="grid gap-6 sm:grid-cols-2 2xl:grid-cols-3">
                {% for session in sessions %}
                <article class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                    <div class="aspect-[16/9] overflow-hidden bg-support-2">
                        <img
                                alt="{{ session.title }}"
                                class="h-full w-full object-cover"
                                src="{{ session.image ? session.image : 'session_default.png'|icon() }}"
                        >
                    </div>

                    <div class="space-y-4 p-5">
                        <div class="space-y-2">
                            <h3 class="text-lg font-semibold text-gray-90">
                                <a class="transition hover:text-primary" href="{{ url('index') ~ 'session/' ~ session.id ~ '/about/' }}">
                                    {{ session.title }}
                                </a>
                            </h3>
                        </div>

                        <div class="space-y-2 text-sm text-gray-50">
                            {% if 'show_session_coach'|api_get_setting == 'true' %}
                            <div class="flex items-center gap-2">
                                <em class="fa fa-user text-primary"></em>
                                <span>{{ session.coach }}</span>
                            </div>
                            {% endif %}

                            <div class="flex items-center gap-2">
                                <em class="fa fa-calendar text-primary"></em>
                                <span>
                                                {% if session.duration %}
                                    {{ 'SessionDurationXDaysTotal'|get_lang|format(session.duration) }}
                                                {% else %}
                                    {{ session.dates.display }}
                                                {% endif %}
                                            </span>
                            </div>
                        </div>

                        {% if session.item is defined and session.item.total_price_formatted is defined %}
                        <div class="flex items-center justify-between gap-4">
                                            <span class="inline-flex items-center rounded-full bg-primary px-3 py-1 text-sm font-semibold text-white">
                                                {{ session.item.total_price_formatted }}
                                            </span>
                        </div>
                        {% endif %}

                        {% if session.enrolled == 'YES' %}
                        <div class="rounded-2xl border border-success/20 bg-success/10 px-4 py-3 text-sm text-gray-90">
                            <em class="fa fa-check-square-o fa-fw"></em>
                            {{ 'TheUserIsAlreadyRegisteredInTheSession'|get_plugin_lang('BuyCoursesPlugin') }}
                        </div>
                        {% elseif session.enrolled == 'TMP' %}
                        <div class="rounded-2xl border border-info/20 bg-support-2 px-4 py-3 text-sm text-gray-90">
                            {{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}
                        </div>
                        {% else %}
                        <a
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-success px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2"
                                href="{{ url('index') ~ 'plugin/BuyCourses/src/subscription_process.php?' ~ {'i': session.id, 't': 2}|url_encode }}"
                        >
                            <em class="fa fa-shopping-cart fa-fw"></em>
                            {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                        </a>
                        {% endif %}
                    </div>
                </article>
                {% endfor %}
            </div>
            {% else %}
            <div class="rounded-3xl border border-gray-25 bg-white p-10 text-center shadow-sm">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-support-2 text-primary">
                    <em class="fa fa-search text-xl"></em>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-90">
                    {{ 'NoSubscriptionsFound'|get_plugin_lang('BuyCoursesPlugin') }}
                </h3>
                <p class="mt-2 text-sm text-gray-50">
                    {{ 'TryChangingSearchFilter'|get_plugin_lang('BuyCoursesPlugin') }}
                </p>
            </div>
            {% endif %}
            {% endif %}

            {% if pagination_pages_count|default(1) > 1 %}
            {% set startPage = pagination_current_page - 2 %}
            {% set endPage = pagination_current_page + 2 %}

            {% if startPage < 1 %}
            {% set endPage = endPage + (1 - startPage) %}
            {% set startPage = 1 %}
            {% endif %}

            {% if endPage > pagination_pages_count %}
            {% set startPage = startPage - (endPage - pagination_pages_count) %}
            {% set endPage = pagination_pages_count %}
            {% endif %}

            {% if startPage < 1 %}
            {% set startPage = 1 %}
            {% endif %}

            <div class="rounded-3xl border border-gray-25 bg-white px-6 py-4 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-gray-50">
                        {{ 'Page'|get_lang }} {{ pagination_current_page }} / {{ pagination_pages_count }}
                    </p>

                    <nav class="flex flex-wrap items-center gap-2" aria-label="Pagination">
                        {% set previousPage = pagination_current_page - 1 %}
                        <a
                                href="{{ (pagination_base_path|default('subscription_course_catalog.php')) ~ '?' ~ {'page': previousPage, 'name': name_filter_value|default('')}|url_encode }}"
                                class="inline-flex min-h-10 items-center justify-center rounded-xl border border-gray-25 bg-white px-4 py-2 text-sm font-semibold text-gray-90 no-underline transition hover:border-primary/30 hover:text-primary {{ pagination_current_page <= 1 ? 'pointer-events-none opacity-50' : '' }}"
                        >
                            {{ 'Previous'|get_lang }}
                        </a>

                        {% if startPage > 1 %}
                        <a
                                href="{{ (pagination_base_path|default('subscription_course_catalog.php')) ~ '?' ~ {'page': 1, 'name': name_filter_value|default('')}|url_encode }}"
                                class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl border border-gray-25 bg-white px-3 py-2 text-sm font-semibold text-gray-90 no-underline transition hover:border-primary/30 hover:text-primary"
                        >
                            1
                        </a>

                        {% if startPage > 2 %}
                        <span class="inline-flex min-h-10 min-w-10 items-center justify-center px-1 text-sm font-semibold text-gray-50">
                                        …
                                    </span>
                        {% endif %}
                        {% endif %}

                        {% for pageNumber in startPage..endPage %}
                        <a
                                href="{{ (pagination_base_path|default('subscription_course_catalog.php')) ~ '?' ~ {'page': pageNumber, 'name': name_filter_value|default('')}|url_encode }}"
                                class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl border px-3 py-2 text-sm font-semibold no-underline transition {{ pageNumber == pagination_current_page ? 'border-primary bg-primary text-white' : 'border-gray-25 bg-white text-gray-90 hover:border-primary/30 hover:text-primary' }}"
                        >
                            {{ pageNumber }}
                        </a>
                        {% endfor %}

                        {% if endPage < pagination_pages_count %}
                        {% if endPage < pagination_pages_count - 1 %}
                        <span class="inline-flex min-h-10 min-w-10 items-center justify-center px-1 text-sm font-semibold text-gray-50">
                                        …
                                    </span>
                        {% endif %}

                        <a
                                href="{{ (pagination_base_path|default('subscription_course_catalog.php')) ~ '?' ~ {'page': pagination_pages_count, 'name': name_filter_value|default('')}|url_encode }}"
                                class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl border border-gray-25 bg-white px-3 py-2 text-sm font-semibold text-gray-90 no-underline transition hover:border-primary/30 hover:text-primary"
                        >
                            {{ pagination_pages_count }}
                        </a>
                        {% endif %}

                        {% set nextPage = pagination_current_page + 1 %}
                        <a
                                href="{{ (pagination_base_path|default('subscription_course_catalog.php')) ~ '?' ~ {'page': nextPage, 'name': name_filter_value|default('')}|url_encode }}"
                                class="inline-flex min-h-10 items-center justify-center rounded-xl border border-gray-25 bg-white px-4 py-2 text-sm font-semibold text-gray-90 no-underline transition hover:border-primary/30 hover:text-primary {{ pagination_current_page >= pagination_pages_count ? 'pointer-events-none opacity-50' : '' }}"
                        >
                            {{ 'Next'|get_lang }}
                        </a>
                    </nav>
                </div>
            </div>
            {% endif %}
        </section>
    </div>
</div>
{% endautoescape %}
