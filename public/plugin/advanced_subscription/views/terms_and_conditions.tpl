{# start copy from head.tpl #}
<meta charset="{{ system_charset }}" />
<link href="https://chamilo.org/chamilo-lms/" rel="help" />
<link href="https://chamilo.org/the-association/" rel="author" />
<link href="https://chamilo.org/the-association/" rel="copyright" />
{{ prefetch }}
{{ favico }}
{{ browser_specific_head }}
<link rel="apple-touch-icon" href="{{ _p.web }}apple-touch-icon.png" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="Generator" content="{{ _s.software_name }} {{ _s.system_version|slice(0,1) }}" />
{#  Use the latest engine in ie8/ie9 or use google chrome engine if available  #}
{#  Improve usability in portal devices #}
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ title_string }}</title>
{{ css_static_file_to_string }}
{{ js_file_to_string }}
{{ css_custom_file_to_string }}
{{ css_style_print }}
{# end copy from head.tpl #}
<h2 class="legal-terms-title legal-terms-popup">
{{ "TermsAndConditions" | get_lang }}
</h2>
{% if termsRejected == 1 %}
<div class="error-message legal-terms-popup">
    {{ "YouMustAcceptTermsAndConditions"  | get_plugin_lang("AdvancedSubscriptionPlugin") | format(session.name) }}
</div>
{% endif %}

{% if errorMessages is defined %}
    <div class="alert alert-warning legal-terms-popup">
        <ul>
            {% for errorMessage in errorMessages %}
                <li>{{ errorMessage }}</li>
            {% endfor %}
        </ul>
    </div>
{% endif %}

<div class="legal-terms legal-terms-popup">
    {{ termsContent }}
</div>
<div class="legal-terms-files legal-terms-popup">
    {{ termsFiles }}
</div>
<div class="legal-terms-buttons legal-terms-popup">
    <a
        class="btn btn-success btn-advanced-subscription btn-accept"
        href="{{ acceptTermsUrl }}"
    >
        {{ "AcceptInfinitive" | get_plugin_lang("AdvancedSubscriptionPlugin") }}
    </a>
    <a
        class="btn btn-danger btn-advanced-subscription btn-reject"
        href="{{ rejectTermsUrl }}"
    >
        {{ "RejectInfinitive" | get_plugin_lang("AdvancedSubscriptionPlugin") }}
    </a>
</div>

<link href="{{ _p.web_plugin }}advanced_subscription/views/css/style.css" rel="stylesheet" type="text/css">