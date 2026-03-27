{% autoescape false %}
{% set formShell = 'rounded-2xl border border-gray-25 bg-white p-6 shadow-sm [&_form]:space-y-6 [&_.form-group]:mb-4 [&_label]:mb-2 [&_label]:block [&_label]:text-sm [&_label]:font-medium [&_label]:text-gray-90 [&_.form-control]:w-full [&_.form-control]:rounded-xl [&_.form-control]:border-gray-25 [&_.form-control]:bg-white [&_.form-control]:px-4 [&_.form-control]:py-2.5 [&_.form-control]:text-gray-90 [&_.form-control]:shadow-sm [&_.form-control]:focus:border-primary [&_.form-control]:focus:ring-primary/20 [&_select]:w-full [&_select]:rounded-xl [&_select]:border-gray-25 [&_select]:bg-white [&_select]:px-4 [&_select]:py-2.5 [&_select]:text-gray-90 [&_.btn]:inline-flex [&_.btn]:items-center [&_.btn]:justify-center [&_.btn]:gap-2 [&_.btn]:rounded-xl [&_.btn]:px-4 [&_.btn]:py-2.5 [&_.btn]:text-sm [&_.btn]:font-semibold [&_.btn]:shadow-sm [&_.btn]:transition [&_.btn-primary]:bg-primary [&_.btn-primary]:text-white [&_.btn-default]:border [&_.btn-default]:border-gray-25 [&_.btn-default]:bg-white [&_.btn-default]:text-gray-90 [&_.btn-success]:bg-success [&_.btn-success]:text-white [&_.btn-danger]:bg-danger [&_.btn-danger]:text-white [&_.btn-info]:bg-info [&_.btn-info]:text-white [&_.btn--primary]:bg-primary [&_.btn--primary]:text-white [&_.btn--plain]:border [&_.btn--plain]:border-gray-25 [&_.btn--plain]:bg-white [&_.btn--plain]:text-gray-90 [&_.btn--success]:bg-success [&_.btn--success]:text-white [&_.btn--danger]:bg-danger [&_.btn--danger]:text-white [&_.btn--info]:bg-info [&_.btn--info]:text-white' %}
{% set tableShell = 'overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm [&_table]:min-w-full [&_table]:divide-y [&_table]:divide-gray-25 [&_thead]:bg-gray-15 [&_th]:px-4 [&_th]:py-3 [&_th]:text-left [&_th]:text-xs [&_th]:font-semibold [&_th]:uppercase [&_th]:tracking-wide [&_th]:text-gray-50 [&_td]:px-4 [&_td]:py-4 [&_td]:align-middle [&_td]:text-sm [&_td]:text-gray-90 [&_tbody_tr]:border-t [&_tbody_tr]:border-gray-20 [&_tbody_tr:hover]:bg-gray-15/60' %}
{% set btnDanger = 'inline-flex items-center justify-center gap-2 rounded-xl bg-danger px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-danger/30 focus:ring-offset-2' %}
<div class="space-y-6">
    <div class="{{ formShell }}">
        {{ items_form }}
    </div>

    <section class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <h3 class="text-base font-semibold text-gray-90">{{ 'FrequencyConfig'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
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
                        <th>{{ 'Actions'|get_lang }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {% for subscription in subscriptions %}
                        <tr>
                            <td>{{ subscription.durationName }}</td>
                            <td>{{ subscription.price }} {{ currencyIso }}</td>
                            <td>
                                <a href="{{ app.request.requestUri ~ '?' ~ {'action':'delete_frequency', 'd': subscription.duration, 'id': subscription.product_id, 'type': subscription.product_type}|url_encode() }}"
                                   class="{{ btnDanger }}">
                                    <em class="fa fa-remove"></em>
                                </a>
                            </td>
                        </tr>
                    {% endfor %}
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
{% endautoescape %}
