<footer class="sticky-footer"> <!-- start of #footer section -->
    <div class="pre-footer">
        {% if plugin_pre_footer is not null %}
            <div id="plugin_pre_footer" class="text-center">
                {{ plugin_pre_footer }}
            </div>
        {% endif %}
    </div>
    <div class="sub-footer">
    <div class="container">
        <div class="row">
            <div id="footer_left" class="col-md-9">
                <div class="partners">
                    <a href="http://www.bosch-stiftung.de" target="_blank">
                        <img src="{{ _p.web_css_theme }}images/rbs_logo_rgb.png"/>
                    </a>
                    <a href="http://www.cavilam.com" target="_blank">
                        <img src="{{ _p.web_css_theme }}images/logo_cavilam.png"/>
                    </a>
                    <a href="http://www.dw.com" target="_blank">
                        <img src="{{ _p.web_css_theme }}images/logo-dw.png"/>
                    </a>
                    <a href="https://www.cdc.de/" target="_blank">
                        <img src="{{ _p.web_css_theme }}images/carlduisberg.png"/>
                    </a>
                </div>
            </div>
            <div id="footer_right" class="col-md-3">
                {% if session_teachers is not null %}
                    <div class="session-teachers">
                        {{ session_teachers }}
                    </div>
                {% endif %}
                {% if teachers is not null %}
                    <div id="teachers">
                        {{ teachers }}
                    </div>
                {% endif %}
                {% if administrator_name is not null %}
                    <div id="admin_name">
                        <a href="{{ "URLOFAJ" | get_lang }}">{{ "OFAJ" | get_lang }}</a>
                    </div>
                {% endif %}
                <div id="software_name">
	                <a href="{{_p.web}}" target="_blank">{{ "PoweredByX" |get_lang | format(_s.software_name) }}</a>
                    &copy; {{ "now"|date("Y") }}
                </div>
                <div id="mentions_legales">
                        <a href="{{_p.web}}{{ "MentionsLegalesLink" |get_lang }}" target="_blank">{{ "MentionsLegales" |get_lang }}</a> - <a href="{{_p.web}}{{ "CGULink" |get_lang }}" target="_blank">{{ "CGU" |get_lang }}</a>
                </div>
                &nbsp;
            </div><!-- end of #footer_right -->
        </div><!-- end of #row -->
        <div class="extra-footer">
            {{ footer_extra_content }}
        </div>
    </div><!-- end of #container -->
    </div>
</footer>

{# Global modal, load content by AJAX call to href attribute on anchor tag with 'ajax' class #}
<div class="modal fade" id="global-modal" tabindex="-1" role="dialog" aria-labelledby="global-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ "Close" | get_lang }}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="global-modal-title">&nbsp;</h4>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>

{# Expands an image modal #}
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

{# Delete modal #}
<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="confirm-delete-title" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel">{{ 'Delete' | get_lang }}</h4>
            </div>

            <div class="modal-body">
                <p class="debug-url"></p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ 'Cancel' | get_lang }}</button>
                <a id="delete_item" class="btn btn-danger btn-ok">{{ 'Delete' | get_lang }}</a>
            </div>
        </div>
    </div>
</div>

{{ execution_stats }}
