{% block header %}
{% include "jquery_mobile/layout/main_header.tpl" %}
{% endblock %}

{% block body %}
	{% if show_sniff == 1 %}
	 	{% include "jquery_mobile/layout/sniff.tpl" %}
	{% endif %}
{% endblock %}

{% block footer %}
    {#  Footer  #}
    {% if show_footer == true %}
        </div> <!-- end of #row" -->
        </div> <!-- end of #main" -->
        <div class="push"></div>
        </div> <!-- end of #wrapper section -->
    {% endif %}
{% include "jquery_mobile/layout/main_footer.tpl" %}
{{ xhprof }}
{% endblock %}
