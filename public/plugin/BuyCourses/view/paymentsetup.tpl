{% autoescape false %}
<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <div class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                    {{ plugin_title }}
                </div>

                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-90 sm:text-3xl">
                        {{ page_title }}
                    </h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-50">
                        Configure the global e-commerce settings, choose the active payment methods, and update the credentials required by each gateway.
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <a
                        href="{{ plugin_index_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                >
                    <em class="fa fa-arrow-left fa-fw"></em>
                    {{ 'Back'|get_lang }}
                </a>

                <a
                        href="{{ plugin_settings_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                >
                    <em class="fa fa-sliders fa-fw"></em>
                    {{ 'PluginSettings'|get_plugin_lang('BuyCoursesPlugin') }}
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Currency'|get_lang }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ selected_currency_label }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'ActivePaymentMethods'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ enabled_payment_method_count }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'TaxSettings'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ tax_enable ? 'Enabled'|get_lang : 'Disabled'|get_lang }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Invoicing'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ invoicing_enable ? 'Enabled'|get_lang : 'Disabled'|get_lang }}
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            {% if enabled_payment_method_labels %}
            {% for methodName in enabled_payment_method_labels %}
            <span class="inline-flex items-center rounded-full bg-primary px-3 py-1 text-xs font-semibold text-white">
                        {{ methodName }}
                    </span>
            {% endfor %}
            {% else %}
            <span class="inline-flex items-center rounded-full bg-gray-20 px-3 py-1 text-xs font-semibold text-gray-50">
                    {{ 'NoPaymentMethodsEnabledYet'|get_plugin_lang('BuyCoursesPlugin') }}
                </span>
            {% endif %}
        </div>
    </section>

    <section class="rounded-2xl border border-info/20 bg-support-2 px-4 py-4 text-sm text-gray-90 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 text-primary">
                    <em class="fa fa-info-circle text-lg"></em>
                </div>
                <div class="space-y-1">
                    <p class="font-semibold text-gray-90">
                        {{ 'GatewayAvailability'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                    <p class="leading-6 text-gray-90">
                        {{ 'PluginInstruction'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>
            </div>

            <a
                    href="{{ plugin_settings_url }}"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-primary/20 bg-white px-4 py-2.5 text-sm font-semibold text-primary transition hover:border-primary/40 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
            >
                <em class="fa fa-external-link fa-fw"></em>
                {{ 'OpenPluginSettings'|get_plugin_lang('BuyCoursesPlugin') }}
            </a>
        </div>
    </section>

    <nav class="overflow-x-auto">
        <div class="inline-flex min-w-full rounded-2xl border border-gray-25 bg-white p-1 shadow-sm sm:min-w-0">
            <a href="#global-config" class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:bg-support-2 hover:text-primary">
                {{ 'GlobalConfig'|get_plugin_lang('BuyCoursesPlugin') }}
            </a>

            {% if paypal_enable %}
            <a href="#paypal-config" class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:bg-support-2 hover:text-primary">
                PayPal
            </a>
            {% endif %}

            {% if tpv_redsys_enable %}
            <a href="#redsys-config" class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:bg-support-2 hover:text-primary">
                Redsys
            </a>
            {% endif %}

            {% if commissions_enable %}
            <a href="#commissions-config" class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:bg-support-2 hover:text-primary">
                {{ 'Commissions'|get_plugin_lang('BuyCoursesPlugin') }}
            </a>
            {% endif %}

            {% if transfer_enable %}
            <a href="#transfer-config" class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:bg-support-2 hover:text-primary">
                {{ 'TransfersConfig'|get_plugin_lang('BuyCoursesPlugin') }}
            </a>
            {% endif %}

            {% if culqi_enable %}
            <a href="#culqi-config" class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:bg-support-2 hover:text-primary">
                Culqi
            </a>
            {% endif %}

            {% if stripe_enable %}
            <a href="#stripe-config" class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:bg-support-2 hover:text-primary">
                Stripe
            </a>
            {% endif %}

            {% if cecabank_enable %}
            <a href="#cecabank-config" class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:bg-support-2 hover:text-primary">
                Cecabank
            </a>
            {% endif %}
        </div>
    </nav>

    <section id="global-config" class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'GlobalConfig'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>
                <p class="text-sm text-gray-50">
                    Configure currency, terms and conditions, sales contact email, tax rules, and invoicing data.
                </p>
            </div>
        </div>

        <div class="p-6">
            {{ global_config_form }}
        </div>
    </section>

    {% if paypal_enable %}
    <section id="paypal-config" class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'PayPalConfig'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>
                <p class="text-sm text-gray-50">
                    Configure the API credentials required to process payments through PayPal.
                </p>
            </div>
        </div>

        <div class="grid gap-6 p-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
            <aside class="rounded-2xl border border-gray-25 bg-support-2 p-5">
                <h3 class="text-sm font-semibold text-gray-90">
                    {{ 'InfoApiCredentials'|get_plugin_lang('BuyCoursesPlugin') }}
                </h3>

                <ul class="mt-4 space-y-3 text-sm leading-6 text-gray-90">
                    <li class="flex gap-3">
                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">1</span>
                        <span>{{ 'InfoApiStepOne'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">2</span>
                        <span>{{ 'InfoApiStepTwo'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">3</span>
                        <span>{{ 'InfoApiStepThree'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                    </li>
                </ul>
            </aside>

            <div>
                {{ paypal_form }}
            </div>
        </div>
    </section>
    {% endif %}

    {% if tpv_redsys_enable %}
    <section id="redsys-config" class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'TpvRedsysConfig'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>
                <p class="text-sm text-gray-50">
                    Configure the Redsys terminal credentials and sandbox settings.
                </p>
            </div>
        </div>

        <div class="p-6">
            {{ tpv_redsys_form }}
        </div>
    </section>
    {% endif %}

    {% if commissions_enable %}
    <section id="commissions-config" class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'CommissionsConfig'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>
                <p class="text-sm text-gray-50">
                    Set the platform commission percentage used by the marketplace.
                </p>
            </div>
        </div>

        <div class="grid gap-6 p-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
            <aside class="rounded-2xl border border-gray-25 bg-support-2 p-5 text-sm leading-6 text-gray-90">
                {{ 'InfoCommissions'|get_plugin_lang('BuyCoursesPlugin') }}
            </aside>

            <div>
                {{ commission_form }}
            </div>
        </div>
    </section>
    {% endif %}

    {% if transfer_enable %}
    <section id="transfer-config" class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'TransfersConfig'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>
                <p class="text-sm text-gray-50">
                    Manage bank accounts for manual transfers and the extra message sent by email after a transfer purchase.
                </p>
            </div>
        </div>

        <div class="grid gap-6 p-6 xl:grid-cols-[380px_minmax(0,1fr)]">
            <div>
                {{ transfer_form }}
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm">
                <div class="border-b border-gray-25 bg-gray-15 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-90">
                        {{ 'BankAccounts'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-25">
                        <thead class="bg-gray-15">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'Name'|get_lang }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'BankAccount'|get_plugin_lang('BuyCoursesPlugin') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'SWIFT'|get_plugin_lang('BuyCoursesPlugin') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'Actions'|get_lang }}
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-25 bg-white">
                        {% for account in transfer_accounts %}
                        <tr class="transition hover:bg-support-2">
                            <td class="px-4 py-4 text-sm font-medium text-gray-90">
                                {{ account.name }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-90">
                                {{ account.account }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-90">
                                {{ account.swift }}
                            </td>
                            <td class="px-4 py-4 text-right">
                                <a
                                        href="{{ delete_transfer_base_url ~ '?' ~ {'action': 'delete_taccount', 'id': account.id}|url_encode }}"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-danger px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-danger/30 focus:ring-offset-2"
                                >
                                    <em class="fa fa-remove fa-fw"></em>
                                    {{ 'Delete'|get_lang }}
                                </a>
                            </td>
                        </tr>
                        {% else %}
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-50">
                                {{ 'NoBankAccountsConfiguredYet'|get_plugin_lang('BuyCoursesPlugin') }}
                            </td>
                        </tr>
                        {% endfor %}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-25 p-6">
            <div class="rounded-2xl border border-gray-25 bg-gray-10 p-5">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-gray-90">
                        {{ 'TransferEmailInformation'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-50">
                        Extra instructions appended to the email sent after choosing bank transfer.
                    </p>
                </div>

                {{ transfer_info_form }}
            </div>
        </div>
    </section>
    {% endif %}

    {% if culqi_enable %}
    <section id="culqi-config" class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'CulqiConfig'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>
                <p class="text-sm text-gray-50">
                    Configure the API credentials required to process payments through Culqi.
                </p>
            </div>
        </div>

        <div class="grid gap-6 p-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
            <aside class="rounded-2xl border border-gray-25 bg-support-2 p-5 text-sm leading-6 text-gray-90">
                {{ 'InfoCulqiCredentials'|get_plugin_lang('BuyCoursesPlugin') }}
            </aside>

            <div>
                {{ culqi_form }}
            </div>
        </div>
    </section>
    {% endif %}

    {% if stripe_enable %}
    <section id="stripe-config" class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'StripeConfig'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>
                <p class="text-sm text-gray-50">
                    Configure your Stripe account ID, secret key and webhook endpoint secret.
                </p>
            </div>
        </div>

        <div class="grid gap-6 p-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
            <aside class="rounded-2xl border border-gray-25 bg-support-2 p-5 text-sm leading-6 text-gray-90">
                {{ 'InfoStripeCredentials'|get_plugin_lang('BuyCoursesPlugin') }}
            </aside>

            <div>
                {{ stripe_form }}
            </div>
        </div>
    </section>
    {% endif %}

    {% if cecabank_enable %}
    <section id="cecabank-config" class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'CecabankConfig'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>
                <p class="text-sm text-gray-50">
                    Configure the keys and merchant identifiers required by Cecabank.
                </p>
            </div>
        </div>

        <div class="p-6">
            {{ cecabank_form }}
        </div>
    </section>
    {% endif %}
</div>
{% endautoescape %}
