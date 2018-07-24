<footer id="footer-section" class="sticky-footer bgfooter">
    <div class="container">
        <div class="pre-footer">
            {% if plugin_pre_footer is not null %}
            <div id="plugin_pre_footer">
                {{ plugin_pre_footer }}
            </div>
            {% endif %}
        </div>
        <div class="sub-footer">
            <div class="row">
                <div class="col-md-4">
                    {% if session_teachers is not null %}
                    <div class="session-teachers">
                        {{ session_teachers }}
                    </div>
                    {% endif %}
                    {% if teachers is not null %}
                    <div class="teachers">
                        {{ teachers }}
                    </div>
                    {% endif %}
                    {% if plugin_footer_left is not null %}
                    <div id="plugin_footer_left">
                        {{ plugin_footer_left }}
                    </div>
                    {% endif %}
                </div>
                <div class="col-md-4">
                    {% if plugin_footer_center is not null %}
                    <div id="plugin_footer_center">
                        {{ plugin_footer_center }}
                    </div>
                    {% endif %}
                </div>
                <div class="col-md-4 text-right">
                    {% if administrator_name is not null %}
                    <div class="administrator-name">
                        {{ administrator_name }}
                    </div>
                    {% endif %}

                    {% if _s.software_name is not empty %}
                        <div class="software-name">
                            <a href="{{_p.web}}" target="_blank">
                                {{ "PoweredByX" |get_lang | format(_s.software_name) }}
                            </a>&copy; {{ "now"|date("Y") }}
                        </div>
                    {% endif %}

                    {% if plugin_footer_right is not null %}
                    <div id="plugin_footer_right">
                        {{ plugin_footer_right }}
                    </div>
                    {% endif %}
                </div>
            </div>
        </div>
        <div class="extra-footer">
            {{ footer_extra_content }}
        </div>
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