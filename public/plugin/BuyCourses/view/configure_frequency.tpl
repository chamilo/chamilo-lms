{% autoescape false %}
{% set btnDanger = 'inline-flex items-center justify-center gap-2 rounded-xl bg-danger px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-danger/30 focus:ring-offset-2' %}

<div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
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
                        {{ 'ConfigureFrequencyIntro'|get_plugin_lang('BuyCoursesPlugin') }}
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
                    href="{{ subscriptions_list_url }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                >
                    <em class="fa fa-repeat fa-fw"></em>
                    {{ 'SubscriptionList'|get_plugin_lang('BuyCoursesPlugin') }}
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'ConfiguredPeriods'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ frequencies_count }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'DurationUnit'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ 'Days'|get_lang }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Usage'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-sm leading-6 text-gray-90">
                    {{ 'FrequencyUsageHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'AddPeriods'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>
                <p class="text-sm text-gray-50">
                    {{ 'AddPeriodsHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                </p>
            </div>
        </div>

        <div class="p-6">
            {{ items_form }}
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'ConfiguredPeriods'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>
                <p class="text-sm text-gray-50">
                    {{ 'ConfiguredPeriodsHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-25">
                <thead class="bg-gray-15">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'Name'|get_lang }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'SubscriptionPeriodDuration'|get_plugin_lang('BuyCoursesPlugin') }}
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'Options'|get_lang }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-25 bg-white">
                    {% for frequency in frequencies_list %}
                        <tr class="transition hover:bg-support-2">
                            <td class="px-4 py-4 text-sm font-medium text-gray-90">
                                {{ frequency.name }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-90">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                                        {{ frequency.duration }} {{ 'Days'|get_lang }}
                                    </span>
                                    {% if frequency.in_use %}
                                        <span class="inline-flex items-center rounded-full bg-warning/10 px-3 py-1 text-xs font-semibold text-warning">
                                            {{ 'FrequencyInUse'|get_plugin_lang('BuyCoursesPlugin')|format(frequency.usage_count) }}
                                        </span>
                                    {% endif %}
                                </div>
                            </td>
                            <td class="px-4 py-4 text-right">
                                {% if frequency.in_use %}
                                    <button
                                        type="button"
                                        disabled
                                        class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-gray-25 px-4 py-2.5 text-sm font-semibold text-gray-50"
                                        title="This subscription period is currently in use and cannot be deleted."
                                    >
                                        <em class="fa fa-lock fa-fw"></em>
                                        {{ 'Delete'|get_lang }}
                                    </button>
                                {% else %}
                                    <form method="post" action="{{ delete_action_url }}" class="inline-flex">
                                        <input type="hidden" name="action" value="delete_frequency">
                                        <input type="hidden" name="duration" value="{{ frequency.duration }}">
                                        <button
                                            type="submit"
                                            title="{{ 'DeleteFrequency'|get_plugin_lang('BuyCoursesPlugin') }}"
                                            class="{{ btnDanger }}"
                                            onclick="return confirm('{{ 'AreYouSureToDelete'|get_lang|e('js') }}');"
                                        >
                                            <em class="fa fa-remove fa-fw"></em>
                                            {{ 'Delete'|get_lang }}
                                        </button>
                                    </form>
                                {% endif %}
                            </td>
                        </tr>
                    {% else %}
                        <tr>
                            <td colspan="3" class="px-4 py-10 text-center text-sm text-gray-50">
                                {{ 'No Results'|get_lang }}
                            </td>
                        </tr>
                    {% endfor %}
                </tbody>
            </table>
        </div>
    </section>
</div>
{% endautoescape %}
