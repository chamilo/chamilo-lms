{% extends template ~ "/layout/layout_1_col.tpl" %}

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
                {{ whoisonline }}
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#whoisonline').on('click', 'a#link_load_more_items', function() {
                page = $("#link_load_more_items").attr("data_link");
                $.ajax({
                    beforeSend: function(objeto) {
                        $("#display_response_id").html('<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>');
                    },
                    type: "GET",
                    url: "main/inc/ajax/online.ajax.php?a=load_online_user",
                    data: "online_page_nr="+page,
                    success: function(data) {
                        $("#display_response_id").html('');
                        if (data != "end") {
                            $("#link_load_more_items").remove();
                            var last = $("#whoisonline");
                            last.append(data);
                        } else {
                            $("#link_load_more_items").remove();
                        }
                    }
                });
            });
        });
    </script>
{% endblock %}
