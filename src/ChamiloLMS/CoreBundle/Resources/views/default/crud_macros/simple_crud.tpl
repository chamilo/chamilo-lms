{% macro list(items, links) %}
    <div class="btn-group">
        <a class="btn btn-default" href="{{ url(links.create_link) }}">
            <i class="fa fa-plus"></i> {{ 'Add' |trans }}
        </a>
    </div>
    <table class="table">
    {% for item in items %}
        <tr>
            <td>
                <a href="{{ url(links.read_link, { id: item.id }) }}">
                {{ item.name }}
                </a>
            </td>
            <td>
                <a class="btn btn-default" href="{{ url(links.update_link, { id: item.id }) }}">
                    {{ 'Edit' |trans }}
                </a>
                <a class="btn btn-danger" href="{{ url(links.delete_link, { id: item.id }) }}">
                    {{ 'Delete' |trans }}
                </a>
            </td>
        </tr>
    {% endfor %}
    </table>
    {{ grid_pagination }}
{% endmacro %}

{% macro add(form, links) %}
    <div class="btn-group">
        <a class="btn btn-default" href="{{ url(links.list_link) }}">
            <i class="fa fa-list"></i> {{ 'Return to list' |trans }}
        </a>
    </div>
    <hr />
    <form action="{{ url(links.create_link) }}" method="post" {{ form_enctype(form) }}>
        {{ form_widget(form) }}
    </form>
{% endmacro %}

{% macro edit(form, links) %}
    <div class="btn-group">
        <a class="btn btn-default" href="{{ url(links.list_link) }}">
            <i class="fa fa-list"></i> {{ 'Return to list' |trans }}
        </a>
    </div>
    <form action="{{ url(links.update_link, {id : item.id}) }}" method = "post" {{ form_enctype(form) }}>
        {{ form_widget(form) }}
    </form>
{% endmacro %}

{% macro read(item, links) %}
    <div class="btn-group">
        <a class="btn btn-default" href="{{ url(links.update_link, {id : item.id}) }}">
            <i class="fa fa-edit"></i> {{ 'Edit' |trans }}
        </a>
        <a class="btn btn-default" href="{{ url(links.list_link) }}">
            <i class="fa fa-list"></i> {{ 'Return to list' |trans }}
        </a>
    </div>
    <h2> {{ item.id  }}</h2>
    <p>{{ item.name }}</p>
{% endmacro %}

