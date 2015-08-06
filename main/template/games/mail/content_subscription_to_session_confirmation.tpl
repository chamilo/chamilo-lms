{{ 'Dear'|get_lang }} {{ complete_name }},
{{ 'YouAreRegisterToSessionX'|get_lang|format(session_name) }}

{{ session_coach }}

{{ 'XTeam'|get_lang|format(_s.site_name) }}

{{ _admin.telephone ? 'T. ' ~ _admin.telephone }}
