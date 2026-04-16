{% autoescape false %}
{% set appliesToLabel = '' %}
{% if service.applies_to == 0 %}
    {% set appliesToLabel = 'None'|get_lang %}
{% elseif service.applies_to == 1 %}
    {% set appliesToLabel = 'User'|get_lang %}
{% elseif service.applies_to == 2 %}
    {% set appliesToLabel = 'Course'|get_lang %}
{% elseif service.applies_to == 3 %}
    {% set appliesToLabel = 'Session'|get_lang %}
{% elseif service.applies_to == 4 %}
    {% set appliesToLabel = 'TemplateTitleCertificate'|get_lang %}
{% endif %}

{% set durationLabel = service.duration_days == 0 ? 'NoLimit'|get_lang : service.duration_days ~ ' ' ~ 'Days'|get_lang %}

<div class="mx-auto w-full max-w-screen-2xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm lg:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <span class="inline-flex items-center rounded-full bg-support-2 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-primary">
                    PayPal
                </span>

                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-90 sm:text-3xl">
                        {{ title }}
                    </h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-50">
                        {{ 'PayPalPaymentOKPleaseConfirm'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>
            </div>

            <div class="flex shrink-0">
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

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="space-y-6 xl:col-span-2">
            <article class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
                <div class="grid gap-0 lg:grid-cols-[320px_minmax(0,1fr)]">
                    <div class="bg-support-2">
                        <div class="aspect-[16/11] overflow-hidden">
                            <img
                                alt="{{ service.name }}"
                                class="h-full w-full object-cover"
                                src="{{ service.image ? service.image : 'session_default.png'|icon() }}"
                            >
                        </div>
                    </div>

                    <div class="p-6 lg:p-8">
                        <div class="space-y-5">
                            <div class="space-y-2">
                                <h2 class="text-2xl font-semibold text-gray-90">
                                    {{ service.name }}
                                </h2>

                                {% if service.description %}
                                    <div class="text-sm leading-7 text-gray-50">
                                        {{ service.description|raw }}
                                    </div>
                                {% endif %}
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                <div class="rounded-2xl bg-support-2 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                        {{ 'Total'|get_lang }}
                                    </div>
                                    <div class="mt-2 text-xl font-semibold text-gray-90">
                                        {{ service_item.total_price_formatted|default(currency_iso ~ ' ' ~ price) }}
                                    </div>
                                </div>

                                <div class="rounded-2xl bg-support-2 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                        {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }}
                                    </div>
                                    <div class="mt-2 text-base font-semibold text-gray-90">
                                        {{ appliesToLabel }}
                                    </div>
                                </div>

                                <div class="rounded-2xl bg-support-2 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                        {{ 'Duration'|get_lang }}
                                    </div>
                                    <div class="mt-2 text-base font-semibold text-gray-90">
                                        {{ durationLabel }}
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3 text-sm text-gray-50">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-support-1 text-support-4">
                                        <em class="fa fa-user"></em>
                                    </span>
                                    <div>
                                        <div class="font-semibold text-gray-90">{{ 'Buyer'|get_lang }}</div>
                                        <div>{{ user.complete_name }}</div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-support-1 text-support-4">
                                        <em class="fa fa-envelope"></em>
                                    </span>
                                    <div>
                                        <div class="font-semibold text-gray-90">{{ 'EmailAddress'|get_lang }}</div>
                                        <div>{{ user.email }}</div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-support-1 text-support-4">
                                        <em class="fa fa-hashtag"></em>
                                    </span>
                                    <div>
                                        <div class="font-semibold text-gray-90">{{ 'Reference'|get_lang }}</div>
                                        <div>{{ service_sale.reference }}</div>
                                    </div>
                                </div>

                                {% if service.owner.name is defined %}
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-support-1 text-support-4">
                                            <em class="fa fa-user-circle"></em>
                                        </span>
                                        <div>
                                            <div class="font-semibold text-gray-90">{{ 'Owner'|get_lang }}</div>
                                            <div>{{ service.owner.name }}</div>
                                        </div>
                                    </div>
                                {% endif %}
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <aside class="space-y-6 xl:col-span-1">
            <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-gray-90">
                    {{ 'ConfirmOrder'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>
                <p class="mt-2 text-sm leading-6 text-gray-50">
                    {{ 'PayPalPaymentOKPleaseConfirm'|get_plugin_lang('BuyCoursesPlugin') }}
                </p>

                <div class="mt-5 space-y-3">
                    {% if service_item.price_formatted is defined %}
                        <div class="flex items-center justify-between gap-4 rounded-2xl bg-support-2 p-4">
                            <span class="text-sm font-semibold text-gray-90">
                                {{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}
                            </span>
                            <span class="text-sm font-semibold text-gray-90">
                                {{ service_item.price_formatted }}
                            </span>
                        </div>
                    {% endif %}

                    {% if service_item.tax_enable is defined and service_item.tax_enable %}
                        <div class="flex items-center justify-between gap-4 rounded-2xl bg-support-2 p-4">
                            <span class="text-sm font-semibold text-gray-90">
                                {{ service_item.tax_name }} ({{ service_item.tax_perc_show }}%)
                            </span>
                            <span class="text-sm font-semibold text-gray-90">
                                {{ service_item.tax_amount_formatted }}
                            </span>
                        </div>
                    {% endif %}

                    {% if service_item.has_coupon is defined and service_item.has_coupon %}
                        <div class="flex items-center justify-between gap-4 rounded-2xl bg-support-2 p-4">
                            <span class="text-sm font-semibold text-gray-90">
                                {{ 'DiscountAmount'|get_plugin_lang('BuyCoursesPlugin') }}
                            </span>
                            <span class="text-sm font-semibold text-gray-90">
                                {{ service_item.discount_amount_formatted }}
                            </span>
                        </div>
                    {% endif %}

                    <div class="flex items-center justify-between gap-4 rounded-2xl bg-primary p-4">
                        <span class="text-sm font-semibold text-white">
                            {{ 'Total'|get_lang }}
                        </span>
                        <span class="text-lg font-semibold text-white">
                            {{ service_item.total_price_formatted|default(currency_iso ~ ' ' ~ price) }}
                        </span>
                    </div>
                </div>

                <form method="post" action="" class="mt-6 space-y-4">
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button
                            type="submit"
                            name="action"
                            value="confirm"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-success px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2"
                        >
                            <em class="fa fa-check fa-fw"></em>
                            {{ 'ConfirmOrder'|get_plugin_lang('BuyCoursesPlugin') }}
                        </button>

                        <button
                            type="submit"
                            name="action"
                            value="cancel"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-danger px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-danger/30 focus:ring-offset-2"
                        >
                            <em class="fa fa-times fa-fw"></em>
                            {{ 'CancelOrder'|get_plugin_lang('BuyCoursesPlugin') }}
                        </button>
                    </div>
                </form>
            </section>
        </aside>
    </div>
</div>
{% endautoescape %}
