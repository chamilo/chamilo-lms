{% autoescape false %}
<div class="mx-auto w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
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
                        {{ 'CouponsPageIntro'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <a
                        href="{{ back_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                >
                    <em class="fa fa-arrow-left fa-fw"></em>
                    {{ 'Back'|get_lang }}
                </a>

                <a
                        href="{{ new_coupon_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                >
                    <em class="fa fa-plus fa-fw"></em>
                    {{ 'CouponAdd'|get_plugin_lang('BuyCoursesPlugin') }}
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'CouponStatus'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ coupon_statuses[selected_status] ?? '—' }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Coupons'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ coupon_list|length }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Scope'|get_lang }}
                </div>
                <div class="mt-2 text-sm leading-6 text-gray-90">
                    {{ 'CouponsScopeHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'Search'|get_lang }}
                </h2>
                <p class="text-sm text-gray-50">
                    {{ 'CouponsFilterHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                </p>
            </div>
        </div>

        <div class="p-6">
            <form method="get" action="" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                <div class="space-y-2">
                    <label for="coupon_status" class="block text-sm font-semibold text-gray-90">
                        {{ 'CouponStatus'|get_plugin_lang('BuyCoursesPlugin') }}
                    </label>

                    <select
                            id="coupon_status"
                            name="status"
                            class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm focus:border-primary focus:ring-primary"
                    >
                        {% for statusValue, statusLabel in coupon_statuses %}
                        <option value="{{ statusValue }}" {{ statusValue == selected_status ? 'selected' : '' }}>
                            {{ statusLabel }}
                        </option>
                        {% endfor %}
                    </select>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                    >
                        <em class="fa fa-filter fa-fw"></em>
                        {{ 'Search'|get_lang }}
                    </button>

                    <a
                            href="{{ app.request.pathInfo }}"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                    >
                        <em class="fa fa-eraser fa-fw"></em>
                        {{ 'Reset'|get_lang }}
                    </a>
                </div>
            </form>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-90">
                        {{ 'CouponList'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-50">
                        {{ coupon_list|length }} result{{ coupon_list|length == 1 ? '' : 's' }}
                    </p>
                </div>

                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ selected_status == coupon_status_active ? 'bg-success text-white' : 'bg-gray-20 text-gray-90' }}">
                    {{ coupon_statuses[selected_status] ?? '—' }}
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-25">
                <thead class="bg-gray-15">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'CouponCode'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'CouponDiscountType'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'CouponDiscount'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'CouponDateStart'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'CouponDateEnd'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'CouponDelivered'|get_plugin_lang('BuyCoursesPlugin') }}
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Options'|get_lang }}
                    </th>
                </tr>
                </thead>

                <tbody class="divide-y divide-gray-25 bg-white">
                {% for coupon in coupon_list %}
                <tr class="transition hover:bg-support-2">
                    <td class="px-4 py-4 text-sm font-semibold text-gray-90">
                        {{ coupon.code }}
                    </td>

                    <td class="px-4 py-4 text-sm text-gray-90">
                                <span class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                                    {{ coupon.discount_type_label }}
                                </span>
                    </td>

                    <td class="px-4 py-4 text-sm font-semibold text-gray-90">
                        {{ coupon.discount_value }}
                    </td>

                    <td class="px-4 py-4 text-sm text-gray-90">
                        {{ coupon.valid_start|api_get_local_time }}
                    </td>

                    <td class="px-4 py-4 text-sm text-gray-90">
                        {{ coupon.valid_end|api_get_local_time }}
                    </td>

                    <td class="px-4 py-4 text-center text-sm text-gray-90">
                        {{ coupon.delivered }}
                    </td>

                    <td class="px-4 py-4 text-right">
                        <a
                                title="{{ 'ConfigureCoupon'|get_plugin_lang('BuyCoursesPlugin') }}"
                                href="{{ url('index') ~ 'plugin/BuyCourses/src/configure_coupon.php?' ~ {'id': coupon.id}|url_encode }}"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-info px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-info/30 focus:ring-offset-2"
                        >
                            <em class="fa fa-wrench fa-fw"></em>
                            {{ 'Configure'|get_lang }}
                        </a>
                    </td>
                </tr>
                {% else %}
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center">
                        <div class="mx-auto max-w-md space-y-2">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-support-2 text-primary">
                                <em class="fa fa-ticket text-xl"></em>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-90">
                                {{ 'NoResults'|get_lang }}
                            </h3>
                            <p class="text-sm text-gray-50">
                                {{ 'NoCouponsFoundForStatus'|get_plugin_lang('BuyCoursesPlugin') }}
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
