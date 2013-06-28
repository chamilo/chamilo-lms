{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <script>

        function check() {
            $("#branch_parent_id option:selected").each(function() {
                var id = $(this).val();
                var name = $(this).text();
                if (id != "" ) {
                    $.ajax({
                        async: false,
                        url: "{{ _p.web_ajax }}&a=exercise_category_exists",
                        data: "id="+id,
                        success: function(return_value) {
                            if (return_value == 0 ) {
                                alert("{{ 'DontExist' }}");
                                //Deleting select option tag
                                $("#branch_parent_id").find("option").remove();

                                $(".holder li").each(function () {
                                    if ($(this).attr("rel") == id) {
                                        $(this).remove();
                                    }
                                });
                            }
                        }
                    });
                }
            });
        }
        $(function() {
            $("#branch_parent_id").fcbkcomplete({
                json_url: "{{ _p.web_ajax }}&a=searchBranch",
                maxitems: 1,
                addontab: false,
                input_min_size: 1,
                cache: false,
                complete_text:" {{ 'StartToType'  }} ",
                firstselected: false,
                onselect: check,
                filter_selected: true,
                newel: true
            });
        });
    </script>

    {% import app.template_style ~ "/default_actions/settings.tpl" as actions %}
    {{ actions.add(form, links) }}
{% endblock %}
