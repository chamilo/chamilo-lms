<p>{{ 'Dear'|get_lang }} {{ complete_name }},</p>
{{ username ? '<p>' ~ username ~ '</p>' }}
<p>{{ 'YouAreRegisterToSessionX'|get_lang|format(session_name) }}</p>
<p>{{ 'Address'|get_lang }}  {{ _s.site_name }} {{ 'Is'|get_lang }} : {{ _p.web }}</p>
<p>{{ 'Problem'|get_lang }}</p>
<p>{{ 'SignatureFormula'|get_lang }}</p>
<p>{{ _admin.name }} {{ _admin.surname }}<br>
    {{ 'Manager'|get_lang }} {{ _s.site_name }}<br>
    {{ _admin.telephone ? 'T. ' ~ _admin.telephone }}<br>
    {{ _admin.email ? 'Email'|get_lang ~ ': ' ~ _admin.email }}</p>
{{ lostPassword ? '<p>' ~ lostPassword ~ '</p>' }}
