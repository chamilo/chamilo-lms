<div class="row">
    <div class="col-sm-5">
        {{ form }}
    </div>
    {% if not errors is empty %}
        <div class="col-sm-7">
            <h4 class="page-header">{{ 'Errors'|get_lang }}</h4>
            <ul>
                {% for error in errors %}
                    <li>
                        {{ 'Email: %s. %s ago'|format(error.mail, error.time) }}
                        <pre>{{ error.reason|replace({'\n': '<br>'}) }}</pre>
                    </li>
                {% endfor %}
            </ul>
        </div>
    {% endif %}
</div>

