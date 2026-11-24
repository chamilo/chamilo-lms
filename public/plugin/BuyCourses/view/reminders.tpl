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
                        {{ 'PurchaseRemindersIntro'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ back_url }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary">
                    <em class="mdi mdi-arrow-left rtl:rotate-180"></em>
                    {{ 'Back'|get_lang }}
                </a>
                <a href="{{ new_reminder_url }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">
                    <em class="mdi mdi-plus"></em>
                    {{ 'PurchaseReminderAdd'|get_plugin_lang('BuyCoursesPlugin') }}
                </a>
            </div>
        </div>
        {% if feature_enabled and cron_is_stale %}
            <div class="mt-6 rounded-2xl border border-warning/30 bg-warning/10 p-4 text-sm leading-6 text-gray-90" role="alert">
                {{ 'PurchaseRemindersCronMissing'|get_plugin_lang('BuyCoursesPlugin')|format(cron_command) }}
            </div>
        {% endif %}
        {% if not feature_enabled %}
            <div class="mt-6 rounded-2xl border border-gray-25 bg-support-2 p-4 text-sm leading-6 text-gray-90">
                {{ 'PurchaseRemindersFeatureDisabled'|get_plugin_lang('BuyCoursesPlugin') }}
            </div>
        {% endif %}
    </section>

    <section class="rounded-3xl border border-gray-25 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-90">{{ 'PurchaseReminderList'|get_plugin_lang('BuyCoursesPlugin') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-support-2 text-xs uppercase tracking-wide text-gray-50">
                    <tr>
                        <th class="px-6 py-3">{{ 'PurchaseReminderDaysAfter'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th class="px-6 py-3">{{ 'PurchaseReminderDiscountType'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th class="px-6 py-3">{{ 'PurchaseReminderProducts'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th class="px-6 py-3">{{ 'Status'|get_lang }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    {% if reminder_list is empty %}
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-50">
                                {{ 'PurchaseReminderEmpty'|get_plugin_lang('BuyCoursesPlugin') }}
                            </td>
                        </tr>
                    {% endif %}
                    {% for reminder in reminder_list %}
                        <tr class="border-t border-gray-25">
                            <td class="px-6 py-4 font-semibold text-gray-90">{{ reminder.days_after }}</td>
                            <td class="px-6 py-4">{{ reminder.discount_value }}</td>
                            <td class="px-6 py-4">{{ reminder.product_count }}</td>
                            <td class="px-6 py-4">
                                {% if reminder.active %}
                                    {{ 'PurchaseReminderActive'|get_plugin_lang('BuyCoursesPlugin') }}
                                {% else %}
                                    {{ 'PurchaseReminderInactive'|get_plugin_lang('BuyCoursesPlugin') }}
                                {% endif %}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <a href="reminder_add.php?id={{ reminder.id }}" class="rounded-lg border border-gray-25 px-3 py-1.5 font-semibold text-primary hover:bg-support-2">
                                        {{ 'Edit'|get_lang }}
                                    </a>
                                    <form method="post" action="reminders.php" onsubmit="return confirm({{ 'PurchaseReminderConfirmDelete'|get_plugin_lang('BuyCoursesPlugin')|json_encode }});">
                                        <input type="hidden" name="{{ csrf_token_name }}" value="{{ csrf_token }}">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="reminder_id" value="{{ reminder.id }}">
                                        <button type="submit" class="rounded-lg border border-gray-25 px-3 py-1.5 font-semibold text-danger hover:bg-support-2">
                                            {{ 'Delete'|get_lang }}
                                        </button>
                                    </form>
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
