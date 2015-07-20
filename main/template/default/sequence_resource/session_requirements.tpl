<h2 class="page-header">{{ 'SessionRequirements'|get_lang }}</h2>

{% for item in sequences %}
    <h4>{{ item.name }}</h4>

    <div id="parents">
        {% for session in item.requirements %}
            <div class="parent">
                <div class="big-icon">
                    <img src="{{ 'item-sequence.png'|icon(48) }}" width="48" height="48">
                    <p class="sequence-course">{{ session.name }}</p>
                    <span class="label {{ session.status ? 'label-success' : 'label-danger' }}">
                        {% if session.status %}
                            <i class="fa fa-check"></i> {{ 'Complete'|get_lang }}
                        {% else %}
                            <i class="fa fa-exclamation-triangle"></i> {{ 'Incomplete'|get_lang }}
                        {% endif %}
                    </span>
                </div>
            </div>

            {% if loop.index != item.requirements|length %}
                <i class="fa fa-plus fa-3x sequence-plus-icon"></i>
            {% endif %}
        {% endfor %}
    </div>
{% endfor %}

{% if allow_subscription %}
    <hr>
    <p>{{ subscribe_button }}</p>
{% endif %}
