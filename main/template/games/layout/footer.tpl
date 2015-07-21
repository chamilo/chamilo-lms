<footer id="footer">
    <div class="subfooter">
        <div class="container">
            <div class="row">
                <div class="col-xs-6 col-sm-3 col-md-3 col-lg-3">
                    <ul class="links-footer">
                        <li><a href="#">¿Quienes somos?</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Cursos</a></li>
                    </ul>

                </div>
                <div class="col-xs-6 col-sm-3 col-md-3 col-lg-3">
                    <ul class="links-footer">
                        <li><a href="#">Politicas de privadidad</a></li>
                        <li><a href="#">Terminos y condiciones</a></li>
                        <li><a href="#">Contacto</a></li>
                    </ul>
                </div>
                <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                    <div class="red-social">
                        <h3 class="social-footer">¡Siguienos en redes sociales!</h3>
                        <a class="media" href="#"><img src="{{ _p.web_css_theme }}images/facebook.png"></a>
                        <a class="media" href="#"><img src="{{ _p.web_css_theme }}images/twitter.png"></a>
                        <a class="media" href="#"><img src="{{ _p.web_css_theme }}images/youtube.png"></a>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                    <div class="direction">
                        <p>Rio de la plata 167 Of.503<br>
                            San Isidro - Lima Perú
                            (511) 221 - 2721<br>
                            contacto@tademi.com
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
            disable_search_threshold: 10
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
</script>
<script type="text/javascript" src="{{ _p.web_css_theme }}js/flip.js"></script>
<script type="text/javascript">
    $(document).ready(function(){
        $(".card").flip({
            axis: "y", // y or x
            trigger: "hover", // click or hover

        });
    });
</script>

{{ execution_stats }}
