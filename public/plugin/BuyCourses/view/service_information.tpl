{% autoescape false %}
{% set appliesToLabel = '' %}
{% if service.applies_to == 1 %}
    {% set appliesToLabel = 'User'|get_lang %}
{% elseif service.applies_to == 2 %}
    {% set appliesToLabel = 'Course'|get_lang %}
{% elseif service.applies_to == 3 %}
    {% set appliesToLabel = 'Session'|get_lang %}
{% elseif service.applies_to == 4 %}
    {% set appliesToLabel = 'TemplateTitleCertificate'|get_lang %}
{% endif %}

{% set durationLabel = 'Unlimited' %}
{% if service.duration_days is defined and service.duration_days and service.duration_days > 0 %}
    {% set durationLabel = service.duration_days ~ ' ' ~ 'Days'|get_lang %}
{% endif %}

<div class="mx-auto w-full max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm lg:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 space-y-3">
                <div class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                    {{ 'Services'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>

                <div class="space-y-2">
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-90 sm:text-3xl">
                        {{ service.name }}
                    </h1>

                    {% if service.description %}
                        <div class="max-w-3xl text-sm leading-7 text-gray-50">
                            {{ service.description|raw }}
                        </div>
                    {% endif %}
                </div>
            </div>

            <div class="flex shrink-0">
                <a
                    href="service_catalog.php"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                >
                    <em class="fa fa-arrow-left fa-fw"></em>
                    {{ 'Back'|get_lang }}
                </a>
            </div>
        </div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="min-w-0 w-full space-y-6 xl:col-span-2">
            <article class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
                <div class="grid gap-0 lg:grid-cols-2">
                    <div class="border-b border-gray-25 bg-support-2 lg:border-b-0 lg:border-r">
                        {% if service.video_url %}
                            {% if essence is not null %}
                                <div class="h-full overflow-hidden">
                                    {{ essence.replace(service.video_url)|raw }}
                                </div>
                            {% else %}
                                <div class="flex h-64 items-center justify-center p-6">
                                    <a
                                        href="{{ service.video_url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary"
                                    >
                                        <em class="fa fa-external-link fa-fw"></em>
                                        {{ 'OpenVideo'|get_plugin_lang('BuyCoursesPlugin') }}
                                    </a>
                                </div>
                            {% endif %}
                        {% elseif service.image %}
                            <div class="flex h-64 items-center justify-center p-4 lg:h-full lg:min-h-[320px]">
                                <img
                                    alt="{{ service.name }}"
                                    class="max-h-full w-full rounded-2xl object-cover"
                                    src="{{ service.image }}"
                                >
                            </div>
                        {% else %}
                            <div class="flex h-64 items-center justify-center p-6 lg:h-full lg:min-h-[320px]">
                                <div class="text-center">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-primary shadow-sm">
                                        <em class="fa fa-briefcase text-2xl"></em>
                                    </div>
                                    <p class="mt-4 text-sm text-gray-50">
                                        {{ 'ServiceInformation'|get_plugin_lang('BuyCoursesPlugin') }}
                                    </p>
                                </div>
                            </div>
                        {% endif %}
                    </div>

                    <div class="p-6 lg:p-8">
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <h2 class="text-xl font-semibold text-gray-90">
                                    {{ 'ServiceInfoOverview'|get_plugin_lang('BuyCoursesPlugin') }}
                                </h2>
                                <p class="text-sm leading-6 text-gray-50">
                                    {{ 'ServiceInfoOverviewHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                                </p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-2xl bg-support-2 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                        {{ 'ServiceInfoPriceLabel'|get_plugin_lang('BuyCoursesPlugin') }}
                                    </div>
                                    <div class="mt-2 text-2xl font-semibold text-gray-90">
                                        {{ service.total_price_formatted }}
                                    </div>
                                </div>

                                {% if appliesToLabel %}
                                    <div class="rounded-2xl bg-support-2 p-4">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                            {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }}
                                        </div>
                                        <div class="mt-2 text-lg font-semibold text-gray-90">
                                            {{ appliesToLabel }}
                                        </div>
                                    </div>
                                {% endif %}

                                <div class="rounded-2xl bg-support-2 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                        {{ 'ServiceInfoDurationLabel'|get_plugin_lang('BuyCoursesPlugin') }}
                                    </div>
                                    <div class="mt-2 text-lg font-semibold text-gray-90">
                                        {{ durationLabel }}
                                    </div>
                                </div>

                                {% if service.owner_name is defined and service.owner_name %}
                                    <div class="rounded-2xl bg-support-2 p-4">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                            {{ 'ServiceInfoOwnerLabel'|get_plugin_lang('BuyCoursesPlugin') }}
                                        </div>
                                        <div class="mt-2 text-lg font-semibold text-gray-90">
                                            {{ service.owner_name }}
                                        </div>
                                    </div>
                                {% endif %}
                            </div>

                            <div class="rounded-2xl border border-gray-25 bg-white p-4">
                                <div class="text-sm font-semibold text-gray-90">
                                    {{ 'ServiceInfoPurchaseReadiness'|get_plugin_lang('BuyCoursesPlugin') }}
                                </div>
                                <ul class="mt-3 space-y-2 text-sm text-gray-50">
                                    <li class="flex items-start gap-2">
                                        <em class="fa fa-check-circle text-success mt-0.5"></em>
                                        <span>{{ 'ServiceAvailableForPurchase'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <em class="fa fa-check-circle text-success mt-0.5"></em>
                                        <span>{{ 'ServiceCouponApplicable'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <em class="fa fa-check-circle text-success mt-0.5"></em>
                                        <span>{{ 'ServicePaymentMethodNextStep'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row">
                                <a
                                    href="service_process.php?i={{ service.id }}&t={{ service.applies_to|default(0) }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-success px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2"
                                >
                                    <em class="fa fa-shopping-cart fa-fw"></em>
                                    {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                                </a>

                                <a
                                    href="service_catalog.php"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-3 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                                >
                                    <em class="fa fa-list fa-fw"></em>
                                    {{ 'BackToList'|get_plugin_lang('BuyCoursesPlugin') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <article class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm lg:p-8">
                <div class="space-y-4">
                    <h2 class="text-xl font-semibold text-gray-90">
                        {{ 'ServiceInformation'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h2>

                    <div class="text-sm leading-7 text-gray-50">
                        {% if service_details_html %}
                            {{ service_details_html|raw }}
                        {% else %}
                            <p>{{ 'ServiceNoAdditionalInfo'|get_plugin_lang('BuyCoursesPlugin') }}</p>
                        {% endif %}
                    </div>
                </div>
            </article>
        </section>

        <aside class="w-full space-y-6 xl:col-span-1">
            <div class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
                <div class="space-y-5">
                    <div class="space-y-2">
                        <h2 class="text-lg font-semibold text-gray-90">
                            {{ 'ServiceSummaryTitle'|get_plugin_lang('BuyCoursesPlugin') }}
                        </h2>
                        <p class="text-sm leading-6 text-gray-50">
                            {{ 'ServiceSummaryHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-support-2 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm font-semibold text-gray-90">
                                {{ 'ServiceTotalLabel'|get_plugin_lang('BuyCoursesPlugin') }}
                            </span>
                            <span class="inline-flex items-center rounded-full bg-primary px-3 py-1 text-sm font-semibold text-white">
                                {{ service.total_price_formatted }}
                            </span>
                        </div>
                    </div>

                    {% if appliesToLabel %}
                        <div class="rounded-2xl border border-gray-25 bg-white p-4">
                            <div class="text-sm font-semibold text-gray-90">
                                {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }}
                            </div>
                            <div class="mt-2 text-sm text-gray-50">
                                {{ appliesToLabel }}
                            </div>
                        </div>
                    {% endif %}

                    <a
                        href="service_process.php?i={{ service.id }}&t={{ service.applies_to|default(0) }}"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-success px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2"
                    >
                        <em class="fa fa-shopping-cart fa-fw"></em>
                        {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                    </a>
                </div>
            </div>

            <div class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-90">
                        {{ 'ShareWithYourFriends'|get_lang }}
                    </h2>

                    <div class="grid gap-3">
                        <a
                            href="https://www.facebook.com/sharer/sharer.php?{{ {'u': pageUrl}|url_encode }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary"
                        >
                            <em class="fa fa-facebook fa-fw"></em>
                            {{ 'ShareOnFacebook'|get_plugin_lang('BuyCoursesPlugin') }}
                        </a>

                        <a
                            href="https://twitter.com/home?{{ {'status': service.name ~ ' ' ~ pageUrl}|url_encode }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary"
                        >
                            <em class="fa fa-twitter fa-fw"></em>
                            {{ 'ShareOnTwitter'|get_plugin_lang('BuyCoursesPlugin') }}
                        </a>

                        <a
                            href="https://www.linkedin.com/shareArticle?{{ {'mini': 'true', 'url': pageUrl, 'title': service.name }|url_encode }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary"
                        >
                            <em class="fa fa-linkedin fa-fw"></em>
                            {{ 'ShareOnLinkedin'|get_plugin_lang('BuyCoursesPlugin') }}
                        </a>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
{% endautoescape %}
