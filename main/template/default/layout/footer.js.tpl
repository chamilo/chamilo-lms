<script>
    /* Makes row highlighting possible */
    $(document).ready( function() {
         $("[data-toggle=popover]").each(function(i, obj) {

            $(this).popover({
              html: true,
              content: function() {
                var id = $(this).attr('id')
                return $('#popover-content-' + id).html();
              }
            });
            
            
            

            });
        
        // Date time settings.
        moment.locale('{{ locale }}');
        $.datepicker.setDefaults($.datepicker.regional["{{ locale }}"]);
        $.datepicker.regional["local"] = $.datepicker.regional["{{ locale }}"];

        // Fix old calls of "inc/lib/mediaplayer/player.swf" and convert to <audio> tag, then rendered by media element js
        // see BT#13405
        $('embed').each( function () {
            var flashVars = $(this).attr('flashvars');
            if (flashVars && flashVars.indexOf("file") == -1) {
                var audioId = Math.floor( Math.random()*99999 );
                flashVars = flashVars.replace('&autostart=false', '');
                flashVars = flashVars.replace('&autostart=true', '');
                var audioDiv = '<audio id="'+audioId+'" controls="controls" style="width:400px;" width:"400px;" src="'+flashVars+'" ><source src="'+flashVars+'" type="audio/mp3"  ></source></audio>';
                $(this).hide();
                $(this).after(audioDiv);
            }
        });

        // Chosen select
        $(".chzn-select").chosen({
            disable_search_threshold: 10,
            no_results_text: '{{ 'SearchNoResultsFound' | get_lang | escape('js') }}',
            placeholder_text_multiple: '{{ 'SelectSomeOptions' | get_lang | escape('js') }}',
            placeholder_text_single: '{{ 'SelectAnOption' | get_lang | escape('js') }}',
            width: "100%"
        });

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
        $(".advanced_options").on("click", function (event) {
            event.preventDefault();
            var id = $(this).attr('id') + '_options';
            var button = $(this);
            $("#" + id).toggle();
        });

        /**
         * <a class="advanced_options_open" href="http://" rel="div_id">Open</a>
         * <a class="advanced_options_close" href="http://" rel="div_id">Close</a>
         * <div id="div_id">Div content</div>
         * */
        $(".advanced_options_open").on("click", function (event) {
            event.preventDefault();
            var id = $(this).attr('rel');
            $("#" + id).show();
        });

        $(".advanced_options_close").on("click", function (event) {
            event.preventDefault();
            var id = $(this).attr('rel');
            $("#" + id).hide();
        });

        // Adv multi-select search input.
        $('.select_class_filter').each( function () {
            var inputId = $(this).attr('id');
            inputId = inputId.replace('-filter', '');
            $("#" + inputId).filterByText($("#" + inputId + "-filter"));
        });

        $(".jp-jplayer audio").addClass('skip');

        // Mediaelement
        if ( {{ show_media_element }} == 1) {
            jQuery('video:not(.skip), audio:not(.skip)').mediaelementplayer(/* Options */);
        }

        // Table highlight.
        $("form .data_table input:checkbox").click(function () {
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

        // Tool tip (in exercises)
        var tip_options = {
            placement: 'right'
        };
        $('.boot-tooltip').tooltip(tip_options);
        var more = '{{ 'SeeMore' | get_lang | escape('js') }}';
        var close = '{{ 'Close' | get_lang | escape('js') }}';
        $('.list-teachers').readmore({
            speed: 75,
            moreLink: '<a href="#">' + more + '</a>',
            lessLink: '<a href="#">' + close + '</a>',
            collapsedHeight: 35,
            blockCSS: 'display: block; width: 100%;'
        });
    });
</script>
