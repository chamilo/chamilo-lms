<footer> <!-- start of #footer section -->
    <div class="container">
        <div class="row">
            <div id="footer_left" class="col-md-4">
                {% if session_teachers is not null %}
                    <div id="session_teachers">
                        {{ session_teachers }}
                    </div>
                {% endif %}

                {% if teachers is not null %}
                    <div id="teachers">
                        {{ teachers }}
                    </div>
                {% endif %}

                {#  Plugins for footer section #}
                {% if plugin_footer_left is not null %}
                    <div id="plugin_footer_left">
                        {{ plugin_footer_left }}
                    </div>
                {% endif %}
                &nbsp;
            </div>

            <div id="footer_center" class="col-md-4">
                {#   Plugins for footer section  #}
                {% if plugin_footer_center is not null %}
                    <div id="plugin_footer_center">
                        {{ plugin_footer_center }}
                    </div>
                {% endif %}
                &nbsp;
            </div>

            <div id="footer_right" class="col-md-4">
                {% if administrator_name is not null %}
                    <div id="admin_name">
                        {{ administrator_name }}
                    </div>
                {% endif %}

                <div id="software_name">
	                <a href="{{_p.web}}" target="_blank">{{ "PoweredByX" |get_lang | format(_s.software_name) }}</a>
                    &copy; {{ "now"|date("Y") }}
                </div>
                {#   Plugins for footer section  #}
                {% if plugin_footer_right is not null %}
                    <div id="plugin_footer_right">
                        {{ plugin_footer_right }}
                    </div>
                {% endif %}
                &nbsp;
            </div><!-- end of #footer_right -->
        </div><!-- end of #row -->
    </div><!-- end of #container -->
</footer>

{# Extra footer configured in admin section, only shown to non-admins #}
{{ footer_extra_content }}

<div class="modal fade" id="expand-image-modal" tabindex="-1" role="dialog" aria-labelledby="expand-image-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ "Close" | get_lang }}"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="expand-image-modal-title">&nbsp;</h4>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>

{% include template ~ '/layout/footer.js.tpl' %}

{{ execution_stats }}
