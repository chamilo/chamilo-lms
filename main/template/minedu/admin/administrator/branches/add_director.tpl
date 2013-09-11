{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <link href="{{ _p.web_lib }}javascript/tag/style.css" rel="stylesheet" type="text/css" />
    <script src="{{ _p.web_lib }}javascript/tag/jquery.fcbkcomplete.js" type="text/javascript" language="javascript"></script>

    <script>
    function check() {
    }
    $(function() {
        $("#branch_user_user_id").fcbkcomplete({
            json_url: "{{ url('jury.controller:searchUserAction', {'role' : 'ROLE_DIRECTOR'})}}",
            maxitems: 1,
            addontab: false,
            input_min_size: 1,
            cache: false,
            complete_text: "{{ 'StartToType' | trans}}",
            firstselected: false,
            onselect: check,
            filter_selected: true,
            newel: true
        });
    });
    </script>

    <form action="{{ url('branch.controller:addDirectorAction', { "id" : id }) }}" method="post" {{ form_enctype(form) }}>
      {{ form_widget(form) }}
    </form>

{% endblock %}
