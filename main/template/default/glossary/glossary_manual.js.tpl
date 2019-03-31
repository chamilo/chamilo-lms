var ajaxRequestUrl = "{{ _p.web }}main/glossary/glossary_ajax_request.php?{{ _p.web_cid_query }}";
var imageSource = "{{ _p.web_main }}" + "inc/lib/javascript/indicator.gif";
var indicatorImage ='<img src="' + imageSource + '" />';

{% if add_ready %}
$(document).ready(function() {
{% endif %}
    $("body").on("click", ".glossary", function() {
        is_glossary_name = $(this).html();
        random_id = Math.round(Math.random()*100);
        div_show_id = "div_show_id";
        div_content_id = "div_content_id";

        var $target = $(this);
        var title = $target.text();

        $(this).append("<div id="+div_show_id+" title="+title+" ><div id="+div_content_id+">&nbsp;</div></div>");


        //$("#"+div_show_id).dialog("destroy");
        $("#"+div_show_id).dialog({
            autoOpen: false,
            width: 500,
            height: 'auto',
            position:  { my: 'left top', at: 'right top', of: $target },
            close: function(){
                $("div#"+div_show_id).remove();
                $("div#"+div_content_id).remove();
            }
        });

        $.ajax({
            contentType: "application/x-www-form-urlencoded",
            beforeSend: function(result) {
                $("div#"+div_content_id).html(indicatorImage);
            },
            type: "POST",
            url: ajaxRequestUrl,
            data: "glossary_name="+is_glossary_name,
            success: function(data) {
                $("div#"+div_content_id).html(data);
                $("#"+div_show_id).dialog("open");
            }
        });
    });

{% if add_ready %}
});
{% endif %}
