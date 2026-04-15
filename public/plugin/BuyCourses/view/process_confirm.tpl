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
                <div class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                    {{ 'PaymentMethods'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>

                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-90 sm:text-3xl">
                        {{ 'PurchaseData'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-50">
                        Review the purchase details and confirm the order.
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

    <div id="message-alert"></div>

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
                                        {{ 'Total'|get_plugin_lang('BuyCoursesPlugin') }}
                                    </div>
                                    <div class="mt-2 text-xl font-semibold text-gray-90">
                                        {{ service_item.total_price_formatted }}
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
                                        <div>{{ service_sale.buyer.name }}</div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-support-1 text-support-4">
                                        <em class="fa fa-calendar"></em>
                                    </span>
                                    <div>
                                        <div class="font-semibold text-gray-90">{{ 'PurchaseDate'|get_lang }}</div>
                                        <div>{{ service_sale.buy_date }}</div>
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

            {% if is_bank_transfer %}
                <article class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm lg:p-8">
                    <div class="space-y-5">
                        <div class="space-y-2">
                            <h2 class="text-xl font-semibold text-gray-90">
                                {{ 'BankAccountInformation'|get_plugin_lang('BuyCoursesPlugin') }}
                            </h2>
                            <p class="text-sm leading-6 text-gray-50">
                                Review the bank account details before confirming the order.
                            </p>
                        </div>

                        {% if transfer_accounts %}
                            <div class="overflow-x-auto rounded-2xl border border-gray-25">
                                <table class="min-w-full divide-y divide-gray-25 bg-white text-sm">
                                    <thead class="bg-support-2">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-90">{{ 'Name'|get_lang }}</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-90">{{ 'BankAccount'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-90">{{ 'SWIFT'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-25">
                                        {% for account in transfer_accounts %}
                                            <tr>
                                                <td class="px-4 py-3 text-gray-90">{{ account.name }}</td>
                                                <td class="px-4 py-3 text-gray-90">{{ account.account }}</td>
                                                <td class="px-4 py-3 text-gray-90">{{ account.swift }}</td>
                                            </tr>
                                        {% endfor %}
                                    </tbody>
                                </table>
                            </div>
                        {% else %}
                            <div class="rounded-2xl border border-gray-25 bg-support-2 px-4 py-3 text-sm text-gray-90">
                                No bank accounts are configured yet.
                            </div>
                        {% endif %}

                        <div class="rounded-2xl border border-info/20 bg-support-2 px-4 py-3 text-sm text-gray-90">
                            {{ 'OnceItIsConfirmedYouWillReceiveAnEmailWithTheBankInformationAndAnOrderReference'|get_plugin_lang('BuyCoursesPlugin') }}
                        </div>
                    </div>
                </article>
            {% endif %}

            {% if terms %}
                <article class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm lg:p-8">
                    <div class="space-y-5">
                        <div class="space-y-2">
                            <h2 class="text-xl font-semibold text-gray-90">
                                {{ 'TermsAndConditions'|get_plugin_lang('BuyCoursesPlugin') }}
                            </h2>
                            <p class="text-sm leading-6 text-gray-50">
                                Review and accept the terms before placing the order.
                            </p>
                        </div>

                        <div class="max-h-80 overflow-y-auto rounded-2xl border border-gray-25 bg-support-2 p-4 text-sm leading-6 text-gray-90">
                            {{ terms|raw }}
                        </div>
                    </div>
                </article>
            {% endif %}
        </section>

        <aside class="space-y-6 xl:col-span-1">
            <div class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
                <div class="space-y-5">
                    <div class="space-y-2">
                        <h2 class="text-lg font-semibold text-gray-90">Summary</h2>
                        <p class="text-sm leading-6 text-gray-50">
                            Confirm the final details before continuing.
                        </p>
                    </div>

                    <div class="space-y-3">
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
                                {{ 'Total'|get_plugin_lang('BuyCoursesPlugin') }}
                            </span>
                            <span class="text-lg font-semibold text-white">
                                {{ service_item.total_price_formatted }}
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-3">
                        <div class="rounded-2xl border border-gray-25 bg-white p-4">
                            <div class="text-sm font-semibold text-gray-90">{{ 'Reference'|get_lang }}</div>
                            <div class="mt-2 text-sm text-gray-50">{{ service_sale.reference }}</div>
                        </div>

                        <div class="rounded-2xl border border-gray-25 bg-white p-4">
                            <div class="text-sm font-semibold text-gray-90">{{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }}</div>
                            <div class="mt-2 text-sm text-gray-50">{{ appliesToLabel }}</div>
                        </div>

                        <div class="rounded-2xl border border-gray-25 bg-white p-4">
                            <div class="text-sm font-semibold text-gray-90">{{ 'Duration'|get_lang }}</div>
                            <div class="mt-2 text-sm text-gray-50">{{ durationLabel }}</div>
                        </div>
                    </div>

                    <form method="post" action="{{ confirm_url }}" id="confirm-order-form" class="space-y-4">
                        {% if terms %}
                            <label class="flex items-start gap-3 rounded-2xl border border-gray-25 bg-white px-4 py-3">
                                <input
                                    type="checkbox"
                                    id="confirmTermsAndConditions"
                                    name="accept_terms"
                                    value="1"
                                    class="mt-1 h-4 w-4 border-gray-25 text-primary focus:ring-primary"
                                >
                                <span class="text-sm text-gray-90">
                                    {{ 'IConfirmIReadAndAcceptTermsAndCondition'|get_plugin_lang('BuyCoursesPlugin') }}
                                </span>
                            </label>
                        {% endif %}

                        {% if is_bank_transfer %}
                            <div class="rounded-2xl border border-info/20 bg-support-2 px-4 py-3 text-sm text-gray-90">
                                Bank transfer orders remain pending until payment is verified.
                            </div>
                        {% elseif is_culqi_payment %}
                            <div class="rounded-2xl border border-info/20 bg-support-2 px-4 py-3 text-sm text-gray-90">
                                You will be prompted to complete the card payment in the next step.
                            </div>
                        {% elseif is_cecabank_payment %}
                            <div class="rounded-2xl border border-info/20 bg-support-2 px-4 py-3 text-sm text-gray-90">
                                You will be redirected to Cecabank to complete the payment.
                            </div>
                        {% endif %}

                        <div class="flex flex-col gap-3 sm:flex-row">
                            {% if is_culqi_payment %}
                                <button
                                    type="button"
                                    id="confirm"
                                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-success px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2"
                                >
                                    <em class="fa fa-check fa-fw"></em>
                                    {{ 'ConfirmOrder'|get_plugin_lang('BuyCoursesPlugin') }}
                                </button>
                            {% else %}
                                <button
                                    type="submit"
                                    name="action"
                                    value="confirm"
                                    id="confirm"
                                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-success px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2"
                                >
                                    <em class="fa fa-check fa-fw"></em>
                                    {{ 'ConfirmOrder'|get_plugin_lang('BuyCoursesPlugin') }}
                                </button>
                            {% endif %}

                            <button
                                type="submit"
                                name="action"
                                value="cancel"
                                id="cancel"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-danger px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-danger/30 focus:ring-offset-2"
                            >
                                <em class="fa fa-times fa-fw"></em>
                                {{ 'CancelOrder'|get_plugin_lang('BuyCoursesPlugin') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
    (function () {
        const confirmButton = document.getElementById('confirm');
        const termsCheckbox = document.getElementById('confirmTermsAndConditions');

        if (confirmButton && termsCheckbox) {
            confirmButton.disabled = true;
            confirmButton.classList.add('opacity-50', 'cursor-not-allowed');

            termsCheckbox.addEventListener('change', function () {
                const enabled = termsCheckbox.checked;
                confirmButton.disabled = !enabled;
                confirmButton.classList.toggle('opacity-50', !enabled);
                confirmButton.classList.toggle('cursor-not-allowed', !enabled);
            });
        }

        {% if is_culqi_payment|default(false) %}
        const confirmCulqiButton = document.getElementById('confirm');
        const cancelCulqiButton = document.getElementById('cancel');

        if (confirmCulqiButton) {
            confirmCulqiButton.addEventListener('click', function (event) {
                event.preventDefault();

                {% if terms %}
                if (termsCheckbox && !termsCheckbox.checked) {
                    return;
                }
                {% endif %}

                const price = {{ service_sale.price|default(0) }} * 100;

                Culqi.codigoComercio = '{{ culqi_params.commerce_code }}';
                Culqi.configurar({
                    nombre: '{{ _s.institution|e('js') }}',
                    orden: '{{ service_sale.reference|e('js') }}',
                    moneda: '{{ currency.iso_code|e('js') }}',
                    descripcion: '{{ service.name|e('js') }}',
                    monto: price
                });

                Culqi.abrir();

                const watcher = window.setInterval(function () {
                    const modal = document.querySelector('.culqi_checkout');

                    if (!modal) {
                        return;
                    }

                    if (Culqi.error) {
                        const alertContainer = document.getElementById('message-alert');

                        if (alertContainer) {
                            alertContainer.innerHTML =
                                '<div class="rounded-2xl border border-danger/20 bg-danger/10 px-4 py-3 text-sm text-gray-90">' +
                                '{{ 'ErrorOccurred'|get_plugin_lang('BuyCoursesPlugin')|e('js') }}'.replace('%s', Culqi.error.codigo).replace('%s', Culqi.error.mensaje) +
                                '</div>';
                        }

                        window.clearInterval(watcher);
                        return;
                    }

                    if (Culqi.token) {
                        const ajaxUrl = '{{ culqi_charge_url|e('js') }}' + encodeURIComponent(Culqi.token.id) + '&service_sale_id={{ service_sale.id }}';

                        $.ajax({
                            type: 'POST',
                            url: ajaxUrl,
                            beforeSend: function () {
                                confirmCulqiButton.disabled = true;
                                cancelCulqiButton.disabled = true;
                                confirmCulqiButton.classList.add('opacity-50', 'cursor-not-allowed');
                                cancelCulqiButton.classList.add('opacity-50', 'cursor-not-allowed');
                            },
                            success: function () {
                                window.location = '{{ catalog_url|e('js') }}';
                            }
                        });

                        window.clearInterval(watcher);
                    }
                }, 700);
            });
        }
        {% endif %}
    })();
</script>
{% endautoescape %}
