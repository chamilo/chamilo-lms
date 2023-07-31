<p>{{ 'Dear'|get_lang }} {{ user.complete_name }},</p>
<p>{{ 'bc_subject'|get_plugin_lang('BuyCoursesPlugin') }}</p>
<div>
    <dl>
        <dt>{{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ sale.date|api_convert_and_format_date(constant('DATE_TIME_FORMAT_LONG_24H')) }}</dd>
        <dt>{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ sale.reference }}</dd>
        <dt>{{ 'Product'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ sale.product }}</dd>
        <dt>{{ 'SalePrice'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ sale.currency ~ ' ' ~ sale.price }}</dd>
    </dl>
</div>

<p>{{ 'SignatureFormula'|get_lang }}</p>
