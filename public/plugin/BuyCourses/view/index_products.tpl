{% autoescape false %}
{% import _self as cards %}

<style>
    .bc-translated-html p {margin: 0 0 0.5rem;}
    .bc-translated-html ul {margin-block: 0.5rem; margin-inline: 1.25rem 0; padding-inline-start: 1.25rem; list-style: disc;}
    .bc-translated-html ol {margin-block: 0.5rem; margin-inline: 1.25rem 0; padding-inline-start: 1.25rem; list-style: decimal;}
    .bc-translated-html li {margin: 0.2rem 0;}
    .bc-free-hero {background: linear-gradient(135deg, #e2f4e9 0%, #eef8ff 62%, #dcecf9 100%);}
    .bc-free-hero-disc {background: #ffffffcc; box-shadow: 0 16px 45px #256b9a26;}
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
                            <em class="mdi mdi-account text-primary"></em>
                            <span>{{ teacher }}</span>
                        </div>
                    {% endfor %}
                {% else %}
                    <div class="flex items-center gap-2">
                        <em class="mdi mdi-account text-primary"></em>
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
                    <em class="mdi mdi-checkbox-marked-outline"></em>
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
                        <em class="mdi mdi-file-document-outline"></em>
                        {{ 'SeeDescription'|get_plugin_lang('BuyCoursesPlugin') }}
                    </a>

                    <a
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-success px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2"
                        href="{{ url('index') ~ 'plugin/BuyCourses/src/' ~ item_buy_script ~ '?' ~ {'i': course.id, 't': item_buy_type}|url_encode }}"
                    >
                        <em class="mdi mdi-cart-outline"></em>
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
                        <em class="mdi mdi-account text-primary"></em>
                        <span>{{ session.coach }}</span>
                    </div>
                {% endif %}

                <div class="flex items-center gap-2">
                    <em class="mdi mdi-calendar text-primary"></em>
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
                    <em class="mdi mdi-checkbox-marked-outline"></em>
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
                        <em class="mdi mdi-file-document-outline"></em>
                        {{ 'SeeDescription'|get_plugin_lang('BuyCoursesPlugin') }}
                    </a>

                    <a
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-success px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2"
                        href="{{ url('index') ~ 'plugin/BuyCourses/src/' ~ item_buy_script ~ '?' ~ {'i': session.id, 't': item_buy_type}|url_encode }}"
                    >
                        <em class="mdi mdi-cart-outline"></em>
                        {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                    </a>
                </div>
            {% endif %}
        </div>
    </article>
{% endmacro %}

{% macro free_plan_card(plan) %}
    <article class="flex h-full flex-col overflow-hidden rounded-3xl border border-primary/30 bg-white shadow-sm transition hover:shadow-md">
        <div class="bc-free-hero relative flex aspect-[16/9] items-center justify-center overflow-hidden">
            <div class="absolute -left-10 -top-12 h-44 w-44 rounded-full border border-white/70" aria-hidden="true"></div>
            <div class="absolute -bottom-24 -right-8 h-56 w-56 rounded-full border border-white/70" aria-hidden="true"></div>
            <div class="bc-free-hero-disc relative flex h-28 w-28 items-center justify-center rounded-3xl border border-white" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="84" height="84" viewBox="0 0 84 84" fill="none" aria-hidden="true" focusable="false">
                    <path d="M16 28c11-4 19-3 26 3v32c-8-7-17-9-26-5V28Z" fill="#d6efe0" stroke="#237e5a" stroke-width="2.5" stroke-linejoin="round"/>
                    <path d="M68 28c-11-4-19-3-26 3v32c8-7 17-9 26-5V28Z" fill="#d6eafb" stroke="#256b9a" stroke-width="2.5" stroke-linejoin="round"/>
                    <path d="M42 31v32M23 36c4-1 8 0 12 2M49 38c4-2 8-3 12-2" stroke="#237e5a" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="m42 12 2.8 7.3L52 22l-7.2 2.7L42 32l-2.7-7.3L32 22l7.3-2.7L42 12Z" fill="#e4ad3e"/>
                </svg>
            </div>
            <span class="absolute bottom-4 left-5 rounded-full bg-white/90 px-3 py-1 text-xs font-bold uppercase tracking-wide text-primary">{{ 'PublicFreePlanPrice'|get_plugin_lang('BuyCoursesPlugin') }}</span>
        </div>
        <div class="flex flex-1 flex-col gap-4 p-5">
            <div>
                <h3 class="text-lg font-semibold text-gray-90">{{ plan.title|e }}</h3>
                <p class="mt-2 whitespace-pre-line text-sm text-gray-50">{{ plan.description|e }}</p>
            </div>
            <div class="rounded-2xl border border-primary/10 bg-support-1 px-4 py-3">
                <p class="text-base font-semibold text-primary">{{ 'PublicFreePlanPrice'|get_plugin_lang('BuyCoursesPlugin') }}</p>
                <dl class="mt-3 space-y-2 text-sm text-gray-90">
                    <div class="flex justify-between gap-2"><dt>{{ 'Courses'|get_lang }}</dt><dd class="font-semibold">{{ plan.max_courses > 0 ? plan.max_courses : 'Unlimited'|get_lang }}</dd></div>
                    <div class="flex justify-between gap-2"><dt>{{ 'PublicPlansHostingLimit'|get_plugin_lang('BuyCoursesPlugin') }}</dt><dd class="font-semibold">{{ plan.hosting_limit > 0 ? plan.hosting_limit : 'Unlimited'|get_lang }}</dd></div>
                    <div class="flex justify-between gap-2"><dt>{{ 'PublicPlansDocumentQuota'|get_plugin_lang('BuyCoursesPlugin') }}</dt><dd class="font-semibold">{{ plan.document_quota_mb > 0 ? plan.document_quota_mb ~ ' MB' : 'Unlimited'|get_lang }}</dd></div>
                </dl>
            </div>
            {% if plan.features %}
                <div class="whitespace-pre-line text-sm leading-6 text-gray-50">{{ plan.features|e }}</div>
            {% endif %}
            <div class="mt-auto pt-2">
                {% if plan.action_enabled %}
                    <a class="inline-flex w-full items-center justify-center rounded-xl bg-success px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90" href="{{ plan.url|e('html_attr') }}">
                        {{ plan.button|e }}
                    </a>
                {% else %}
                    <span class="inline-flex w-full cursor-not-allowed items-center justify-center rounded-xl bg-gray-20 px-4 py-2.5 text-sm font-semibold text-gray-50" aria-disabled="true">
                        <em class="mdi mdi-lock-outline mr-2" aria-hidden="true"></em>
                        {{ plan.button|e }}
                    </span>
                {% endif %}
                {% if plan.action_notice %}
                    <p class="mt-2 text-center text-xs font-medium leading-5 text-gray-70">{{ plan.action_notice|e }}</p>
                {% endif %}
                <p class="mt-2 text-center text-xs leading-5 text-gray-50">{{ 'PublicFreePlanEligibility'|get_plugin_lang('BuyCoursesPlugin') }}</p>
            </div>
        </div>
    </article>
{% endmacro %}

{% macro promotional_plan_card(plan) %}
    <article class="flex h-full flex-col overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm transition hover:shadow-md">
        <div class="flex aspect-[16/9] items-center justify-center bg-support-2">
            {% if plan.image_url %}
                <img alt="{{ plan.title|e('html_attr') }}" src="{{ plan.image_url|e('html_attr') }}" loading="lazy" class="h-full w-full object-cover">
            {% else %}
                <em class="mdi mdi-open-in-new text-6xl text-primary" aria-hidden="true"></em>
            {% endif %}
        </div>
        <div class="flex flex-1 flex-col gap-4 p-5">
            <div>
                <h3 class="text-lg font-semibold text-gray-90">{{ plan.title|e }}</h3>
                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-50">{{ plan.description|e }}</p>
            </div>
            <div class="rounded-2xl border border-primary/10 bg-support-1 px-4 py-3 text-sm font-semibold text-primary">
                {{ 'PublicExternalPlanPrice'|get_plugin_lang('BuyCoursesPlugin') }}
            </div>
            {% if plan.features %}
                <div class="whitespace-pre-line rounded-xl bg-support-2 p-4 text-sm leading-6 text-gray-90">{{ plan.features|e }}</div>
            {% endif %}
            <div class="mt-auto pt-2">
                <a class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90" href="{{ plan.url|e('html_attr') }}" target="_blank" rel="noopener noreferrer">
                    <em class="mdi mdi-open-in-new" aria-hidden="true"></em>
                    {{ plan.button|e }}
                </a>
            </div>
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
                        <em class="mdi mdi-calendar text-primary"></em>
                        {{ service.billing_cycle_label|default('') }}
                    </span>
                    {% if service.duration_label|default('') %}
                        <span class="inline-flex items-center rounded-full bg-white px-2.5 py-1">
                            {{ service.duration_label }}
                        </span>
                    {% endif %}
                </div>
            </div>

            {% if service.is_upgrade|default(false) and service.upgrade_offer %}
                <div class="rounded-2xl border border-info/20 bg-support-2 px-4 py-3 text-sm text-gray-90">
                    <div class="flex items-center gap-2 font-semibold text-primary">
                        <em class="mdi mdi-arrow-up-bold-circle-outline"></em>
                        {{ 'UpgradeFromService'|get_plugin_lang('BuyCoursesPlugin')|format(service.upgrade_offer.source_service_name|e) }}
                    </div>
                    <div class="mt-2 grid gap-1 text-xs text-gray-50">
                        <span>{{ 'UpgradeRemainingDays'|get_plugin_lang('BuyCoursesPlugin')|format(service.upgrade_offer.remaining_days) }}</span>
                        <span>{{ 'UpgradeProratedCredit'|get_plugin_lang('BuyCoursesPlugin') }}: - {{ service.upgrade_offer.credit_amount_formatted }}</span>
                        <span class="font-semibold text-gray-90">{{ 'UpgradePriceToday'|get_plugin_lang('BuyCoursesPlugin') }}: {{ service.upgrade_total_price_formatted }}</span>
                        {% if service.upgrade_offer.source_recurring_enabled %}
                            <span>{{ 'UpgradeNextRenewalPrice'|get_plugin_lang('BuyCoursesPlugin') }}: {{ service.display_price }}</span>
                        {% endif %}
                    </div>
                </div>
            {% endif %}

            <div class="flex flex-col gap-3">
                <a
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                    href="{{ url('index') ~ 'plugin/BuyCourses/src/service_information.php?' ~ {'service_id': service.id}|url_encode }}"
                >
                    <em class="mdi mdi-file-document-outline"></em>
                    {{ 'SeeDescription'|get_plugin_lang('BuyCoursesPlugin') }}
                </a>

                {% if service.has_blocking_sale|default(false) %}
                    <button
                        type="button"
                        class="inline-flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-primary/15 px-4 py-2.5 text-sm font-semibold text-primary opacity-80"
                        disabled
                    >
                        <em class="mdi mdi-check-circle"></em>
                        {{ 'Active'|get_plugin_lang('BuyCoursesPlugin') }}
                    </button>
                {% elseif service.has_pending_sale|default(false) %}
                    <span class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-warning/15 px-4 py-2.5 text-sm font-semibold text-warning">
                        <em class="mdi mdi-clock-outline"></em>
                        {{ 'PayoutStatusPending'|get_plugin_lang('BuyCoursesPlugin') }}
                    </span>
                {% else %}
                    {% set canBuyThisService = service.can_buy is defined ? service.can_buy : can_buy|default(false) %}
                    {% if canBuyThisService %}
                        <a
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-success px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2"
                            href="{{ url('index') ~ 'plugin/BuyCourses/src/service_process.php?' ~ {'i': service.id, 't': service.applies_to|default(0)}|url_encode }}"
                        >
                            <em class="mdi {{ service.is_upgrade|default(false) ? 'mdi-arrow-up-bold-circle-outline' : 'mdi-cart-outline' }}"></em>
                            {{ (service.is_upgrade|default(false) ? 'Upgrade' : 'Buy')|get_plugin_lang('BuyCoursesPlugin') }}
                        </a>
                    {% elseif not service.purchase_blocked_by_active_upsale_chain|default(false) %}
                        <span class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gray-20 px-4 py-2.5 text-sm font-semibold text-gray-50">
                            <em class="mdi mdi-lock"></em>
                            {{ (service.is_upgrade|default(false) ? 'Upgrade' : 'Buy')|get_plugin_lang('BuyCoursesPlugin') }}
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
                <em class="mdi mdi-cart-outline text-xl"></em>
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
                    <em class="mdi mdi-information-outline"></em>
                    {{ buyer_role_notice }}
                </div>
            {% endif %}

            {% if section.card == 'service' and section.items is empty and not public_plan_cards.free and public_plan_cards.promos is empty %}
                <div class="rounded-3xl border border-gray-25 bg-white p-10 text-center shadow-sm">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-support-2 text-primary">
                        <em class="mdi mdi-magnify text-xl"></em>
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
                    {% if section.card == 'service' and public_plan_cards.free %}
                        {{ cards.free_plan_card(public_plan_cards.free) }}
                    {% endif %}
                    {% for item in section.items %}
                        {% if section.card == 'course' %}
                            {{ cards.course_card(item, section.buy_script, section.buy_type) }}
                        {% elseif section.card == 'session' %}
                            {{ cards.session_card(item, section.buy_script, section.buy_type) }}
                        {% elseif section.card == 'service' %}
                            {{ cards.service_card(item, can_buy_services) }}
                        {% endif %}
                    {% endfor %}
                    {% if section.card == 'service' %}
                        {% for plan in public_plan_cards.promos %}
                            {{ cards.promotional_plan_card(plan) }}
                        {% endfor %}
                    {% endif %}
                </div>
            {% endif %}
        </section>
    {% endfor %}
</div>
{% endautoescape %}
