{% autoescape false %}
{% set formShell = 'rounded-3xl border border-gray-25 bg-white shadow-sm [&_form]:space-y-6 [&_.form-group]:mb-0 [&_.form-group]:rounded-2xl [&_.form-group]:border [&_.form-group]:border-gray-25 [&_.form-group]:bg-white [&_.form-group]:p-5 [&_.form-group]:shadow-sm [&_label]:mb-2 [&_label]:block [&_label]:text-sm [&_label]:font-semibold [&_label]:text-gray-90 [&_input:not([type=radio]):not([type=checkbox])]:block [&_input:not([type=radio]):not([type=checkbox])]:w-full [&_input:not([type=radio]):not([type=checkbox])]:rounded-xl [&_input:not([type=radio]):not([type=checkbox])]:border-gray-25 [&_input:not([type=radio]):not([type=checkbox])]:bg-white [&_input:not([type=radio]):not([type=checkbox])]:text-sm [&_input:not([type=radio]):not([type=checkbox])]:text-gray-90 [&_input:not([type=radio]):not([type=checkbox])]:shadow-sm [&_input:not([type=radio]):not([type=checkbox])]:placeholder:text-gray-50 [&_input:not([type=radio]):not([type=checkbox])]:focus:border-primary [&_input:not([type=radio]):not([type=checkbox])]:focus:ring-primary [&_select]:block [&_select]:w-full [&_select]:rounded-xl [&_select]:border-gray-25 [&_select]:bg-white [&_select]:text-sm [&_select]:text-gray-90 [&_select]:shadow-sm [&_select]:focus:border-primary [&_select]:focus:ring-primary [&_.btn]:inline-flex [&_.btn]:items-center [&_.btn]:justify-center [&_.btn]:gap-2 [&_.btn]:rounded-xl [&_.btn]:px-4 [&_.btn]:py-2.5 [&_.btn]:text-sm [&_.btn]:font-semibold [&_.btn]:shadow-sm [&_.btn]:transition [&_.btn]:hover:opacity-90 [&_.btn]:focus:outline-none [&_.btn]:focus:ring-2 [&_.btn]:focus:ring-offset-2 [&_.btn-primary]:bg-primary [&_.btn-primary]:text-white [&_.btn-primary]:focus:ring-primary/30 [&_.btn-success]:bg-success [&_.btn-success]:text-white [&_.btn-success]:focus:ring-success/30 [&_.btn-default]:border [&_.btn-default]:border-gray-25 [&_.btn-default]:bg-white [&_.btn-default]:text-gray-90 [&_.help-block]:mt-2 [&_.help-block]:block [&_.help-block]:text-sm [&_.help-block]:text-gray-50 [&_.col-sm-2]:w-full [&_.col-sm-3]:w-full [&_.col-sm-7]:w-full [&_.col-sm-8]:w-full [&_.col-sm-10]:w-full [&_.col-sm-11]:w-full' %}

