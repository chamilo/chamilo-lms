{% extends 'layout/layout_1_col.tpl'|get_template %}

{% block content %}
    {{ form_filter }}

    {% for item in data %}
    <div id="career-{{ item.id }}" class="career panel panel-default">
        <div  class="panel-heading">
            <h4>
                {% if _u.is_admin %}
                    <a href="{{ _p.web }}main/admin/careers.php?action=edit&id={{ item.id }}">
                    {{ item.name }}
                    </a>
                {% else %}
                    {{ item.name }}
                {% endif %}
            </h4>
        </div>
        <div class="panel-body">
            {{ item.description }}
            <table class="table promotions">
                <thead class="title">
                    <th>{{ 'Promotions' | get_lang }}</th>
                    <th>{{ 'StudyCycle' | get_lang }} </th>
                    <th>{{ 'Courses' | get_lang }} </th>
                </thead>
            {% for promotions in item.career %}
                {% for prom in promotions %}
                    {% set line = prom.sessions|length + 1 %}
                    <tr>
                        <td class="promo" rowspan="{{ line }}">
                            <h4 id="promotion-id-{{ prom.id }}">
                                <a title="{{ prom.name }}" href="{{ _p.web }}main/admin/promotions.php?action=edit&id={{ prom.id }}">
                                    {{ prom.name }}
                                </a>
                            </h4>
                        </td>
                        {% if line == 1 %}
                        <td>&nbsp;</td><td>&nbsp;</td>
                        {% endif %}
                    </tr>
                    {% for session in prom.sessions %}
                    {% set sessionid = session.data.id %}
                        <tr>
                            <td class="cycles">
                                <h4 id="session-id-{{ sessionid }}">
                                    <a title="{{ session.data.name }}" href="{{ _p.web }}main/session/resume_session.php?id_session={{ sessionid }}">
                                        {{ session.data.name }}
                                    </a>
                                </h4>
                            </td>
                            <td class="courses">
                                <ul>
                                {% for course in session.courses %}
                                <li>
                                    <a href="{{ _p.web }}courses/{{ course.directory }}/index.php?id_session={{ sessionid }}" title="{{ course.title }}">
                                        {{ course.title }}
                                    </a>
                                </li>
                                {% endfor %}
                                </ul>
                            </td>
                        </tr>
                    {% endfor %}
                {% endfor %}
            {% endfor %}
            </table>
        </div>
    </div>
    {% endfor %}
{% endblock %}
