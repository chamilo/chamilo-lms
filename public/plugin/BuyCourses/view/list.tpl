{% autoescape false %}
{% set activeTab = 'courses' %}
{% if showing_services|default(false) %}
{% set activeTab = 'services' %}
{% elseif showing_sessions|default(false) %}
{% set activeTab = 'sessions' %}
{% endif %}

<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-2">
                <div class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                    {{ plugin_title|default('plugin_title'|get_plugin_lang('BuyCoursesPlugin')) }}
                </div>

                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-90 sm:text-3xl">
                        {{ page_title|default('AvailableCourses'|get_lang) }}
                    </h1>
                    <p class="mt-2 text-sm leading-6 text-gray-50">
                        {{ 'ConfigurationOfCoursesAndPrices'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>
            </div>

            {% if activeTab == 'services' %}
            <div class="flex items-center">
                <a
                        href="{{ url('index') ~ 'plugin/BuyCourses/src/services_add.php' }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                >
                    <em class="fa fa-cart-plus fa-fw"></em>
                    {{ 'NewService'|get_plugin_lang('BuyCoursesPlugin') }}
                </a>
            </div>
            {% endif %}
        </div>
    </section>

    {% if sessions_are_included or services_are_included %}
    <nav class="overflow-x-auto">
        <div class="inline-flex min-w-full rounded-2xl border border-gray-25 bg-white p-1 shadow-sm sm:min-w-0">
            <a
                    href="{{ url('index') ~ 'plugin/BuyCourses/src/list.php' }}"
                    class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold transition
                    {{ activeTab == 'courses'
                    ? 'bg-primary text-white shadow-sm'
                    : 'text-gray-90 hover:bg-support-2 hover:text-primary'
                    }}"
            >
                {{ 'Courses'|get_lang }}
            </a>

            {% if sessions_are_included %}
            <a
                    href="{{ url('index') ~ 'plugin/BuyCourses/src/list_session.php' }}"
                    class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold transition
                        {{ activeTab == 'sessions'
                    ? 'bg-primary text-white shadow-sm'
                    : 'text-gray-90 hover:bg-support-2 hover:text-primary'
                    }}"
            >
                {{ 'Sessions'|get_lang }}
            </a>
            {% endif %}

            {% if services_are_included %}
            <a
                    href="{{ url('index') ~ 'plugin/BuyCourses/src/list_service.php' }}"
                    class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold transition
                        {{ activeTab == 'services'
                    ? 'bg-primary text-white shadow-sm'
                    : 'text-gray-90 hover:bg-support-2 hover:text-primary'
                    }}"
            >
                {{ 'Services'|get_plugin_lang('BuyCoursesPlugin') }}
            </a>
            {% endif %}
        </div>
    </nav>
    {% endif %}

    {% if activeTab == 'courses' %}
    <section class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 px-6 py-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-90">
                        {{ 'AvailableCourses'|get_lang }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-50">
                        {{ 'ConfigurationOfCoursesAndPrices'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table id="courses_table" class="min-w-full divide-y divide-gray-25">
                <thead class="bg-gray-15">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Title'|get_lang }}
                    </th>
                    <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'OfficialCode'|get_lang }}
                    </th>
                    <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'VisibleInCatalog'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    {% if tax_enable and (tax_applies_to == 1 or tax_applies_to == 2) %}
                    <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ tax_name }}
                    </th>
                    {% endif %}
                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Options'|get_lang }}
                    </th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-25 bg-white">
                {% for item in courses %}
                <tr data-item="{{ item.id }}" data-type="course" class="align-middle transition hover:bg-support-2">
                    <td class="px-6 py-4">
                        <div class="flex min-w-[20rem] items-center gap-3">
                            <div class="shrink-0">
                                {% if item.visibility == 0 %}
                                <img
                                        src="{{ 'bullet_red.png'|icon() }}"
                                        alt="{{ 'CourseVisibilityClosed'|get_lang }}"
                                        title="{{ 'CourseVisibilityClosed'|get_lang }}"
                                        class="h-5 w-5 rounded-full"
                                >
                                {% elseif item.visibility == 1 %}
                                <img
                                        src="{{ 'bullet_orange.png'|icon() }}"
                                        alt="{{ 'Private'|get_lang }}"
                                        title="{{ 'Private'|get_lang }}"
                                        class="h-5 w-5 rounded-full"
                                >
                                {% elseif item.visibility == 2 %}
                                <img
                                        src="{{ 'bullet_green.png'|icon() }}"
                                        alt="{{ 'OpenToThePlatform'|get_lang }}"
                                        title="{{ 'OpenToThePlatform'|get_lang }}"
                                        class="h-5 w-5 rounded-full"
                                >
                                {% elseif item.visibility == 3 %}
                                <img
                                        src="{{ 'bullet_blue.png'|icon() }}"
                                        alt="{{ 'OpenToTheWorld'|get_lang }}"
                                        title="{{ 'OpenToTheWorld'|get_lang }}"
                                        class="h-5 w-5 rounded-full"
                                >
                                {% elseif item.visibility == 4 %}
                                <img
                                        src="{{ 'bullet_grey.png'|icon() }}"
                                        alt="{{ 'CourseVisibilityHidden'|get_lang }}"
                                        title="{{ 'CourseVisibilityHidden'|get_lang }}"
                                        class="h-5 w-5 rounded-full"
                                >
                                {% endif %}
                            </div>

                            <div class="min-w-0">
                                <a
                                        href="{{ url('chamilo_core_course_home', { cid: item.id }) }}"
                                        class="block truncate text-sm font-semibold text-gray-90 transition hover:text-primary"
                                >
                                    {{ item.title }}
                                </a>

                                <div class="mt-2">
                                                <span class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                                                    {{ item.code }}
                                                </span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <td class="px-6 py-4 text-center text-sm font-medium text-gray-90">
                        <span class="whitespace-nowrap">{{ item.code }}</span>
                    </td>

                    <td class="px-6 py-4 text-center">
                        {% if item.buyCourseData %}
                        <span class="inline-flex items-center gap-2 rounded-full bg-success/10 px-3 py-1 text-xs font-semibold text-success">
                                            <em class="fa fa-check fa-fw"></em>
                                            {{ 'Yes'|get_lang }}
                                        </span>
                        {% else %}
                        <span class="inline-flex items-center gap-2 rounded-full bg-gray-20 px-3 py-1 text-xs font-semibold text-gray-50">
                                            <em class="fa fa-times fa-fw"></em>
                                            {{ 'No'|get_lang }}
                                        </span>
                        {% endif %}
                    </td>

                    <td class="px-6 py-4 text-right text-sm font-semibold text-gray-90">
                        {% if item.buyCourseData and item.buyCourseData.price_formatted %}
                        <span class="whitespace-nowrap">{{ item.buyCourseData.price_formatted }}</span>
                        {% else %}
                        <span class="text-gray-50">—</span>
                        {% endif %}
                    </td>

                    {% if tax_enable and (tax_applies_to == 1 or tax_applies_to == 2) %}
                    <td class="px-6 py-4 text-center text-sm text-gray-90">
                        {% if item.buyCourseData and item.buyCourseData.tax_perc_show is defined %}
                        {{ item.buyCourseData.tax_perc_show }} %
                        {% else %}
                        <span class="text-gray-50">—</span>
                        {% endif %}
                    </td>
                    {% endif %}

                    <td class="px-6 py-4 text-right">
                        <a
                                href="{{ url('index') ~ 'plugin/BuyCourses/src/configure_course.php?' ~ {'id': item.id, 'type': product_type_course}|url_encode }}"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                        >
                            <em class="fa fa-wrench fa-fw"></em>
                            {{ 'Configure'|get_lang }}
                        </a>
                    </td>
                </tr>
                {% else %}
                <tr>
                    <td colspan="{{ tax_enable and (tax_applies_to == 1 or tax_applies_to == 2) ? 6 : 5 }}" class="px-6 py-10 text-center text-sm text-gray-50">
                        {{ 'NoResults'|get_lang }}
                    </td>
                </tr>
                {% endfor %}
                </tbody>
            </table>
        </div>

        {% if course_pages_count > 1 %}
        {% set paginationBaseUrl = url('index') ~ 'plugin/BuyCourses/src/list.php?' %}
        {% set startPage = course_current_page - 2 %}
        {% set endPage = course_current_page + 2 %}

        {% if startPage < 1 %}
        {% set endPage = endPage + (1 - startPage) %}
        {% set startPage = 1 %}
        {% endif %}

        {% if endPage > course_pages_count %}
        {% set startPage = startPage - (endPage - course_pages_count) %}
        {% set endPage = course_pages_count %}
        {% endif %}

        {% if startPage < 1 %}
        {% set startPage = 1 %}
        {% endif %}

        <div class="border-t border-gray-25 px-6 py-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-50">
                    {{ 'Page'|get_lang }} {{ course_current_page }} / {{ course_pages_count }}
                </p>

                <nav class="flex flex-wrap items-center gap-2" aria-label="Pagination">
                    {% set previousPage = course_current_page - 1 %}
                    <a
                            href="{{ paginationBaseUrl ~ {'page': previousPage, 'type': product_type_course}|url_encode }}"
                            class="inline-flex min-h-10 items-center justify-center rounded-xl border border-gray-25 bg-white px-4 py-2 text-sm font-semibold text-gray-90 no-underline transition hover:border-primary/30 hover:text-primary {{ course_current_page <= 1 ? 'pointer-events-none opacity-50' : '' }}"
                    >
                        {{ 'Previous'|get_lang }}
                    </a>

                    {% if startPage > 1 %}
                    <a
                            href="{{ paginationBaseUrl ~ {'page': 1, 'type': product_type_course}|url_encode }}"
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
                            href="{{ paginationBaseUrl ~ {'page': pageNumber, 'type': product_type_course}|url_encode }}"
                            class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl border px-3 py-2 text-sm font-semibold no-underline transition
                        {{ pageNumber == course_current_page
                            ? 'border-primary bg-primary text-white'
                            : 'border-gray-25 bg-white text-gray-90 hover:border-primary/30 hover:text-primary'
                            }}"
                    >
                        {{ pageNumber }}
                    </a>
                    {% endfor %}

                    {% if endPage < course_pages_count %}
                    {% if endPage < course_pages_count - 1 %}
                    <span class="inline-flex min-h-10 min-w-10 items-center justify-center px-1 text-sm font-semibold text-gray-50">
                            …
                        </span>
                    {% endif %}

                    <a
                            href="{{ paginationBaseUrl ~ {'page': course_pages_count, 'type': product_type_course}|url_encode }}"
                            class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl border border-gray-25 bg-white px-3 py-2 text-sm font-semibold text-gray-90 no-underline transition hover:border-primary/30 hover:text-primary"
                    >
                        {{ course_pages_count }}
                    </a>
                    {% endif %}

                    {% set nextPage = course_current_page + 1 %}
                    <a
                            href="{{ paginationBaseUrl ~ {'page': nextPage, 'type': product_type_course}|url_encode }}"
                            class="inline-flex min-h-10 items-center justify-center rounded-xl border border-gray-25 bg-white px-4 py-2 text-sm font-semibold text-gray-90 no-underline transition hover:border-primary/30 hover:text-primary {{ course_current_page >= course_pages_count ? 'pointer-events-none opacity-50' : '' }}"
                    >
                        {{ 'Next'|get_lang }}
                    </a>
                </nav>
            </div>
        </div>
        {% endif %}
    </section>
    {% endif %}

    {% if sessions_are_included and activeTab == 'sessions' %}
    <section class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-90">
                {{ 'Sessions'|get_lang }}
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table id="session_table" class="min-w-full divide-y divide-gray-25">
                <thead class="bg-gray-15">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Title'|get_lang }}
                    </th>
                    <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'StartDate'|get_lang }}
                    </th>
                    <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'EndDate'|get_lang }}
                    </th>
                    <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'VisibleInCatalog'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    {% if tax_enable and (tax_applies_to == 1 or tax_applies_to == 3) %}
                    <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ tax_name }}
                    </th>
                    {% endif %}
                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Options'|get_lang }}
                    </th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-25 bg-white">
                {% for item in sessions %}
                <tr data-item="{{ item.id }}" data-type="session" class="align-middle transition hover:bg-support-2">
                    <td class="px-6 py-4">
                        <a
                                href="{{ url('index') ~ 'main/session/index.php?' ~ {'session_id': item.id}|url_encode() }}"
                                class="block min-w-[18rem] text-sm font-semibold text-gray-90 transition hover:text-primary"
                        >
                            {{ item.title }}
                        </a>
                    </td>

                    <td class="px-6 py-4 text-center text-sm text-gray-90">
                        {{ item.displayStartDate|api_convert_and_format_date(6) }}
                    </td>

                    <td class="px-6 py-4 text-center text-sm text-gray-90">
                        {{ item.displayEndDate|api_convert_and_format_date(6) }}
                    </td>

                    <td class="px-6 py-4 text-center">
                        {% if item.buyCourseData %}
                        <span class="inline-flex items-center gap-2 rounded-full bg-success/10 px-3 py-1 text-xs font-semibold text-success">
                                            <em class="fa fa-check fa-fw"></em>
                                            {{ 'Yes'|get_lang }}
                                        </span>
                        {% else %}
                        <span class="inline-flex items-center gap-2 rounded-full bg-gray-20 px-3 py-1 text-xs font-semibold text-gray-50">
                                            <em class="fa fa-times fa-fw"></em>
                                            {{ 'No'|get_lang }}
                                        </span>
                        {% endif %}
                    </td>

                    <td class="px-6 py-4 text-right text-sm font-semibold text-gray-90">
                        {% if item.buyCourseData and item.buyCourseData.price_formatted is defined %}
                        <span class="whitespace-nowrap">{{ item.buyCourseData.price_formatted }}</span>
                        {% else %}
                        <span class="text-gray-50">—</span>
                        {% endif %}
                    </td>

                    {% if tax_enable and (tax_applies_to == 1 or tax_applies_to == 3) %}
                    <td class="px-6 py-4 text-center text-sm text-gray-90">
                        {% if item.buyCourseData and item.buyCourseData.tax_perc_show is defined %}
                        {{ item.buyCourseData.tax_perc_show }} %
                        {% else %}
                        <span class="text-gray-50">—</span>
                        {% endif %}
                    </td>
                    {% endif %}

                    <td class="px-6 py-4 text-right">
                        <a
                                href="{{ url('index') ~ 'plugin/BuyCourses/src/configure_course.php?' ~ {'id': item.id, 'type': product_type_session}|url_encode }}"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                        >
                            <em class="fa fa-wrench fa-fw"></em>
                            {{ 'Configure'|get_lang }}
                        </a>
                    </td>
                </tr>
                {% else %}
                <tr>
                    <td colspan="{{ tax_enable and (tax_applies_to == 1 or tax_applies_to == 3) ? 7 : 6 }}" class="px-6 py-10 text-center text-sm text-gray-50">
                        {{ 'NoResults'|get_lang }}
                    </td>
                </tr>
                {% endfor %}
                </tbody>
            </table>
        </div>

        {% if session_pages_count > 1 %}
        {% set paginationBaseUrl = url('index') ~ 'plugin/BuyCourses/src/list_session.php?' %}
        {% set startPage = session_current_page - 2 %}
        {% set endPage = session_current_page + 2 %}

        {% if startPage < 1 %}
        {% set endPage = endPage + (1 - startPage) %}
        {% set startPage = 1 %}
        {% endif %}

        {% if endPage > session_pages_count %}
        {% set startPage = startPage - (endPage - session_pages_count) %}
        {% set endPage = session_pages_count %}
        {% endif %}

        {% if startPage < 1 %}
        {% set startPage = 1 %}
        {% endif %}

        <div class="border-t border-gray-25 px-6 py-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-50">
                    {{ 'Page'|get_lang }} {{ session_current_page }} / {{ session_pages_count }}
                </p>

                <nav class="flex flex-wrap items-center gap-2" aria-label="Pagination">
                    {% set previousPage = session_current_page - 1 %}
                    <a
                            href="{{ paginationBaseUrl ~ {'page': previousPage, 'type': product_type_session}|url_encode }}"
                            class="inline-flex min-h-10 items-center justify-center rounded-xl border border-gray-25 bg-white px-4 py-2 text-sm font-semibold text-gray-90 no-underline transition hover:border-primary/30 hover:text-primary {{ session_current_page <= 1 ? 'pointer-events-none opacity-50' : '' }}"
                    >
                        {{ 'Previous'|get_lang }}
                    </a>

                    {% if startPage > 1 %}
                    <a
                            href="{{ paginationBaseUrl ~ {'page': 1, 'type': product_type_session}|url_encode }}"
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
                            href="{{ paginationBaseUrl ~ {'page': pageNumber, 'type': product_type_session}|url_encode }}"
                            class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl border px-3 py-2 text-sm font-semibold no-underline transition
                {{ pageNumber == session_current_page
                            ? 'border-primary bg-primary text-white'
                            : 'border-gray-25 bg-white text-gray-90 hover:border-primary/30 hover:text-primary'
                            }}"
                    >
                        {{ pageNumber }}
                    </a>
                    {% endfor %}

                    {% if endPage < session_pages_count %}
                    {% if endPage < session_pages_count - 1 %}
                    <span class="inline-flex min-h-10 min-w-10 items-center justify-center px-1 text-sm font-semibold text-gray-50">
                …
            </span>
                    {% endif %}

                    <a
                            href="{{ paginationBaseUrl ~ {'page': session_pages_count, 'type': product_type_session}|url_encode }}"
                            class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl border border-gray-25 bg-white px-3 py-2 text-sm font-semibold text-gray-90 no-underline transition hover:border-primary/30 hover:text-primary"
                    >
                        {{ session_pages_count }}
                    </a>
                    {% endif %}

                    {% set nextPage = session_current_page + 1 %}
                    <a
                            href="{{ paginationBaseUrl ~ {'page': nextPage, 'type': product_type_session}|url_encode }}"
                            class="inline-flex min-h-10 items-center justify-center rounded-xl border border-gray-25 bg-white px-4 py-2 text-sm font-semibold text-gray-90 no-underline transition hover:border-primary/30 hover:text-primary {{ session_current_page >= session_pages_count ? 'pointer-events-none opacity-50' : '' }}"
                    >
                        {{ 'Next'|get_lang }}
                    </a>
                </nav>
            </div>
        </div>
        {% endif %}
    </section>
    {% endif %}

    {% if services_are_included and activeTab == 'services' %}
    <section class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 px-6 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-90">
                        {{ 'Services'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-50">
                        {{ 'Service'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>            </div>
        </div>

        <div class="overflow-x-auto">
            <table id="services_table" class="min-w-full divide-y divide-gray-25">
                <thead class="bg-gray-15">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Service'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Description'|get_lang }}
                    </th>
                    <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Duration'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'VisibleInCatalog'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Owner'|get_lang }}
                    </th>
                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    {% if tax_enable and (tax_applies_to == 1 or tax_applies_to == 4) %}
                    <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ tax_name }}
                    </th>
                    {% endif %}
                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Options'|get_lang }}
                    </th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-25 bg-white">
                {% for item in services %}
                <tr data-item="{{ item.id }}" data-type="service" class="align-middle transition hover:bg-support-2">
                    <td class="px-6 py-4 text-sm font-semibold text-gray-90">
                        {{ item.name }}
                    </td>

                    <td class="px-6 py-4 text-sm text-gray-90">
                        {{ item.description }}
                    </td>

                    <td class="px-6 py-4 text-center text-sm text-gray-90">
                        {% if item.duration_days == 0 %}
                        {{ 'NoLimit'|get_lang }}
                        {% else %}
                        {{ item.duration_days }} {{ 'Days'|get_lang }}
                        {% endif %}
                    </td>

                    <td class="px-6 py-4 text-center">
                        {% if item.visibility == 1 %}
                        <span class="inline-flex items-center gap-2 rounded-full bg-success/10 px-3 py-1 text-xs font-semibold text-success">
                                            <em class="fa fa-check fa-fw"></em>
                                            {{ 'Yes'|get_lang }}
                                        </span>
                        {% else %}
                        <span class="inline-flex items-center gap-2 rounded-full bg-gray-20 px-3 py-1 text-xs font-semibold text-gray-50">
                                            <em class="fa fa-times fa-fw"></em>
                                            {{ 'No'|get_lang }}
                                        </span>
                        {% endif %}
                    </td>

                    <td class="px-6 py-4 text-center text-sm text-gray-90">
                        {{ item.owner_name }}
                    </td>

                    <td class="px-6 py-4 text-right text-sm font-semibold text-gray-90">
                        <span class="whitespace-nowrap">{{ item.price_formatted }}</span>
                    </td>

                    {% if tax_enable and (tax_applies_to == 1 or tax_applies_to == 4) %}
                    <td class="px-6 py-4 text-center text-sm text-gray-90">
                        {% if item.tax_perc is null %}
                        {{ global_tax_perc }} %
                        {% else %}
                        {{ item.tax_perc }} %
                        {% endif %}
                    </td>
                    {% endif %}

                    <td class="px-6 py-4 text-right">
                        <a
                                href="{{ url('index') ~ 'plugin/BuyCourses/src/services_edit.php?' ~ {'id': item.id}|url_encode }}"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                        >
                            <em class="fa fa-wrench fa-fw"></em>
                            {{ 'Edit'|get_lang }}
                        </a>
                    </td>
                </tr>
                {% else %}
                <tr>
                    <td colspan="{{ tax_enable and (tax_applies_to == 1 or tax_applies_to == 4) ? 8 : 7 }}" class="px-6 py-10 text-center text-sm text-gray-50">
                        {{ 'NoResults'|get_lang }}
                    </td>
                </tr>
                {% endfor %}
                </tbody>
            </table>
        </div>

        <div
                class="border-t border-gray-25 px-6 py-4
                [&_.pagination]:m-0
                [&_.pagination]:flex
                [&_.pagination]:flex-wrap
                [&_.pagination]:items-center
                [&_.pagination]:justify-center
                [&_.pagination]:gap-2
                [&_.pagination]:list-none
                [&_.pagination]:p-0
                [&_.pagination>li]:m-0
                [&_.pagination>li>a]:inline-flex
                [&_.pagination>li>a]:min-h-10
                [&_.pagination>li>a]:min-w-10
                [&_.pagination>li>a]:items-center
                [&_.pagination>li>a]:justify-center
                [&_.pagination>li>a]:rounded-xl
                [&_.pagination>li>a]:border
                [&_.pagination>li>a]:border-gray-25
                [&_.pagination>li>a]:bg-white
                [&_.pagination>li>a]:px-3
                [&_.pagination>li>a]:py-2
                [&_.pagination>li>a]:text-sm
                [&_.pagination>li>a]:font-semibold
                [&_.pagination>li>a]:text-gray-90
                [&_.pagination>li>a]:no-underline
                [&_.pagination>li>a:hover]:border-primary/30
                [&_.pagination>li>a:hover]:text-primary
                [&_.pagination>li>span]:inline-flex
                [&_.pagination>li>span]:min-h-10
                [&_.pagination>li>span]:min-w-10
                [&_.pagination>li>span]:items-center
                [&_.pagination>li>span]:justify-center
                [&_.pagination>li>span]:rounded-xl
                [&_.pagination>li>span]:border
                [&_.pagination>li>span]:border-gray-25
                [&_.pagination>li>span]:bg-white
                [&_.pagination>li>span]:px-3
                [&_.pagination>li>span]:py-2
                [&_.pagination>li>span]:text-sm
                [&_.pagination>li>span]:font-semibold
                [&_.pagination>li>span]:text-gray-90
                [&_.pagination>li.active>a]:border-primary
                [&_.pagination>li.active>a]:bg-primary
                [&_.pagination>li.active>a]:text-white
                [&_.pagination>li.active>span]:border-primary
                [&_.pagination>li.active>span]:bg-primary
                [&_.pagination>li.active>span]:text-white
                [&_.pagination>li.disabled>span]:cursor-not-allowed
                [&_.pagination>li.disabled>span]:opacity-50"
        >
            {{ service_pagination }}
        </div>
    </section>
    {% endif %}
</div>
{% endautoescape %}
