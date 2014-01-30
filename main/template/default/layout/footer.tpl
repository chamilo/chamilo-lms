<footer{% block footer_open_attributes %}{% endblock footer_open_attributes %}>
    <!-- start of #footer section -->
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

{{ footer_extra_content }}

{% raw %}
<script>
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

// Functions used in main/admin.

var textarea = "";
var max_char = 255;

function maxCharForTextarea(obj) {
    num_characters = obj.value.length;
    if (num_characters > max_char){
        obj.value = textarea;
    } else {
        textarea = obj.value;
    }
}

function moveItem(origin , destination) {
    for(var i = 0 ; i<origin.options.length ; i++) {
        if(origin.options[i].selected) {
            destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
            origin.options[i]=null;
            i = i-1;
        }
    }
    destination.selectedIndex = -1;
    sortOptions(destination.options);
}

function sortOptions(options) {
    newOptions = new Array();
    for (i = 0 ; i<options.length ; i++)
        newOptions[i] = options[i];

    newOptions = newOptions.sort(mysort);
    options.length = 0;
    for(i = 0 ; i < newOptions.length ; i++)
        options[i] = newOptions[i];
}

function mysort(a, b) {
    if(a.text.toLowerCase() > b.text.toLowerCase()){
        return 1;
    }
    if(a.text.toLowerCase() < b.text.toLowerCase()){
        return -1;
    }
    return 0;
}

$("form").on("click", ' .advanced_parameters', function() {
    var id = $(this).attr('id') + '_options';
    var button = $(this);
    $("#"+id).toggle(function() {
        button.toggleClass('active');
    });
});

/** Makes row highlighting possible */
$(document).ready( function() {
    /**
     * Advanced options
     * Usage
     * <a id="link" href="url">Advanced</a>
     * <div id="link_options" style="display:none">
     *     hidden content :)
     * </div>
     * */

     $(".advanced_options").on("click", function() {
        var id = $(this).attr('id') + '_options';
        var button = $(this);
        $("#"+id).toggle(function() {
            button.toggleClass('active');
        });
    });

    $(function() {
        $('a').tooltip({
            placement: 'right',
            show: 500,
            hide: 500
        });
    });


    $('.advanced_parameters').addClass('btn-default');
    //$('.btn').addClass('btn-default');

    // Chosen select.
    $(".chzn-select").chosen({
        disable_search_threshold: 10
    });

    // Adv multiselect text inputs.
    $('.select_class_filter').each(function(){
        var inputId = $(this).attr('id');

        inputId = inputId.replace('f-', '');
        inputId = inputId.replace('-filter', '');

        $("#"+ inputId+"-f").filterByText($("#f-"+inputId+"-filter"));
        $("#"+ inputId+"-t").filterByText($("#t-"+inputId+"-filter"));
    });

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
</script>
{% endraw %}
{{ execution_stats }}
