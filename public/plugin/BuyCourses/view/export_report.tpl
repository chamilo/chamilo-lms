{% autoescape false %}
<div class="mx-auto w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-support-2 to-white px-6 py-6">
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
                            {{ 'ExportReportIntro'|get_plugin_lang('BuyCoursesPlugin') }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <a
                        href="{{ back_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                    >
                        <em class="mdi mdi-arrow-left"></em>
                        {{ 'Back'|get_lang }}
                    </a>

                    <a
                        href="{{ sales_report_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                    >
                        <em class="mdi mdi-chart-bar"></em>
                        {{ 'SalesReport'|get_plugin_lang('BuyCoursesPlugin') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="grid gap-4 border-t border-gray-25 p-6 md:grid-cols-4">
            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'SaleSource'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ selected_source_label }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'OrderStatus'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ selected_status_label }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Date'|get_lang }}
                </div>
                <div class="mt-2 text-sm font-semibold text-gray-90">
                    {{ date_start }} → {{ date_end }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Results'|get_lang }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ sales_count }}
                </div>
            </div>
        </div>
    </section>

    <nav class="overflow-x-auto">
        <div class="inline-flex min-w-full rounded-2xl border border-gray-25 bg-white p-1 shadow-sm sm:min-w-0">
            <a
                href="{{ sales_report_url }}"
                class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:bg-support-2 hover:text-primary"
            >
                {{ 'CourseSessionBlock'|get_plugin_lang('BuyCoursesPlugin') }}
            </a>

            <a
                href="{{ service_sales_report_url }}"
                class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:bg-support-2 hover:text-primary"
            >
                {{ 'Services'|get_plugin_lang('BuyCoursesPlugin') }}
            </a>

            <a
                href="{{ subscription_sales_report_url }}"
                class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:bg-support-2 hover:text-primary"
            >
                {{ 'Subscriptions'|get_plugin_lang('BuyCoursesPlugin') }}
            </a>

            <a
                href="#"
                class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm"
            >
                {{ 'Export'|get_plugin_lang('BuyCoursesPlugin') }}
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
                    {{ 'ExportReportFormHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                </p>
            </div>
        </div>

        <div class="p-6">
            {{ form }}
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-gray-25 bg-gray-15 px-6 py-4 md:flex-row md:items-center md:justify-between">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'Preview'|get_lang }}
                </h2>
                <p class="text-sm text-gray-50">
                    {{ 'ExportReportPreviewHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                    {% if sales_count > preview_limit %}
                        {{ 'ExportReportPreviewLimited'|get_plugin_lang('BuyCoursesPlugin')|format(preview_limit) }}
                    {% endif %}
                </p>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-white px-4 py-3 text-right shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'ExportReportTotalAmount'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="text-lg font-semibold text-gray-90">
                    {{ total_amount }}
                </div>
            </div>
        </div>

        {% if sale_list is not empty %}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-25">
                    <thead class="bg-gray-15">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">{{ 'SaleSource'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">{{ 'OrderStatus'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">{{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">{{ 'PaymentMethod'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-50">{{ 'SalePrice'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-50">{{ 'CouponDiscount'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">{{ 'ProductName'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">{{ 'UserName'|get_lang }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">{{ 'Email'|get_lang }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-25 bg-white">
                        {% for sale in sale_list %}
                            <tr class="transition hover:bg-support-2">
                                <td class="px-4 py-4 text-sm font-medium text-gray-90">{{ sale.source_label|e }}</td>
                                <td class="px-4 py-4 text-sm text-gray-90">{{ sale.reference|e }}</td>
                                <td class="px-4 py-4 text-sm text-gray-90">
                                    {% if sale.status == -1 %}
                                        <span class="inline-flex items-center rounded-full bg-danger px-3 py-1 text-xs font-semibold text-white">{{ sale.status_label|e }}</span>
                                    {% elseif sale.status == 0 %}
                                        <span class="inline-flex items-center rounded-full bg-warning px-3 py-1 text-xs font-semibold text-white">{{ sale.status_label|e }}</span>
                                    {% elseif sale.status == 1 %}
                                        <span class="inline-flex items-center rounded-full bg-success px-3 py-1 text-xs font-semibold text-white">{{ sale.status_label|e }}</span>
                                    {% else %}
                                        <span class="inline-flex items-center rounded-full bg-gray-20 px-3 py-1 text-xs font-semibold text-gray-90">{{ sale.status_label|e }}</span>
                                    {% endif %}
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-90">{{ sale.order_date_label|e }}</td>
                                <td class="px-4 py-4 text-sm text-gray-90">{{ sale.payment_type_label|e }}</td>
                                <td class="px-4 py-4 text-right text-sm font-semibold text-gray-90">{{ sale.price_label|e }}</td>
                                <td class="px-4 py-4 text-right text-sm text-gray-90">{{ sale.discount_amount_label|e }}</td>
                                <td class="px-4 py-4 text-sm text-gray-90">{{ sale.product_name|e }}</td>
                                <td class="px-4 py-4 text-sm text-gray-90">{{ sale.username|e }}</td>
                                <td class="px-4 py-4 text-sm text-gray-90">{{ sale.email|e }}</td>
                            </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
        {% else %}
            <div class="p-8 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-support-2 text-primary">
                    <em class="mdi mdi-file-search-outline text-2xl"></em>
                </div>
                <h3 class="mt-4 text-base font-semibold text-gray-90">
                    {{ 'NoSalesFoundForExport'|get_plugin_lang('BuyCoursesPlugin') }}
                </h3>
                <p class="mt-2 text-sm text-gray-50">
                    {{ 'TryChangingSearchFilter'|get_plugin_lang('BuyCoursesPlugin') }}
                </p>
            </div>
        {% endif %}
    </section>
</div>
{% endautoescape %}
