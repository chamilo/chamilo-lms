{% autoescape false %}
{% set formShell = 'rounded-2xl border border-gray-25 bg-white p-6 shadow-sm [&_form]:space-y-6 [&_.form-group]:mb-5 [&_.form-group]:space-y-3 [&_label]:mb-3 [&_label]:block [&_label]:text-sm [&_label]:font-semibold [&_label]:text-gray-90 [&_.form-control]:w-full [&_.form-control]:rounded-xl [&_.form-control]:border-gray-25 [&_.form-control]:bg-white [&_.form-control]:px-4 [&_.form-control]:py-2.5 [&_.form-control]:text-gray-90 [&_.form-control]:shadow-sm [&_.form-control]:focus:border-primary [&_.form-control]:focus:ring-primary/20 [&_select]:w-full [&_select]:rounded-xl [&_select]:border-gray-25 [&_select]:bg-white [&_select]:px-4 [&_select]:py-2.5 [&_select]:text-gray-90 [&_.btn]:inline-flex [&_.btn]:items-center [&_.btn]:justify-center [&_.btn]:gap-2 [&_.btn]:rounded-xl [&_.btn]:px-4 [&_.btn]:py-2.5 [&_.btn]:text-sm [&_.btn]:font-semibold [&_.btn]:shadow-sm [&_.btn]:transition [&_.btn-primary]:bg-primary [&_.btn-primary]:text-white [&_.btn-default]:border [&_.btn-default]:border-gray-25 [&_.btn-default]:bg-white [&_.btn-default]:text-gray-90 [&_.btn-success]:bg-success [&_.btn-success]:text-white [&_.btn-danger]:bg-danger [&_.btn-danger]:text-white [&_.btn-info]:bg-info [&_.btn-info]:text-white [&_.help-block]:mt-2 [&_.help-block]:block [&_.help-block]:text-sm [&_.help-block]:text-gray-50' %}
{% set tableShell = 'overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm [&_table]:min-w-full [&_table]:divide-y [&_table]:divide-gray-25 [&_thead]:bg-gray-15 [&_th]:px-4 [&_th]:py-3 [&_th]:text-left [&_th]:text-xs [&_th]:font-semibold [&_th]:uppercase [&_th]:tracking-wide [&_th]:text-gray-50 [&_td]:px-4 [&_td]:py-4 [&_td]:align-middle [&_td]:text-sm [&_td]:text-gray-90 [&_tbody_tr]:border-t [&_tbody_tr]:border-gray-20 [&_tbody_tr:hover]:bg-gray-15/60' %}
{% set btnDanger = 'inline-flex items-center justify-center gap-2 rounded-xl bg-danger px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-danger/30 focus:ring-offset-2' %}
{% set btnBack = 'inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 shadow-sm transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2' %}

<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <span class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-primary">
                    {{ 'Buy courses'|get_plugin_lang('BuyCoursesPlugin') }}
                </span>
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight text-gray-90">
                        {{ page_title }}
                    </h1>
                    <p class="mt-2 text-sm text-gray-50">
                        Configure tax and subscription periods for this product.
                    </p>
                </div>
            </div>

            <a href="{{ back_url }}" class="{{ btnBack }}">
                <em class="fa fa-arrow-left"></em>
                {{ 'Back'|get_lang }}
            </a>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-gray-25 bg-gray-10 px-5 py-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'ProductType'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-lg font-semibold text-gray-90">
                    {{ product_label }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-gray-10 px-5 py-4 md:col-span-2">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Name'|get_lang }}
                </div>
                <div class="mt-2 text-lg font-semibold text-gray-90">
                    {{ product_name }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-gray-10 px-5 py-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Configured periods'|default('Configured periods') }}
                </div>
                <div class="mt-2 text-lg font-semibold text-gray-90">
                    {{ subscriptions_count }}
                </div>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <h3 class="text-base font-semibold text-gray-90">
                {{ 'Subscription settings'|default('Subscription settings') }}
            </h3>
            <p class="mt-1 text-sm text-gray-50">
                Update the tax rate applied to this subscription product.
            </p>
        </div>

        <div class="p-6">
            <div class="{{ formShell }} border-0 p-0 shadow-none">
                {{ items_form }}
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <h3 class="text-base font-semibold text-gray-90">
                {{ 'FrequencyConfig'|get_plugin_lang('BuyCoursesPlugin') }}
            </h3>
            <p class="mt-1 text-sm text-gray-50">
                Add or remove subscription periods for this product.
            </p>
        </div>

        <div class="grid gap-6 p-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
            <div class="{{ formShell }} border-0 p-0 shadow-none">
                {{ frequency_form }}
            </div>

            <div class="{{ tableShell }}">
                <table>
                    <thead>
                    <tr>
                        <th>{{ 'Duration'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th>{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th class="text-right">{{ 'Actions'|get_lang }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {% if subscriptions is empty %}
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-50">
                                {{ 'No subscriptions configured yet.' }}
                            </td>
                        </tr>
                    {% else %}
                        {% for subscription in subscriptions %}
                            <tr>
                                <td>
                                    <span class="font-semibold text-gray-90">
                                        {{ subscription.durationName }}
                                    </span>
                                </td>
                                <td>
                                    <span class="font-semibold text-gray-90">
                                        {{ subscription.price }} {{ currencyIso }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <form
                                        method="post"
                                        action="{{ delete_action_url }}"
                                        class="inline-flex"
                                        onsubmit="return confirm('Are you sure you want to remove this subscription period?');"
                                    >
                                        <input type="hidden" name="action" value="delete_frequency">
                                        <input type="hidden" name="duration" value="{{ subscription.duration }}">

                                        <button type="submit" class="{{ btnDanger }}">
                                            <em class="fa fa-remove"></em>
                                            {{ 'Delete'|get_lang }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        {% endfor %}
                    {% endif %}
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
{% endautoescape %}
