{% autoescape false %}
<div class="mx-auto max-w-4xl space-y-6 px-4 py-8">
    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm lg:p-8">
        <div class="space-y-3">
            <h1 class="text-2xl font-semibold text-gray-90">
                {{ 'PurchaseData'|get_plugin_lang('BuyCoursesPlugin') }}
            </h1>
            <p class="text-sm text-gray-50">
                {{ 'ProcessConfirmIntro'|get_plugin_lang('BuyCoursesPlugin') }}
            </p>
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-support-2 p-5">
                <div class="font-semibold text-gray-90">{{ sale.product_name|e }}</div>
                <div class="font-semibold text-gray-90">{{ sale.price|e }} {{ currency.iso_code|e }}</div>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm lg:p-8">
        <div class="space-y-5">
            <h2 class="text-lg font-semibold text-gray-90">BTCPay Server</h2>
            <p class="text-sm leading-6 text-gray-50">
                {{ 'BtcPayConfirmMessage'|get_plugin_lang('BuyCoursesPlugin') }}
            </p>

            {% if terms %}
                <div class="space-y-2">
                    <h3 class="font-semibold text-gray-90">{{ 'TermsAndConditions'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
                    <div class="max-h-64 overflow-y-auto rounded-xl border border-gray-25 bg-support-2 p-4 text-sm text-gray-90">
                        {{ terms|raw }}
                    </div>
                </div>
            {% endif %}

            <div>{{ form }}</div>
        </div>
    </section>
</div>
{% endautoescape %}
