{% extends 'layout/layout_1_col.tpl'|get_template %}

{% set user_id = student_id == _u.id ? 0 : student_id %}

{% block content %}
    {{ toolbar }}
    <nav aria-label="...">
        <ul class="pager">
            <li class="previous">
                <a href="{{ _p.web_self ~ '?' ~ {"year": search_year - 1, "user": user_id, "order": current_order } |url_encode }}">
                    <span aria-hidden="true">&larr;</span> {{ search_year - 1 }}
                </a>
            </li>
            <li class="current">
                {{ search_year }}
            </li>
            <li class="next">
                <a href="{{ _p.web_self ~ '?' ~ {"year": search_year + 1, "user": user_id, "order": current_order }|url_encode }}">
                    {{ search_year + 1 }} <span aria-hidden="true">&rarr;</span>
                </a>
            </li>
        </ul>
    </nav>
    {% if students|length > 0 %}
        <div class="table-responsive" id="calendar-session-planification">
            <table class="table table-bordered table-striped table-condensed">
                <thead>
                <tr>
                    <th class="col-session">
                        <a href=" {{ _p.web_self ~ '?' ~ {"year": search_year, "user": user_id, "order": order }|url_encode }} ">
                            {{ 'Student'|get_lang }}
                        </a>
                    </th>
                    <th>{{ 'Session'|get_lang }}</th>
                    {% for i in 1..52 %}
                        <th class="col-week text-center" title="{{ 'WeekX'|get_lang|format(i) }}"><span>{{ i }}</span></th>
                    {% endfor %}
                </tr>
                </thead>
                <tbody>
                {% for student in students %}
                    {% for session in student.sessions %}
                        <tr>
                        <td class="col-session">
                            {% if loop.index0 == 0 %}
                                {{ student.complete_name }}
                            {% endif %}
                        </td>
                        <td >
                            {% if loop.index0 == 0 %}
                                {{ student.sessions | length }}
                            {% endif %}
                        </td>
                        {% if session.start > 0 %}
                            <td class="col-week" colspan="{{ session.start }}">&nbsp;</td>
                        {% endif %}

                        <td class="col-week text-center {{ session.start_in_last_year or session.no_start ? 'in_last_year' : '' }} {{ session.end_in_next_year or session.no_end ? 'in_next_year' : '' }}"
                            colspan="{{ session.duration }}" title="{{ session.name | e('html') }} - {{ session.human_date }}"
                            style="background-color: {{ session.color }}">
                            <span>
                                <span class="sr-only">{{ session.name | e('html') }} - {{ session.human_date }}</span>
                            </span>
                        </td>

                        {% if session.duration + session.start < 52 %}
                            <td class="col-week" colspan="{{ 52 - session.duration - session.start }}">&nbsp;</td>
                        {% endif %}
                        </tr>
                    {% endfor %}
                {% endfor %}
                </tbody>
            </table>

            {{ legend }}
        </div>
    {% else %}
        <div class="alert alert-warning">
            {{ 'ThereIsNotStillASession'|get_lang }}
        </div>
    {% endif %}
{% endblock %}
