<p>{{ 'Dear'|get_lang }} {{ complete_name }},</p>
<p>{{ 'YouAreReg'|get_lang }} {{ _s.site_name }} {{ 'WithTheFollowingSettings'|get_lang }}</p>
<p>{{ 'Username'|get_lang }} : {{ login_name }}<br>
{% if original_password != '' %}
{{ 'Pass'|get_lang }} : {{ original_password }}</p>
{% endif %}
<p>{{ 'Address'|get_lang }} {{ _s.site_name }} {{ 'Is'|get_lang }} : {{ portal_url }}</p>
<p>{{ 'Problem'|get_lang }}</p>
<p>{{ 'SignatureFormula'|get_lang }}</p>
<p>{{ _admin.name }}, {{ _admin.surname }}<br>
    {{ 'Manager'|get_lang }} {{ _s.site_name }}<br>
    {{ _admin.telephone ? 'T. ' ~ _admin.telephone }}<br>
    {{ _admin.email ? 'Email'|get_lang ~ ': ' ~ _admin.email }}</p>