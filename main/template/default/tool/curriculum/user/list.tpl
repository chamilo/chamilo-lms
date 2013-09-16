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
    // Create delete buttons:
    $("#list form input:text").each(function(index, value) {
        var input = $(this);

        var removeForm = $('<a class="btn btn-danger" href="#"><i class="icon-minus-sign icon-large"></i></a>');
        var maxRepeat = input.parent().parent().parent().parent().attr('data-max');

        if (maxRepeat != 1) {
            input.parent().append(removeForm);
            addTagFormDeleteLink(removeForm);
        }
    });

    // Create add buttons:
    $(".items").each(function(index, value) {
        var itemId = $(this).attr('id');
        var lastInput = $(this).find("input:text").last();
        var addButton = $('<a class="btn-add btn btn-primary" data-target="'+itemId+'"><i class="icon-plus-sign icon-large"></i></a>');
    });

    // When clicking in the add button:
    $('.btn-add[data-target]').live('click', function(event) {
        var collectionHolder = $('#' + $(this).attr('data-target'));
        var maxRepeat = collectionHolder.attr('data-max');
        var countInput = collectionHolder.find('input:text').length;

        // Disables the add button when reached the max count
        var showButtonWhenIsHidden = false;
        if (countInput > maxRepeat - 2) {
            showButtonWhenIsHidden = true;
        }

        // Disables the clicking when it's disabled
        if (countInput > maxRepeat - 1) {
            $(this).addClass('disabled');
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
        if (showButtonWhenIsHidden) {
            addTagFormDeleteLink(removeForm, $(this));
        } else {

            addTagFormDeleteLink(removeForm);
        }
        event && event.preventDefault();
    });

    $('#saveQuestionBar').affix();
});

function addTagFormDeleteLink($tagFormLi, showButton) {
    $tagFormLi.on('click', function(e) {
        e.preventDefault();
        $(this).parent().remove();
        // Restore the add button.
        if (showButton) {
            showButton.removeClass('disabled');
        }
    });
}

function save(itemId) {
    var form = $("#"+itemId).parent();
    var serializedForm = form.serialize();
    $.ajax({
        'url' : '{{ url('curriculum_user.controller:saveUserItemAction', {'course' : course.code, 'id_session' : course_session.id }) }}',
        'data' : serializedForm,
        'async': false,
        'type' : 'post'
        }
    );
}

function saveAll() {
    var items = $("form").find(".items");
    $(items).each(function(index, value) {
        var itemId = $(this).attr('id');
        save(itemId);
    });
    window.location = '{{ url('index') }}';
}

</script>
<div class="row">
    <div class="span10">
        {%  if is_granted('ROLE_ADMIN') and isAllowed %}
            <div class="actions">
                <a href="{{ url('curriculum_category.controller:indexAction', { 'course' : course.code, 'id_session' : course_session.id }) }}">
                   {{ "Categories" | trans }}
                </a>
                <a href="{{ url('curriculum_category.controller:resultsAction', { 'course' : course.code, 'id_session' : course_session.id }) }}">
                   {{ "Results" | trans }}
                </a>
            </div>
        {%  endif  %}

        <h2>Trayectoria</h2>
        <p>Explicaciones</p>
        <p>Las respuestas a este formulario son de carácter jurado.</p>
        <div id="list" class="trajectory">
            {% for subcategory in categories %}
                {% if subcategory.lvl == 0 %}
                   {#  <h3> {{ subcategory.title }} </h3>
                    <hr /> #}
                {% else %}
                    <h4> {{ subcategory.title }}</h4>
                {% endif %}

                {% for item in subcategory.items %}
                    {# Items #}
                    {{ item.title }} (item) - {{ 'CurriculumMaximumItem' | trans }} {{ item.maxRepeat }}
                    <div class="row">
                    {{ form_start(form_list[item.id]) }}

                        <div id="items_{{ item.id }}" class="items span8" data-max="{{ item.maxRepeat }}" data-prototype="{{ form_widget(form_list[item.id].userItems.vars.prototype)|e }}" >
                            {% for widget in form_list[item.id].userItems.children %}
                                {{ _self.widget_prototype(widget, 'Remove item') }}
                            {% endfor %}
                            <ul>
                            </ul>
                        </div>

                        <div class="span8">
                            <div class="btn-group">
                                {% if item.maxRepeat > 1 %}
                                    <a class="btn-add btn btn-primary" data-target="items_{{ item.id }}"><i class="icon-plus-sign icon-large"></i></a>
                                {% endif %}
                            </div>
                        </div>
                    {{ form_end(form_list[item.id]) }}
                    </div>
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
