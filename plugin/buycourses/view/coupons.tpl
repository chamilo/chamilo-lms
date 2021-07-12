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
                <div class="btn-group btn-group-xs" role="group" aria-label="...">
                    <a title="{{ 'ConfigureCoupon'|get_plugin_lang('BuyCoursesPlugin') }}" href="{{ _p.web_plugin ~ 'buycourses/src/configure_coupon.php?' ~ {'id': coupon.id}|url_encode() }}"
                        class="btn btn-default">
                        <em class="fa fa-wrench fa-fw"></em>
                    </a>
                </div>
                </td>
            </tr>
        {% endfor %}
        </tbody>
    </table>
</div>