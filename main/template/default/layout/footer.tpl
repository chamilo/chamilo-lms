<footer> <!-- start of #footer section -->
    <div class="container">
        <div class="row">
            <div id="footer_left" class="span4">
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

            <div id="footer_center" class="span4">
                {#   Plugins for footer section  #}
                {% if plugin_footer_center is not null %}
                    <div id="plugin_footer_center">
                        {{ plugin_footer_center }}
                    </div>
                {% endif %}
                 &nbsp;
            </div>

            <div id="footer_right" class="span4">
                {% if administrator_name is not null %}
                    <div id="admin_name">
                        {{ administrator_name }}
                    </div>
                {% endif %}

                <div id="software_name">
                    {{ "Platform"|get_lang }} <a href="{{_p.web}}" target="_blank">{{_s.software_name}} {{_s.system_version}}</a>
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

{% raw %}
<script>

$("form").on("click", ' .advanced_parameters', function() {
    var id = $(this).attr('id') + '_options';
    var button = $(this);
    $("#"+id).toggle(function() {
        button.toggleClass('active');
    });
});


/* Makes row highlighting possible */
$(document).ready( function() {
    /**
     * Advanced options
     * Usage
     * <a id="link" href="url">Advanced</a>
     * <div id="link_options">
     *     hidden content :)
     * </div>
     * */
    $(".advanced_options").on("click", function(event) {
        event.preventDefault();
        var id = $(this).attr('id') + '_options';
        var button = $(this);
        $("#"+id).toggle(function() {
            button.toggleClass('active');
        });
    });

    /**
     * <a class="advanced_options_open" href="http://" rel="div_id">Open</a>
     * <a class="advanced_options_close" href="http://" rel="div_id">Close</a>
     * <div id="div_id">Div content</div>
     * */
    $(".advanced_options_open").on("click", function(event) {
        event.preventDefault();
        var id = $(this).attr('rel');
        $("#"+id).show();
    });

    $(".advanced_options_close").on("click", function(event) {
        event.preventDefault();
        var id = $(this).attr('rel');
        $("#"+id).hide();
    });

    // Chosen select
    $(".chzn-select").chosen({
        disable_search_threshold: 10
    });

    $(".jp-jplayer audio").addClass('skip');

    // Mediaelement
    jQuery('video:not(.skip), audio:not(.skip)').mediaelementplayer(/* Options */);

    // Table highlight.
    $("form .data_table input:checkbox").click(function() {
        if ($(this).is(":checked")) {
            $(this).parentsUntil("tr").parent().addClass("row_selected");

        } else {
            $(this).parentsUntil("tr").parent().removeClass("row_selected");
        }
    });

    /* For non HTML5 browsers */
    if ($("#formLogin".length > 1)) {
        $("input[name=login]").focus();
    }

    /* For IOS users */
    $('.autocapitalize_off').attr('autocapitalize', 'off');

    //Tool tip (in exercises)
    var tip_options = {
        placement : 'right'
    }
    $('.boot-tooltip').tooltip(tip_options);
});
{% endraw %}

</script>

{{ execution_stats }}
