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
    // When loading the page create delete buttons for inputs:
    $("#list form :input").each(function(index, value) {
        var input = $(this);
        if (input.attr('type') == 'text') {
            var removeForm = $('<a class="btn btn-danger" href="#"><i class="icon-minus-sign icon-large"></i></a>');
            //var id = input.parent().parent().parent().parent().attr('id');
            var maxRepeat = input.parent().parent().parent().parent().attr('data-max');

            if (maxRepeat != 1) {
                input.parent().append(removeForm);
                addTagFormDeleteLink(removeForm);
            }
        }
    });

    // When clicking in the add button:
    $('.btn-add[data-target]').live('click', function(event) {
        var collectionHolder = $('#' + $(this).attr('data-target'));
        var maxRepeat = collectionHolder.attr('data-max');
        var countInput = collectionHolder.find('input:text').length;

        if (countInput > maxRepeat - 1) {
            return false;
        }

        if (!collectionHolder.attr('data-counter')) {
            collectionHolder.attr('data-counter', collectionHolder.children().length);
        }
        var prototype = collectionHolder.attr('data-prototype');
        var form = prototype.replace(/__name__/g, collectionHolder.attr('data-counter'));

        collectionHolder.attr('data-counter', Number(collectionHolder.attr('data-counter')) + 1);

        var removeForm = $('<a class="btn btn-danger" href="#"><i class="icon-minus-sign icon-large"></i></a>');

        var liItem = $('<li id="'+collectionHolder.attr('data-counter')+'">'+form+'</li>');
        liItem.find('.controls').append(removeForm);

        var item = collectionHolder.find('ul').append(liItem);
        addTagFormDeleteLink(removeForm);
        event && event.preventDefault();
    });

    $('#saveQuestionBar').affix();
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

function saveAll() {
    var items = $("form").find(".items");
    $(items).each(function(index, value) {
        var itemId = $(this).attr('id')
        save(itemId);
    });
}

</script>
<div class="row">
    <div class="span10">

        <h2>Trayectoria</h2>
        <p>Explicaciones</p>
        <p>Las respuestas a este formulario son de carácter jurado.</p>
        <div id="list" class="trajectory">
            {% for subcategory in categories %}
                {% if subcategory.lvl == 0 %}
                    <h3> {{ subcategory.title }}</h3>
                    <hr />
                {% else %}
                    <h4> {{ subcategory.title }}</h4>
                {% endif %}

                {% for item in subcategory.items %}
                    <h5> {{ item.title }} (item) - Max {{ item.maxRepeat }}</h5>

                    {{ form_start(form_list[item.id]) }}
                        <div class="btn-group">
                            {# form_widget(form_list[item.id].submit) #}
                            <!-- <a class="btn btn-success" onclick="save('items_{{ item.id }}');" data-target="items_{{ item.id }}">{{ 'Save items' | get_lang }}</a> -->
                            {% if item.maxRepeat > 1 %}
                                <a class="btn-add btn btn-primary" data-target="items_{{ item.id }}"><i class="icon-plus-sign icon-large"></i></a>
                            {% endif %}
                        </div>

                        <div id="items_{{ item.id }}" class="items" data-max="{{ item.maxRepeat }}" data-prototype="{{ form_widget(form_list[item.id].userItems.vars.prototype)|e }}" >
                            {% for widget in form_list[item.id].userItems.children %}
                                {{ _self.widget_prototype(widget, 'Remove item') }}
                            {% endfor %}

                            <ul>
                            </ul>
                        </div>

                    {{ form_end(form_list[item.id]) }}
                {% endfor %}
            {% endfor %}
        </div>
    </div>

    <div class="span2">
        <div id="saveQuestionBar" data-spy="affix" data-offset-top="500">
            <a class="btn btn-success btn-large  btn-block" onclick="saveAll();">{{ 'Save all' | get_lang }}</a>
        </div>
    </div>
</div>
{% endblock %}
