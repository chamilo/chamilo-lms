{% extends 'layout/layout_1_col.tpl'|get_template %}

{% block content %}
    <div class="row">
        <div class="col-md-12">
            {% if social_search %}
            <div class="search-user">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        {{ 'SearchUsers' | get_lang}}
                    </div>
                    <div class="panel-body">
                        {{ social_search }}
                    </div>
                </div>
            </div>
            {% endif %}
            <div id="whoisonline">
                <div id="user-list" class="row">
                    {{ whoisonline }}
                </div>
                <div class="col-md-12">
                    <a class="btn btn-large btn-default" id="link_load_more_items" data_link="2" >{{ 'More' | get_lang }}</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $("#link_load_more_items").click(function() {
                page = $("#link_load_more_items").attr("data_link");
                $.ajax({
                    beforeSend: function() {
                        $("#link_load_more_items").html("{{ 'Loading' | get_lang|escape('js') }} <em class='fa fa-spinner fa-pulse fa-fw'></em>");
                    },
                    type: "GET",
                    url: "main/inc/ajax/online.ajax.php?a=load_online_user",
                    data: "online_page_nr=" + page,
                    success: function(data) {
                        if (data != "end") {
                            $("#link_load_more_items").attr("data_link", parseInt(page) + 1);
                            $("#user-list").append(data);
                            $("#link_load_more_items").html("{{ 'More' | get_lang|escape('js')}}");
                        } else {
                            $("#link_load_more_items").remove();
                        }
                    }
                });
            });
        });
    </script>
{% endblock %}

