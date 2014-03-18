{# Load the template basis from the default template #}
{% extends app.template_style ~ "/../default/layout/head.tpl" %}

{% block header_end %}
<link rel="stylesheet" href="http://code.jquery.com/mobile/1.3.1/jquery.mobile-1.3.1.min.css" />
<script src="http://code.jquery.com/mobile/1.3.1/jquery.mobile-1.3.1.min.js"></script>
{% endblock header_end %}

