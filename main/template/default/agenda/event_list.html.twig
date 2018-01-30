{{ agenda_actions }}

<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
    {% for event in agenda_events %}
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="heading-{{ event.id }}">
                {% if is_allowed_to_edit and show_action %}
                    <div class="pull-right">
                        {% if event.visibility == 1 %}
                            <a class="btn btn-default btn-xs"
                               href="{% if url %}{{ url }}{% else %}{{ event.url }}{% endif %}&action=change_visibility&visibility=0&id={{ event.real_id }}&type={{ event.type }}">
                                <img title="{{ 'Invisible' }}" src="{{ 'visible.png'|icon(22) }}">
                            </a>
                        {% else %}
                            {% if event.type == 'course' or event.type == 'session' %}
                                <a class="btn btn-default btn-xs"
                                   href="{% if url %}{{ url }}{% else %}{{ event.url }}{% endif %}&action=change_visibility&visibility=1&id={{ event.real_id }}&type={{ event.type }}">
                                    <img title="{{ 'Visible' }}" src="{{ 'invisible.png'|icon(22) }}">
                                </a>
                            {% endif %}
                        {% endif %}
                    </div>
                {% endif %}

                <h4 class="panel-title">
                    <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                       href="#collapse-{{ event.id }}" aria-expanded="false" aria-controls="collapse-{{ event.id }}">
                        {{ event.title }}
                        <br>
                        <small>
                            {{ event.start_date_localtime }}

                            &dash;

                            {% if event.allDay %}
                                {{ 'AllDay' | get_lang }}
                            {% else %}
                                {{ event.end_date_localtime }}
                            {% endif %}
                        </small>
                    </a>
                </h4>
            </div>
            <div id="collapse-{{ event.id }}" class="panel-collapse collapse" role="tabpanel"
                 aria-labelledby="heading-{{ event.id }}">
                <ul class="list-group">
                    {% if event.description %}
                        <li class="list-group-item">
                            {{ event.description }}
                        </li>
                    {% endif %}

                    {% if event.comment %}
                        <li class="list-group-item">
                            {{ event.comment }}
                        </li>
                    {% endif %}

                    {% if event.attachment %}
                        <li class="list-group-item">{{ event.attachment }}</li>
                    {% endif %}
                </ul>
            </div>
        </div>
    {% endfor %}
</div>
