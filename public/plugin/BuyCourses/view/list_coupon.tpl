{% autoescape false %}
{% set formShell = 'rounded-2xl border border-gray-25 bg-white p-6 shadow-sm [&_form]:space-y-6 [&_.form-group]:mb-4 [&_label]:mb-2 [&_label]:block [&_label]:text-sm [&_label]:font-medium [&_label]:text-gray-90 [&_.form-control]:w-full [&_.form-control]:rounded-xl [&_.form-control]:border-gray-25 [&_.form-control]:bg-white [&_.form-control]:px-4 [&_.form-control]:py-2.5 [&_.form-control]:text-gray-90 [&_.form-control]:shadow-sm [&_.form-control]:focus:border-primary [&_.form-control]:focus:ring-primary/20 [&_select]:w-full [&_select]:rounded-xl [&_select]:border-gray-25 [&_select]:bg-white [&_select]:px-4 [&_select]:py-2.5 [&_select]:text-gray-90 [&_.btn]:inline-flex [&_.btn]:items-center [&_.btn]:justify-center [&_.btn]:gap-2 [&_.btn]:rounded-xl [&_.btn]:px-4 [&_.btn]:py-2.5 [&_.btn]:text-sm [&_.btn]:font-semibold [&_.btn]:shadow-sm [&_.btn]:transition [&_.btn-primary]:bg-primary [&_.btn-primary]:text-white [&_.btn-default]:border [&_.btn-default]:border-gray-25 [&_.btn-default]:bg-white [&_.btn-default]:text-gray-90 [&_.btn-success]:bg-success [&_.btn-success]:text-white [&_.btn-danger]:bg-danger [&_.btn-danger]:text-white [&_.btn-info]:bg-info [&_.btn-info]:text-white [&_.btn--primary]:bg-primary [&_.btn--primary]:text-white [&_.btn--plain]:border [&_.btn--plain]:border-gray-25 [&_.btn--plain]:bg-white [&_.btn--plain]:text-gray-90 [&_.btn--success]:bg-success [&_.btn--success]:text-white [&_.btn--danger]:bg-danger [&_.btn--danger]:text-white [&_.btn--info]:bg-info [&_.btn--info]:text-white' %}
{% set tableShell = 'overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm [&_table]:min-w-full [&_table]:divide-y [&_table]:divide-gray-25 [&_thead]:bg-gray-15 [&_th]:px-4 [&_th]:py-3 [&_th]:text-left [&_th]:text-xs [&_th]:font-semibold [&_th]:uppercase [&_th]:tracking-wide [&_th]:text-gray-50 [&_td]:px-4 [&_td]:py-4 [&_td]:align-middle [&_td]:text-sm [&_td]:text-gray-90 [&_tbody_tr]:border-t [&_tbody_tr]:border-gray-20 [&_tbody_tr:hover]:bg-gray-15/60' %}
{% set btnPrimary = 'inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2' %}
{% set btnSecondary = 'inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 shadow-sm transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2' %}
<div class="space-y-6">
    <div class="{{ formShell }}">
        {{ form }}
    </div>
    <div class="{{ tableShell }}">
        <table>
            <thead>
            <tr>
                <th>{{ 'CouponCode'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th>{{ 'CouponDiscountType'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th>{{ 'CouponDiscount'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th>{{ 'CouponDateStart'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th>{{ 'CouponDateEnd'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th>{{ 'CouponDelivered'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th>{{ 'Options'|get_lang }}</th>
            </tr>
            </thead>
            <tbody>
            {% for coupon in coupon_list %}
                <tr>
                    <td>{{ coupon.code }}</td>
                    <td>{{ coupon.discount_type }}</td>
                    <td>{{ coupon.discount_value }}</td>
                    <td>{{ coupon.valid_start | api_get_local_time }}</td>
                    <td>{{ coupon.valid_end | api_get_local_time }}</td>
                    <td>{{ coupon.delivered }}</td>
                    <td>
                        {% if coupon.active == coupon_status_active %}
                            <a title="{{ 'CouponDisable'|get_plugin_lang('BuyCoursesPlugin') }}" href="{{ app.request.requestUri ~ '?' ~ {'coupon': coupon.id, 'action': 'deactivate'}|url_encode() }}"
                               class="{{ btnSecondary }}">
                                <img src="{{ 'user_subscribe_session.png' | icon(22) }}" width="22" height="22" alt="{{ 'CouponDisable'|get_plugin_lang('BuyCoursesPlugin') }}">
                            </a>
                        {% endif %}
                        {% if coupon.active == coupon_status_disable %}
                            <a title="{{ 'CouponEnable'|get_plugin_lang('BuyCoursesPlugin') }}" href="{{ app.request.requestUri ~ '?' ~ {'coupon': coupon.id, 'action': 'activate'}|url_encode() }}"
                               class="{{ btnPrimary }}">
                                <img src="{{ 'user_subscribe_session.png' | icon(22) }}" width="22" height="22" alt="{{ 'CouponEnable'|get_plugin_lang('BuyCoursesPlugin') }}">
                            </a>
                        {% endif %}
                    </td>
                </tr>
            {% endfor %}
            </tbody>
        </table>
    </div>
</div>
{% endautoescape %}
