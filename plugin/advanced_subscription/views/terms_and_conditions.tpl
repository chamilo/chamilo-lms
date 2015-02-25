{% if termsRejected == 1 %}
<div class="error-message">
    {{ "YouMustAcceptTermsAndConditions"  | get_plugin_lang("AdvancedSubscriptionPlugin") | format(session.name) }}
</div>
{% endif %}
<div class="legal-terms">
    {{ termsContent }}
</div>
<div>
    {{ termsFiles }}
</div>
<div>
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