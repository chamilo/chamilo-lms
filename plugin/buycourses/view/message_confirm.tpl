<div>
    <dl>
        <dt>{{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ sale.date|api_convert_and_format_date(constant('DATE_TIME_FORMAT_LONG_24H')) }}</dd>
        <dt>{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ sale.reference }}</dd>
        <dt>{{ 'UserName'|get_lang }}</dt>
        <dd>{{ user.complete_name }}</dd>
        <dt>{{ 'Product'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ sale.product }}</dd>
        <dt>{{ 'SalePrice'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ sale.currency ~ ' ' ~ sale.price }}</dd>
    </dl>
</div>
