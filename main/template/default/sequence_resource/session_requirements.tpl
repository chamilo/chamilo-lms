{% if 'requirements' == item_type %}
    <h3>{{ 'RequiredCourses'|get_lang }}</h3>
{% else %}
    <h3>{{ 'Dependencies'|get_lang }}</h3>
{% endif %}

{% for item in sequences %}
    {% if 'requirements' == item_type %}
        {% set sessions = item.requirements %}
    {% else %}
        {% set sessions = item.dependents %}
    {% endif %}

    <h4>{{ item.name }}</h4>
    <div id="parents">
        {% for session in sessions %}
            <div class="parent">
                <div class="big-icon">
                    <img src="{{ 'item-sequence.png'|icon(48) }}" width="48" height="48">
                    <p class="sequence-course">{{ session.name }}</p>

                    {% if _u.logged %}
                        <span class="label {{ session.status ? 'label-success' : 'label-danger' }}">
                            {% if session.status %}
                                <em class="fa fa-check"></em> {{ 'Complete'|get_lang }}
                            {% else %}
                                <em class="fa fa-exclamation-triangle"></em> {{ 'Incomplete'|get_lang }}
                            {% endif %}
                        </span>
                    {% endif %}
                </div>
            </div>

            {% if loop.index != sessions|length %}
                <em class="fa fa-plus fa-3x sequence-plus-icon"></em>
            {% endif %}
        {% endfor %}
    </div>
{% endfor %}

{% if allow_subscription %}
    <hr>
    <p>{{ subscribe_button }}</p>
{% endif %}
