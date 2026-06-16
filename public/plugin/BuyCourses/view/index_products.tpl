{% autoescape false %}
{% import _self as cards %}

<style>
    .bc-translated-html p {margin: 0 0 0.5rem;}
    .bc-translated-html ul {margin: 0.5rem 0 0.5rem 1.25rem; padding-left: 1.25rem; list-style: disc;}
    .bc-translated-html ol {margin: 0.5rem 0 0.5rem 1.25rem; padding-left: 1.25rem; list-style: decimal;}
    .bc-translated-html li {margin: 0.2rem 0;}
</style>

{#
    Direct product listing rendered on the BuyCourses landing page for non-admin
    users. Card markup mirrors view/catalog.tpl and view/subscription_catalog.tpl
    so the look and the buy/description links stay consistent. The buy script
    (process.php vs subscription_process.php) and product type (1 course, 2
    session) can be defined per item, with section values as fallback.
#}

{% macro course_card(course, buy_script, t) %}
    {% set item_buy_script = course.buy_script|default(buy_script) %}
    {% set item_buy_type = course.buy_type|default(t) %}
    {% set description_url = url('index') ~ 'plugin/BuyCourses/src/course_information.php?' ~ {'course_id': course.id}|url_encode %}
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
                        href="{{ description_url }}"
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
                    <a
                        class="ajax inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                        href="{{ description_url }}"
                        data-title="{{ course.title }}"
                    >
                        <em class="fa fa-file-text fa-fw"></em>
                        {{ 'SeeDescription'|get_plugin_lang('BuyCoursesPlugin') }}
                    </a>

                    <a
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-success px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2"
                        href="{{ url('index') ~ 'plugin/BuyCourses/src/' ~ item_buy_script ~ '?' ~ {'i': course.id, 't': item_buy_type}|url_encode }}"
                    >
                        <em class="fa fa-shopping-cart fa-fw"></em>
                        {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                    </a>
                </div>
            {% endif %}
        </div>
    </article>
{% endmacro %}

{% macro session_card(session, buy_script, t) %}
    {% set item_buy_script = session.buy_script|default(buy_script) %}
    {% set item_buy_type = session.buy_type|default(t) %}
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
                        href="{{ url('index') ~ 'plugin/BuyCourses/src/' ~ item_buy_script ~ '?' ~ {'i': session.id, 't': item_buy_type}|url_encode }}"
                    >
                        <em class="fa fa-shopping-cart fa-fw"></em>
                        {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                    </a>
                </div>
            {% endif %}
        </div>
    </article>
{% endmacro %}

{% macro service_card(service, can_buy) %}
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

            <div class="bc-translated-html text-sm text-gray-50">
                {{ service.description|raw }}
            </div>

            <div class="rounded-2xl border border-primary/10 bg-support-1 px-4 py-3">
                <div class="text-xs font-semibold uppercase tracking-wide text-primary">
                    {{ 'Price'|get_lang }}
                </div>
                <div class="mt-1 text-base font-semibold text-gray-90">
                    {{ service.display_price|default(service.total_price|default('')) }}
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-semibold text-gray-50">
                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1">
                        <em class="fa fa-calendar fa-fw text-primary"></em>
                        {{ service.billing_cycle_label|default('') }}
                    </span>
                    {% if service.duration_label|default('') %}
                        <span class="inline-flex items-center rounded-full bg-white px-2.5 py-1">
                            {{ service.duration_label }}
                        </span>
                    {% endif %}
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <a
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                    href="{{ url('index') ~ 'plugin/BuyCourses/src/service_information.php?' ~ {'service_id': service.id}|url_encode }}"
                >
                    <em class="fa fa-file-text fa-fw"></em>
                    {{ 'SeeDescription'|get_plugin_lang('BuyCoursesPlugin') }}
                </a>

                {% if service.has_blocking_sale|default(false) %}
                    <span class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary/15 px-4 py-2.5 text-sm font-semibold text-primary">
                        <em class="fa fa-check-circle fa-fw"></em>
                        {{ 'Already purchased'|get_lang }}
                    </span>
                {% else %}
                    {% if service.has_pending_sale|default(false) %}
                        <span class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-warning/15 px-4 py-2.5 text-sm font-semibold text-warning">
                            <em class="fa fa-clock-o fa-fw"></em>
                            {{ 'PayoutStatusPending'|get_plugin_lang('BuyCoursesPlugin') }}
                        </span>
                    {% endif %}

                    {% if can_buy|default(false) %}
                        <a
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-success px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2"
                            href="{{ url('index') ~ 'plugin/BuyCourses/src/service_process.php?' ~ {'i': service.id, 't': service.applies_to|default(0)}|url_encode }}"
                        >
                            <em class="fa fa-shopping-cart fa-fw"></em>
                            {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                        </a>
                    {% else %}
                        <span class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gray-20 px-4 py-2.5 text-sm font-semibold text-gray-50">
                            <em class="fa fa-lock fa-fw"></em>
                            {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                        </span>
                    {% endif %}
                {% endif %}
            </div>
        </div>
    </article>
{% endmacro %}

<div class="mt-8 space-y-12">
    {% if sections is empty %}
        <section class="rounded-3xl border border-gray-25 bg-white p-10 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-support-2 text-primary">
                <em class="fa fa-shopping-cart text-xl"></em>
            </div>
            <h2 class="mt-4 text-lg font-semibold text-gray-90">
                {{ 'NoProductsAvailable'|get_plugin_lang('BuyCoursesPlugin') }}
            </h2>
        </section>
    {% endif %}

    {% for section in sections %}
        <section>
            <div class="mb-5 flex items-center justify-between gap-4">
                <h2 class="text-2xl font-semibold tracking-tight text-gray-90">
                    {{ section.title }}
                </h2>

                {% if section.see_all_links is defined and section.see_all_links %}
                    <div class="flex flex-wrap justify-end gap-2">
                        {% for seeAllLink in section.see_all_links %}
                            <a
                                href="{{ seeAllLink.url }}"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                            >
                                {{ 'See all'|get_lang }} ({{ seeAllLink.total }})
                                <span class="font-semibold text-primary">&rarr;</span>
                            </a>
                        {% endfor %}
                    </div>
                {% elseif section.total > section.items|length and section.see_all_url is defined and section.see_all_url %}
                    <a
                        href="{{ section.see_all_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                    >
                        {{ 'See all'|get_lang }} ({{ section.total }})
                        <span class="font-semibold text-primary">&rarr;</span>
                    </a>
                {% endif %}
            </div>

            {% if section.card == 'service' and section.billing_cycle_tabs is defined and section.billing_cycle_tabs %}
                <nav class="mb-5 overflow-x-auto" aria-label="{{ 'BillingPeriod'|get_plugin_lang('BuyCoursesPlugin') }}">
                    <div class="inline-flex rounded-2xl border border-gray-25 bg-white p-1 shadow-sm">
                        {% for billingTab in section.billing_cycle_tabs %}
                            <a
                                href="{{ billingTab.url }}"
                                class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ billingTab.active ? 'bg-primary text-white shadow-sm' : 'text-gray-90 hover:bg-support-2 hover:text-primary' }}"
                            >
                                {{ billingTab.label }}
                            </a>
                        {% endfor %}
                    </div>
                </nav>
            {% endif %}

            {% if section.card == 'service' and buyer_role_notice %}
                <div class="mb-4 rounded-2xl border border-warning bg-support-6 px-4 py-3 text-sm text-gray-90">
                    <em class="fa fa-info-circle fa-fw"></em>
                    {{ buyer_role_notice }}
                </div>
            {% endif %}

            {% if section.card == 'service' and section.items is empty %}
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
            {% else %}
                <div class="grid gap-6 sm:grid-cols-2 2xl:grid-cols-3">
                    {% for item in section.items %}
                        {% if section.card == 'course' %}
                            {{ cards.course_card(item, section.buy_script, section.buy_type) }}
                        {% elseif section.card == 'session' %}
                            {{ cards.session_card(item, section.buy_script, section.buy_type) }}
                        {% elseif section.card == 'service' %}
                            {{ cards.service_card(item, can_buy_services) }}
                        {% endif %}
                    {% endfor %}
                </div>
            {% endif %}
        </section>
    {% endfor %}
</div>
{% endautoescape %}
