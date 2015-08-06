{{ 'Dear'|get_lang }} {{ complete_name }},
</br>
{{ 'YouAreReg'|get_lang }} {{ _s.site_name }} {{ 'WithTheFollowingSettings'|get_lang }}</br>
{{ 'Username'|get_lang }} : {{ login_name }}
{{ 'Pass'|get_lang }} : {{ original_password }}
</br></br>
{{ 'Address'|get_lang }} {{ _s.site_name }} {{ 'Is'|get_lang }} : {{ mailWebPath }}</br>
{{ 'Problem'|get_lang }}</br>
{{ 'SignatureFormula'|get_lang }}</br>
{{ _admin.name }}, {{ _admin.surname }}
{{ 'Manager'|get_lang }} {{ _s.site_name }}
{{ _admin.telephone ? 'T. ' ~ _admin.telephone ~ '<br>' }}
{{ _admin.email ? 'Email'|get_lang ~ ': ' ~ _admin.email }}
