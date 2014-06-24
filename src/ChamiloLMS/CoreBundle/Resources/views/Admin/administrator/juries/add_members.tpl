{% extends "@template_style/layout/layout_1_col.tpl" %}
{% block content %}
    <script>
    function check() {

    }
    $(function() {
        $("#jury_user_user_id").fcbkcomplete({
            json_url: "{{ url('jury.controller:searchUserAction')}}",
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

    <form action="{{ url('jury.controller:addMembersAction', { "id" : jury_id }) }}" method="post" {{ form_enctype(form) }}>
      {{ form_widget(form) }}
    </form>
{% endblock %}
