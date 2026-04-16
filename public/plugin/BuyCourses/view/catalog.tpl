{% autoescape false %}
{% set catalogType = 'courses' %}
{% if showing_sessions %}
    {% set catalogType = 'sessions' %}
{% elseif showing_services %}
    {% set catalogType = 'services' %}
{% endif %}

{% set searchEntityLabel = 'Name'|get_lang %}
{% if showing_courses %}
    {% set searchEntityLabel = 'Course name'|get_lang %}
{% elseif showing_sessions %}
    {% set searchEntityLabel = 'SessionName'|get_lang %}
{% elseif showing_services %}
    {% set searchEntityLabel = 'Service'|get_plugin_lang('BuyCoursesPlugin') %}
{% endif %}

{% set visibleTabsCount = 0 %}
{% if show_courses_tab|default(false) %}
    {% set visibleTabsCount = visibleTabsCount + 1 %}
{% endif %}
{% if show_sessions_tab|default(false) %}
    {% set visibleTabsCount = visibleTabsCount + 1 %}
{% endif %}
{% if show_services_tab|default(false) %}
    {% set visibleTabsCount = visibleTabsCount + 1 %}
{% endif %}

<div class="mx-auto w-full max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8 2xl:px-10">
    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="space-y-2">
                <div class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                    {{ 'BuyCourses'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>

                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-90 sm:text-3xl">
                        {{ page_title|default('CourseListOnSale'|get_plugin_lang('BuyCoursesPlugin')) }}
                    </h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-50">
                        {% if showing_courses %}
                            {{ 'CatalogCoursesIntro'|get_plugin_lang('BuyCoursesPlugin') }}
                        {% elseif showing_sessions %}
                            {{ 'CatalogSessionsIntro'|get_plugin_lang('BuyCoursesPlugin') }}
                        {% else %}
                            {{ 'CatalogServicesIntro'|get_plugin_lang('BuyCoursesPlugin') }}
                        {% endif %}
                    </p>
                </div>
            </div>

            {% if back_url is defined and back_url %}
                <div class="flex flex-col gap-3 sm:flex-row">
                    <a
                        href="{{ back_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                    >
                        <em class="fa fa-arrow-left fa-fw"></em>
                        {{ 'Back'|get_lang }}
                    </a>
                </div>
            {% endif %}
        </div>
    </section>

    {% if visibleTabsCount > 1 %}
        <nav class="overflow-x-auto">
            <div class="inline-flex min-w-full rounded-2xl border border-gray-25 bg-white p-1 shadow-sm sm:min-w-0">
                {% if show_courses_tab|default(false) %}
                    <a
                        href="course_catalog.php"
                        class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ showing_courses ? 'bg-primary text-white shadow-sm' : 'text-gray-90 hover:bg-support-2 hover:text-primary' }}"
                    >
                        {{ 'Courses'|get_lang }}
                    </a>
                {% endif %}

                {% if show_sessions_tab|default(false) %}
                    <a
                        href="session_catalog.php"
                        class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ showing_sessions ? 'bg-primary text-white shadow-sm' : 'text-gray-90 hover:bg-support-2 hover:text-primary' }}"
                    >
                        {{ 'Sessions'|get_lang }}
                    </a>
                {% endif %}

                {% if show_services_tab|default(false) %}
                    <a
                        href="service_catalog.php"
                        class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ showing_services ? 'bg-primary text-white shadow-sm' : 'text-gray-90 hover:bg-support-2 hover:text-primary' }}"
                    >
                        {{ 'Services'|get_plugin_lang('BuyCoursesPlugin') }}
                    </a>
                {% endif %}
            </div>
        </nav>
    {% endif %}

    <div class="grid gap-6 xl:grid-cols-12">
        <aside class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm xl:col-span-3">
            <div class="space-y-2">
                <h2 class="text-xl font-semibold text-gray-90">
                    {{ 'SearchFilter'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>
                <p class="text-sm leading-6 text-gray-50">
                    {% if showing_courses %}
                        {{ 'CatalogCoursesFilterHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                    {% elseif showing_sessions %}
                        {{ 'CatalogSessionsFilterHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                    {% else %}
                        {{ 'CatalogServicesFilterHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                    {% endif %}
                </p>
            </div>

            <form method="get" action="" class="mt-6 space-y-5">
                <div class="space-y-2">
                    <label for="catalog_name" class="block text-sm font-semibold text-gray-90">
                        {{ searchEntityLabel }}
                    </label>
                    <input
                        id="catalog_name"
                        name="name"
                        type="text"
                        value="{{ name_filter_value|default('') }}"
                        placeholder="{{ 'EnterNameToSearch'|get_plugin_lang('BuyCoursesPlugin') }}"
                        class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm placeholder:text-gray-50 focus:border-primary focus:ring-primary"
                    >
                </div>

                {% if showing_sessions %}
                    <div class="space-y-2">
                        <label for="session_category" class="block text-sm font-semibold text-gray-90">
                            {{ 'SessionCategory'|get_lang }}
                        </label>
                        <select
                            id="session_category"
                            name="session_category"
                            class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm focus:border-primary focus:ring-primary"
                        >
                            <option value="0" {{ session_category_value|default(0) == 0 ? 'selected' : '' }}>
                                {{ 'AllCategories'|get_lang }}
                            </option>
                            {% if session_categories is defined and session_categories %}
                                {% for category in session_categories %}
                                    <option value="{{ category.id }}" {{ session_category_value|default(0) == category.id ? 'selected' : '' }}>
                                        {{ category.name }}
                                    </option>
                                {% endfor %}
                            {% endif %}
                        </select>
                    </div>
                {% endif %}

                <div class="space-y-2">
                    <label for="catalog_min_price" class="block text-sm font-semibold text-gray-90">
                        {{ 'MinimumPrice'|get_plugin_lang('BuyCoursesPlugin') }}
                    </label>
                    <input
                        id="catalog_min_price"
                        name="min"
                        type="number"
                        min="0"
                        step="0.01"
                        value="{{ min_filter_value|default('') }}"
                        placeholder="0.00"
                        class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm placeholder:text-gray-50 focus:border-primary focus:ring-primary"
                    >
                </div>

                <div class="space-y-2">
                    <label for="catalog_max_price" class="block text-sm font-semibold text-gray-90">
                        {{ 'MaximumPrice'|get_plugin_lang('BuyCoursesPlugin') }}
                    </label>
                    <input
                        id="catalog_max_price"
                        name="max"
                        type="number"
                        min="0"
                        step="0.01"
                        value="{{ max_filter_value|default('') }}"
                        placeholder="0.00"
                        class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm placeholder:text-gray-50 focus:border-primary focus:ring-primary"
                    >
                </div>

                <div class="flex flex-col gap-3 pt-2">
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                    >
                        <em class="fa fa-filter fa-fw"></em>
                        {{ 'Search'|get_lang }}
                    </button>

                    <a
                        href="{{ pagination_base_path|default('course_catalog.php') }}"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                    >
                        <em class="fa fa-eraser fa-fw"></em>
                        {{ 'Reset'|get_lang }}
                    </a>
                </div>
            </form>
        </aside>

        <section class="space-y-6 xl:col-span-9">
            {% if showing_courses %}
                {% if courses %}
                    <div class="grid gap-6 sm:grid-cols-2 2xl:grid-cols-3">
                        {% for course in courses %}
                            {% set course_description_url = url('index') ~ 'plugin/BuyCourses/src/course_information.php?' ~ {'course_id': course.id}|url_encode %}
                            <article class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm transition hover:shadow-md">
                                <div class="aspect-[16/9] overflow-hidden bg-support-2">
                                    <img
                                        alt="{{ course.title }}"
                                        class="h-full w-full object-cover"
                                        src="{{ course.course_img ? course.course_img : 'session_default.png'|icon() }}"
                                    >
                                </div>

                                <div class="space-y-4 p-5">
                                    <div class="space-y-2">
                                        <h3 class="line-clamp-2 text-lg font-semibold text-gray-90">
                                            <a
                                                class="ajax transition hover:text-primary"
                                                href="{{ course_description_url }}"
                                                data-title="{{ course.title }}"
                                            >
                                                {{ course.title }}
                                            </a>
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

                                    <div class="flex items-center justify-between gap-4">
                                        <span class="inline-flex items-center rounded-full bg-primary px-3 py-1 text-sm font-semibold text-white">
                                            {{ course.item.total_price_formatted }}
                                        </span>
                                    </div>

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
                                            <a
                                                class="ajax inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                                                href="{{ course_description_url }}"
                                                data-title="{{ course.title }}"
                                            >
                                                <em class="fa fa-file-text fa-fw"></em>
                                                {{ 'SeeDescription'|get_plugin_lang('BuyCoursesPlugin') }}
                                            </a>

                                            <a
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-success px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2"
                                                href="{{ url('index') ~ 'plugin/BuyCourses/src/process.php?' ~ {'i': course.id, 't': 1}|url_encode }}"
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
                            {{ 'NoCoursesFound'|get_plugin_lang('BuyCoursesPlugin') }}
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
                            <article class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm transition hover:shadow-md">
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
                                            {{ 'TheUserIsAlreadyRegisteredInTheCourse'|get_plugin_lang('BuyCoursesPlugin') }}
                                        </div>
                                    {% elseif session.enrolled == 'TMP' %}
                                        <div class="rounded-2xl border border-info/20 bg-support-2 px-4 py-3 text-sm text-gray-90">
                                            {{ 'WaitingToReceiveThePayment'|get_plugin_lang('BuyCoursesPlugin') }}
                                        </div>
                                    {% else %}
                                        <div class="flex flex-col gap-3">
                                            <a
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                                                href="{{ url('index') ~ 'session/' ~ session.id ~ '/about/' }}"
                                            >
                                                <em class="fa fa-file-text fa-fw"></em>
                                                {{ 'SeeDescription'|get_plugin_lang('BuyCoursesPlugin') }}
                                            </a>

                                            <a
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-success px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2"
                                                href="{{ url('index') ~ 'plugin/BuyCourses/src/process.php?' ~ {'i': session.id, 't': 2}|url_encode }}"
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
                            {{ 'NoSessionsFound'|get_plugin_lang('BuyCoursesPlugin') }}
                        </h3>
                        <p class="mt-2 text-sm text-gray-50">
                            {{ 'TryChangingSearchFilter'|get_plugin_lang('BuyCoursesPlugin') }}
                        </p>
                    </div>
                {% endif %}
            {% endif %}

            {% if showing_services %}
                {% if buyer_role_notice is defined and buyer_role_notice %}
                    <div class="rounded-2xl border border-warning bg-support-6 px-4 py-3 text-sm text-gray-90">
                        <em class="fa fa-info-circle fa-fw"></em>
                        {{ buyer_role_notice }}
                    </div>
                {% endif %}

                {% if services %}
                    <div class="grid gap-6 sm:grid-cols-2 2xl:grid-cols-3">
                        {% for service in services %}
                            <article class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm transition hover:shadow-md">
                                <div class="aspect-[16/9] overflow-hidden bg-support-2">
                                    <img
                                        alt="{{ service.name }}"
                                        class="h-full w-full object-cover"
                                        src="{{ service.image ? service.image : 'session_default.png'|icon() }}"
                                    >
                                </div>

                                <div class="space-y-4 p-5">
                                    <div class="space-y-2">
                                        <h3 class="text-lg font-semibold text-gray-90">
                                            {{ service.name }}
                                        </h3>
                                    </div>

                                    <div class="text-sm text-gray-50">
                                        {{ service.description }}
                                    </div>

                                    <div class="rounded-2xl border border-primary/10 bg-support-1 px-4 py-3">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-primary">
                                            {{ 'Price'|get_lang }}
                                        </div>
                                        <div class="mt-1 text-base font-semibold text-gray-90">
                                            {{ service.display_price|default(service.total_price|default('')) }}
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-3">
                                        <a
                                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                                            href="service_information.php?service_id={{ service.id }}"
                                        >
                                            <em class="fa fa-file-text fa-fw"></em>
                                            {{ 'SeeDescription'|get_plugin_lang('BuyCoursesPlugin') }}
                                        </a>

                                        {% if service.has_blocking_sale|default(false) %}
                                            <span
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gray-20 px-4 py-2.5 text-sm font-semibold text-gray-50"
                                            >
                                                <em class="fa fa-check-circle fa-fw"></em>
                                                {{ 'Already purchased'|get_lang }}
                                            </span>
                                        {% elseif can_buy_services|default(false) %}
                                            <a
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-success px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2"
                                                href="service_process.php?i={{ service.id }}&t={{ service.applies_to|default(0) }}"
                                            >
                                                <em class="fa fa-shopping-cart fa-fw"></em>
                                                {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                                            </a>
                                        {% else %}
                                            <span
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gray-20 px-4 py-2.5 text-sm font-semibold text-gray-50"
                                            >
                                                <em class="fa fa-lock fa-fw"></em>
                                                {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                                            </span>
                                        {% endif %}
                                    </div>
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
                            {{ 'NoServicesFound'|get_plugin_lang('BuyCoursesPlugin') }}
                        </h3>
                        <p class="mt-2 text-sm text-gray-50">
                            {{ 'TryChangingSearchFilter'|get_plugin_lang('BuyCoursesPlugin') }}
                        </p>
                    </div>
                {% endif %}
            {% endif %}

            {% if pagination_pages_count is defined and pagination_pages_count > 1 %}
                <nav class="flex items-center justify-center gap-2 pt-2">
                    {% set query = app.request.query.all %}
                    {% for page in 1..pagination_pages_count %}
                        {% set pageQuery = query|merge({'page': page}) %}
                        <a
                            href="{{ pagination_base_path|default('course_catalog.php') }}?{{ pageQuery|url_encode }}"
                            class="inline-flex h-10 min-w-[2.5rem] items-center justify-center rounded-xl border px-3 text-sm font-semibold transition {{ page == pagination_current_page ? 'border-primary bg-primary text-white shadow-sm' : 'border-gray-25 bg-white text-gray-90 hover:border-primary/30 hover:text-primary' }}"
                        >
                            {{ page }}
                        </a>
                    {% endfor %}
                </nav>
            {% endif %}
        </section>
    </div>
</div>
{% endautoescape %}
