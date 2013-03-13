{% extends "default/layout/layout_1_col.tpl" %}
{% block content %}
    {#
    <h2>{{ 'SubscribeUsersToLp' | get_lang }}</h2>
    <script>
        $().ready(function() {
            $('#add').click(function() {
               return !$('#{{ form.origin.vars.id }} option:selected').remove().appendTo('#{{ form.destination.vars.id }}');
            });

            $('#remove').click(function() {
                return !$('#{{ form.destination.vars.id }} option:selected').remove().appendTo('#{{ form.origin.vars.id }}');
            });

            $('#send').click(function() {
                $("#{{ form.destination.vars.id }}").each(function(){
                $("#{{ form.destination.vars.id }} option").attr("selected","selected");
                });
            });
        });
    </script>
    <form method="POST" {{ form_enctype(form) }}>
        {{ form_errors(form) }}
        <div class="row">
            <div class="span4">
                {{ form_label(form.origin) }}
                {{ form_widget(form.origin) }}
            </div>
            <div class="span1">
                <br />
                <button id="add" class="btn btn-block"><i class="icon-arrow-right icon-large"></i></button>
                <button id="remove" class="btn btn-block"><i class="icon-arrow-left icon-large"></i></button>
            </div>
            <div class="span4">
                {{ form_label(form.destination) }}
                {{ form_widget(form.destination) }}
            </div>
        </div>
        <div class="row">
            <div class="span12">
                <button id="send" type="submit" class="btn btn-primary"><i class="icon-arrow-lef icon-large"></i>{{ 'Save' | get_lang }}</button>
            </div>
        </div>
        {{ form_rest(form) }}
    </form>
    #}
    {{ form }}
{% endblock %}