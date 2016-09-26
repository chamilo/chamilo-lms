{% block header %}
{% include template ~ "/layout/main_header.tpl" %}
{% endblock %}

{% block body %}
	{% if show_sniff == 1 %}
	 	{% include template ~ "/layout/sniff.tpl" %}
	{% endif %}
{% endblock %}
<div class="sub-footer">
    <div class="container">
        <div class="row">
                <div class="col-md-12">
                    <div class="partners">
                        <a href="#"><img src="{{ _p.web_css_theme }}images/rbs_logo_rgb.png"/></a>
                        <a href="#"><img src="{{ _p.web_css_theme }}images/logo_cavilam.png"/></a>
                    </div>
                </div>
        </div>
    </div>
</div>
{% block footer %}
   
    {% if show_footer == true %}
        </div> <!-- end of #col" -->
        </div> <!-- end of #container" -->
    {% endif %}
    {% include template ~ "/layout/main_footer.tpl" %}
{% endblock %}
