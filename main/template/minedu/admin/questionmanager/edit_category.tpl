{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <script>
    function check() {
        $("#parent_id option:selected").each(function() {
            var id = $(this).val();
            var name = $(this).text();
            if (id != "" ) {
                $.ajax({
                async: false,
                url: "{{ _p.web_ajax }}exercise.ajax.php?type=global&a=exercise_category_exists",
                data: "id="+id,
                success: function(return_value) {
                    if (return_value == 0 ) {
                        alert("{{ 'CategoryDoesNotExists' | get_lang }}");
                        //Deleting select option tag
                        $("#parent_id").find("option").remove();
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
        $("#parent_id").fcbkcomplete({
           json_url: "{{ _p.web_ajax }}exercise.ajax.php?type=global&a=search_category_parent",
           maxitems: 1,
           addontab: false,
           input_min_size: 1,
           cache: false,
           complete_text:"{{ 'StartToType' | get_lang }}",
           firstselected: false,
           onselect: check,
           filter_selected: true,
           newel: true
        });
    });
    </script>

    {{ form }}
{% endblock %}
