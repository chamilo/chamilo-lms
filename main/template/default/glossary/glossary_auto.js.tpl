{% if add_ready %}
$(document).ready(function() {
    $(window).on("load", function () {
{% endif %}
    var my_text = $(".glossary-content").html();
    var ajaxRequestUrl = "{{ _p.web }}main/glossary/glossary_ajax_request.php?{{ _p.web_cid_query }}";
    var imageSource = "{{ _p.web }}main/inc/lib/javascript/indicator.gif";
    var indicatorImage ='<img src="' + imageSource + '" />';
    var termsArray = new Array(); // needed in this function for a wide scope
    var termsArrayCopy = new Array();

    $.ajax({
        contentType: "application/x-www-form-urlencoded",
        beforeSend: function(content_object) {},
        type: "POST",
        url: ajaxRequestUrl,
        data: "glossary_data=true",
        success: function(datas) {
            if (datas.length==0) {
                return false;
            }
            // glossary terms
            data_terms=datas.split("[|.|_|.|-|.|]");
            var complex_array = new Array();
            var cp_complex_array = new Array();
            for(i=0;i<data_terms.length;i++) {
                specific_terms=data_terms[i].split("__|__|");
                var real_term = specific_terms[1]; // glossary term
                var real_code = specific_terms[0]; // glossary id
                termsArray[real_code] = real_term;
                termsArrayCopy[real_code] = real_term;
            }

            termsArray.reverse();

            for (var my_index in termsArray) {
                n = termsArray[my_index];
                if (n == null) {
                    n = '';
                } else {
                    for (var cp_my_index in termsArrayCopy) {
                        cp_data = termsArrayCopy[cp_my_index];
                        if (cp_data == null) {
                            cp_data = '';
                        } else {
                            if (cp_data == n) {
                                my_index = cp_my_index;
                            }
                        }
                    }
                    $('body').removeHighlight().highlight(n, my_index);
                }
            }

            //mouse on click
            $("body").on("click", ".glossary-ajax", function(e) {
                random_id = Math.round(Math.random()*100);
                div_show_id = "div_show_id";
                div_content_id = "div_content_id";

                $(this).append("<div id="+div_show_id+"><div id="+div_content_id+">&nbsp;</div></div>");
                var $target = $(this);

                //$("#"+div_show_id).dialog("destroy");
                $("#"+div_show_id).dialog({
                    autoOpen: false,
                    width: 600,
                    height: 200,
                    position:  { my: 'left top', at: 'right top', of: $target },
                    close: function(){
                        $("div#"+div_show_id).remove();
                        $("div#"+div_content_id).remove();
                    }
                });

                notebook_id = $(this).attr("name");
                data_notebook = notebook_id.split("link");
                my_glossary_id = data_notebook[1];

                $.ajax({
                    contentType: "application/x-www-form-urlencoded",
                    beforeSend: function(content_object) {
                        $("div#"+div_content_id).html(indicatorImage);
                    },
                    type: "POST",
                    url: ajaxRequestUrl,
                    data: "glossary_id="+my_glossary_id,
                    success: function(datas) {
                        $("div#"+div_content_id).html(datas);

                        // Make sure the dialog opens also with links to other
                        // glossary terms
                        for (var my_index in termsArray) {
                            n = termsArray[my_index];
                            if (n == null) {
                                n = '';
                            } else {
                                for (var cp_my_index in termsArrayCopy) {
                                    cp_data = termsArrayCopy[cp_my_index];
                                    if (cp_data == null) {
                                        cp_data = '';
                                    } else {
                                        if (cp_data == n) {
                                            my_index = cp_my_index;
                                        }
                                    }
                                }
                                $("div#"+div_content_id).highlight(n, my_index);
                            }
                        }
                        $("#"+div_show_id).dialog("open");
                    }
                });
            });
        }
    });
{% if add_ready %}
    });
});

{% endif %}
