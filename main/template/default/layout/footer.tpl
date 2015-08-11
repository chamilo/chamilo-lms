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

<script>
    $("form").on("click", ' .advanced_parameters', function() {
        /*var id = $(this).attr('id') + '_options';
        console.log(id);
        $("#"+id).toggleClass('active');
        var button = $(this);
        $("#"+id).toggle(function() {
            $("#"+id).toggleClass('active');
        });*/
    });

    /* Makes row highlighting possible */
    $(document).ready( function() {
        // Date time settings.
        moment.locale('{{ locale }}');
        $.datepicker.setDefaults($.datepicker.regional["{{ locale }}"]);
        $.datepicker.regional["local"] = $.datepicker.regional["{{ locale }}"];

        // Bootstrap tabs.
        $('.tab-wrapper a').click(function (e) {
            e.preventDefault();
            $(this).tab('show');

            //$('#tabs a:first').tab('show') // Select first tab
        });

        // Fixes bug when loading links inside a tab.
        $('.tab-wrapper .tab-pane a').unbind();

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
            button.toggleClass('active');
            $("#"+id).toggle();
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
            disable_search_threshold: 10,
            no_results_text: '{{ 'SearchNoResultsFound' | get_lang }}',
            placeholder_text_multiple: '{{ 'SelectSomeOptions' | get_lang }}',
            placeholder_text_single: '{{ 'SelectAnOption' | get_lang }}'
        });

        // Adv multi-select search input.
        $('.select_class_filter').on('focus', function() {
            var inputId = $(this).attr('id');
            inputId = inputId.replace('-filter', '');
            $("#"+ inputId).filterByText($("#"+inputId+"-filter"));
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

        // Tool tip (in exercises)
        var tip_options = {
            placement : 'right'
        };
        $('.boot-tooltip').tooltip(tip_options);
    });

    // @todo move in a chamilo.js js lib.

    jQuery.fn.filterByText = function(textbox) {
        return this.each(function() {
            var select = this;
            var options = [];
            $(select).find('option').each(function() {
                options.push({value: $(this).val(), text: $(this).text()});
            });
            $(select).data('options', options);

            $(textbox).bind('change keyup', function() {
                var options = $(select).empty().data('options');
                var search = $.trim($(this).val());
                var regex = new RegExp(search,"gi");

                $.each(options, function(i) {
                    var option = options[i];
                    if(option.text.match(regex) !== null) {
                        $(select).append(
                            $('<option>').text(option.text).val(option.value)
                        );
                    }
                });
            });
        });
    };

    /**
     * Generic function to replace the deprecated jQuery toggle function
     * @param inId          : id of block to hide / unhide
     * @param inIdTxt       : id of the button
     * @param inTxtHide     : text one of the button
     * @param inTxtUnhide   : text two of the button
     * @todo : allow to detect if text is from a button or from a <a>
     */
    function hideUnhide(inId, inIdTxt, inTxtHide, inTxtUnhide)
    {
        if ($('#'+inId).css("display") == "none") {
            $('#'+inId).show(400);
            $('#'+inIdTxt).attr("value", inTxtUnhide);
        } else {
            $('#'+inId).hide(400);
            $('#'+inIdTxt).attr("value", inTxtHide);
        }
    }
</script>

{{ execution_stats }}