<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <div class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                    {{ plugin_title|default('BuyCourses') }}
                </div>

                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-90 sm:text-3xl">
                        {{ page_title }}
                    </h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-50">
                        Review service sales, filter them by status, and optionally narrow the results by user, email, service name or reference.
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <a
                        href="{{ back_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                >
                    <em class="fa fa-arrow-left fa-fw"></em>
                    {{ 'Back'|get_lang }}
                </a>

                <a
                        href="{{ export_report_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                >
                    <em class="mdi mdi-file-excel fa-fw"></em>
                    {{ 'GenerateReport'|get_lang }}
                </a>

                {% if paypal_enable and commissions_enable %}
                <a
                        href="{{ paypal_payout_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-secondary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-secondary/30 focus:ring-offset-2"
                >
                    <em class="fa fa-paypal fa-fw"></em>
                    {{ 'PaypalPayoutCommissions'|get_plugin_lang('BuyCoursesPlugin') }}
                </a>
                {% endif %}

                {% if commissions_enable %}
                <a
                        href="{{ payout_report_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-info px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-info/30 focus:ring-offset-2"
                >
                    <em class="fa fa-money fa-fw"></em>
                    {{ 'PayoutReport'|get_plugin_lang('BuyCoursesPlugin') }}
                </a>
                {% endif %}
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Results'|get_lang }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ sales_count }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'OrderStatus'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ selected_status_label ?: '—' }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Scope'|get_lang }}
                </div>
                <div class="mt-2 text-sm leading-6 text-gray-90">
                    {{ 'ServiceSalesReportIntro'|get_plugin_lang('BuyCoursesPlugin') }}
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
    </section>

    <nav class="overflow-x-auto">
        <div class="inline-flex min-w-full rounded-2xl border border-gray-25 bg-white p-1 shadow-sm sm:min-w-0">
            <a
                    href="sales_report.php"
                    class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:bg-support-2 hover:text-primary"
            >
                {{ 'CourseSessionBlock'|get_lang }}
            </a>

            {% if services_are_included %}
            <a
                    href="service_sales_report.php"
                    class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm"
            >
                {{ 'Services'|get_plugin_lang('BuyCoursesPlugin') }}
            </a>
            {% endif %}

            <a
                    href="subscription_sales_report.php"
                    class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:bg-support-2 hover:text-primary"
            >
                {{ 'Subscriptions'|get_plugin_lang('BuyCoursesPlugin') }}
            </a>
        </div>
    </nav>

    <section class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'Search'|get_lang }}
                </h2>
                <p class="text-sm text-gray-50">
                    Filter by service order status and narrow results using a user-related text search.
                </p>
            </div>
        </div>

        <div class="p-6">
            <div class="{{ formShell }}">
                {{ form }}
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'Services'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>
                <p class="text-sm text-gray-50">
                    Review the sales returned by the current filters.
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-25">
                <thead class="bg-gray-15">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'OrderStatus'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'PaymentMethod'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'CouponDiscount'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Coupon'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Name'|get_lang }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'UserName'|get_lang }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Email'|get_lang }}
                    </th>
                </tr>
                </thead>

                <tbody class="divide-y divide-gray-25 bg-white">
                {% for sale in sale_list %}
                <tr class="transition hover:bg-support-2">
                    <td class="px-4 py-4 text-sm font-medium text-gray-90">
                        {{ sale.reference|default('') }}
                    </td>

                    <td class="px-4 py-4 text-sm text-gray-90">
                        {% if sale.status == sale_status_cancelled %}
                        <span class="inline-flex items-center rounded-full bg-danger px-3 py-1 text-xs font-semibold text-white">
                                        {{ sale.status_label }}
                                    </span>
                        {% elseif sale.status == sale_status_pending %}
                        <span class="inline-flex items-center rounded-full bg-warning px-3 py-1 text-xs font-semibold text-white">
                                        {{ sale.status_label }}
                                    </span>
                        {% elseif sale.status == sale_status_completed %}
                        <span class="inline-flex items-center rounded-full bg-success px-3 py-1 text-xs font-semibold text-white">
                                        {{ sale.status_label }}
                                    </span>
                        {% else %}
                        <span class="inline-flex items-center rounded-full bg-gray-20 px-3 py-1 text-xs font-semibold text-gray-90">
                                        {{ sale.status_label }}
                                    </span>
                        {% endif %}
                    </td>

                    <td class="px-4 py-4 text-sm text-gray-90">
                        {{ sale.date|default('')|api_get_local_time }}
                    </td>

                    <td class="px-4 py-4 text-sm text-gray-90">
                        {{ sale.payment_type|default('') }}
                    </td>

                    <td class="px-4 py-4 text-right text-sm font-semibold text-gray-90">
                        {{ sale.total_price|default('') }}
                    </td>

                    <td class="px-4 py-4 text-right text-sm text-gray-90">
                        {{ sale.total_discount|default('') }}
                    </td>

                    <td class="px-4 py-4 text-sm text-gray-90">
                        {{ sale.coupon_code|default('') }}
                    </td>

                    <td class="px-4 py-4 text-sm text-gray-90">
                        {{ sale.service_name|default(sale.name|default('')) }}
                    </td>

                    <td class="px-4 py-4 text-sm text-gray-90">
                        {{ sale.complete_user_name|default('') }}
                    </td>

                    <td class="px-4 py-4 text-sm text-gray-90">
                        {{ sale.email|default('') }}
                    </td>
                </tr>
                {% else %}
                <tr>
                    <td colspan="10" class="px-4 py-10 text-center">
                        <div class="mx-auto max-w-md space-y-2">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-support-2 text-primary">
                                <em class="fa fa-briefcase text-xl"></em>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-90">
                                {{ 'NoResults'|get_lang }}
                            </h3>
                            <p class="text-sm text-gray-50">
                                No service sales were found for the selected filters.
                            </p>
                        </div>
                    </td>
                </tr>
                {% endfor %}
                </tbody>
            </table>
        </div>
    </section>
</div>
{% endautoescape %}
