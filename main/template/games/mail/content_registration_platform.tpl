{{ 'Dear'|get_lang }} {{ complete_name }},
{{ 'ThanksForRegisitering'|get_lang }}
{{ 'AsYouKnowEffectiveLearning'|get_lang }}
{{ 'YouCanStartSubscribingToCoursesEnteringHereXUrl'|get_lang|format(_p.web_main ~ 'auth/courses.php') }}
{{ 'Enjoy'|get_lang }}
{{ 'XTeam'|get_lang|format(_s.site_name) }}

{{ _admin.telephone ? 'T. ' ~ _admin.telephone }}
