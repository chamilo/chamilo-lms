{% for block_item in blocks %}
    <div id="tabs-{{ loop.index }}" class="col-md-6">
        <div class="well_border {{ block_item.class }}">
            <h4>{{ block_item.icon }} {{ block_item.label }}</h4>
            <div>
                {{ block_item.search_form }}
            </div>
            {% if block_item.items is not empty %}
                <ul>
                {% for url in block_item.items %}
                    {% if url.url is not empty %}
                    <li>
                        <a href="{{ url.url }}">
                            {{ url.label }}
                        </a>
                    </li>
                    {% endif %}
                {% endfor %}
                </ul>
            {% endif %}

            {% if block_item.extra is not null %}
                <div>
                    {{ block_item.extra }}
                </div>
            {% endif %}
        </div>
    </div>
{% endfor %}

<div class="col-md-6">
    <div class="well_border">
        <ul>
            <li>
                <a href="{{ url('question_score.controller:indexAction') }}">{{ 'Question score name' |trans }}</a>
            </li>
            <li>
                <a href="{{ url('question_score_name.controller:indexAction') }}">{{ 'Question names' |trans }}</a>
            </li>
        </ul>
    </div>
</div>

