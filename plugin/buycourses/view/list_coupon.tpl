<br />
<br />
{{ form }}

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
        <tr class="sale-columns">
            <th>{{ 'CouponCode'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th>{{ 'CouponDiscountType'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th>{{ 'CouponDiscount'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th>{{ 'CouponDateStart'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th>{{ 'CouponDateEnd'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th>{{ 'CouponDelivered'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th width="10%">{{ 'Options'|get_lang }}</th>
        </tr>
        </thead>
        <tbody>
        {% for coupon in coupon_list %}
            <tr class="sale-row">
                <td class="text-center">{{ coupon.code }}</td>
                <td class="text-center">{{ coupon.discount_type }}</td>
                <td class="text-center">{{ coupon.discount_value }}</td>
                <td class="text-center">{{ coupon.valid_start | api_get_local_time }}</td>
                <td class="text-center">{{ coupon.valid_end | api_get_local_time }}</td>
                <td class="text-center">{{ coupon.delivered }}</td>
                <td class="text-center">
                    {% if coupon.active == coupon_status_active %}
                        <div class="btn-group btn-group-xs" role="group" aria-label="...">
                            <a title="{{ 'CouponDisable'|get_plugin_lang('BuyCoursesPlugin') }}" href="{{ _p.web_self ~ '?' ~ {'coupon': coupon.id, 'action': 'deactivate'}|url_encode() }}"
                               class="btn btn-default">
                                <img src="{{ 'user_subscribe_session.png' | icon(22) }}" width="22" height="22 alt="{{ 'CouponDisable'|get_plugin_lang('BuyCoursesPlugin') }}">
                            </a>
                        </div>
                    {% endif %}
                    {% if coupon.active == coupon_status_disable %}
                        <div class="btn-group btn-group-xs" role="group" aria-label="...">
                            <a title="{{ 'CouponEnable'|get_plugin_lang('BuyCoursesPlugin') }}" href="{{ _p.web_self ~ '?' ~ {'coupon': coupon.id, 'action': 'activate'}|url_encode() }}"
                               class="btn btn-default">
                                <img src="{{ 'user_subscribe_session.png' | icon(22) }}" width="22" height="22 alt="{{ 'CouponEnable'|get_plugin_lang('BuyCoursesPlugin') }}">
                            </a>
                        </div>
                    {% endif %}                    
                </td>
            </tr>
        {% endfor %}
        </tbody>
    </table>
</div>