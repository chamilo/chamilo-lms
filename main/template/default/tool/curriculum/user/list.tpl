{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}

{% macro widget_prototype(widget, remove_text) %}
    {% if widget.get('prototype') %}
        {% set form = widget.get('prototype') %}
        {% set name = widget.get('prototype').get('description') %}
    {% else %}
        {% set form = widget %}
        {% set name = widget.get('full_name') %}
    {% endif %}

    <div data-content="{{ name }}">
        {# <a class="btn-remove" data-related="{{ name }}">{{ remove_text }}</a> #}
        {{ form_widget(form) }}
    </div>

{% endmacro %}

{% block content %}

<script>

$(function() {

    $("#list form :input").each(function(index, value) {
        var input = $(this);
        if (input.attr('type') == 'text') {
            var removeForm = $('<a class="btn btn-danger" href="#">{{ 'Delete' | get_lang }}</a>');
            input.parent().parent().append(removeForm);
            addTagFormDeleteLink(removeForm);
        }
    });

    $('.btn-add[data-target]').live('click', function(event) {
        var collectionHolder = $('#' + $(this).attr('data-target'));
        if (!collectionHolder.attr('data-counter')) {
            collectionHolder.attr('data-counter', collectionHolder.children().length);
        }
        var prototype = collectionHolder.attr('data-prototype');
        var form = prototype.replace(/__name__/g, collectionHolder.attr('data-counter'));

        collectionHolder.attr('data-counter', Number(collectionHolder.attr('data-counter')) + 1);
        //collectionHolder.append(form);
        var removeForm = $('<a class="btn btn-danger" href="#">{{ 'Delete' | get_lang }}</a>');
        var liItem = $('<li id="'+collectionHolder.attr('data-counter')+'">'+form+'</li>').append(removeForm);
        var item = collectionHolder.find('ul').append(liItem);
        addTagFormDeleteLink(removeForm);
        event && event.preventDefault();
    });
});


function addTagFormDeleteLink($tagFormLi) {
    $tagFormLi.on('click', function(e) {
        e.preventDefault();
        $(this).parent().remove();
    });
}

function save(itemId) {
    var form = $("#"+itemId).parent();
    var serializedForm = form.serialize();
    $.post('{{ url('curriculum_user.controller:saveUserItemAction') }}', serializedForm);
    return false;
}

</script>
    <h2>Trayectoria</h2>

    <p>Explicaciones</p>
    <p>Las respuestas a este formulario son de carácter jurado.</p>
    <div id="list">

    {% for subcategory in categories %}
        <h3> {{ subcategory.title }}</h3>
        {% for item in subcategory.items %}
            <h4> {{ item.title }} (item)</h4>

            {{ form_start(form_list[item.id]) }}
                <div id="items_{{ item.id }}" class="items" data-prototype="{{ form_widget(form_list[item.id].userItems.vars.prototype)|e }}" >
                    <ul>
                    </ul>
                    {% for widget in form_list[item.id].userItems.children %}
                        {{ _self.widget_prototype(widget, 'Remove item') }}
                    {% endfor %}

                    <div class="btn-group">
                        {# form_widget(form_list[item.id].submit) #}
                        <a class="btn btn-success" onclick="save('items_{{ item.id }}');" data-target="items_{{ item.id }}">{{ 'Save item' | get_lang }}</a>
                        <a class="btn-add btn btn-primary" data-target="items_{{ item.id }}">{{ 'Add' | get_lang }}</a>
                    </div>
                </div>

            {{ form_end(form_list[item.id]) }}

        {% endfor %}
    {% endfor %}

    </div>




{% endblock %}
