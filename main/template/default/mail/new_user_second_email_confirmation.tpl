<p>{{ 'Dear'|get_lang }} {{ complete_name }},</p>
<p>{{ 'YouAreReg'|get_lang }} {{ _s.site_name }} {{ 'WithTheFollowingSettings'|get_lang }}</p>
    {{ 'Pass'|get_lang }} : {{ original_password }}</p>
<p>{{ 'YouReceivedAnEmailWithTheUsername'|get_lang }}</p>
<p>{{ 'ThanksForRegisteringToSite'|get_lang|format(_s.site_name) }}</p>
<p>{{ 'Address'|get_lang }} {{ _s.site_name }} {{ 'Is'|get_lang }} : {{ mailWebPath }}</p>
<p>{{ 'Problem'|get_lang }}</p>
<p>{{ 'SignatureFormula'|get_lang }}</p>
<p>{{ _admin.name }}, {{ _admin.surname }}<br>
    {{ 'Manager'|get_lang }} {{ _s.site_name }}<br>
    {{ _admin.telephone ? 'T. ' ~ _admin.telephone }}<br>
    {{ _admin.email ? 'Email'|get_lang ~ ': ' ~ _admin.email }}</p>
