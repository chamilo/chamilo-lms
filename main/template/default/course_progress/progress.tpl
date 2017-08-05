{{ actions }}
{{ message }}
{{ flash_messages }}
{% if data is not empty %}
{% set tutor =  false|api_is_allowed_to_edit(true) %}
<div id="course-progress" class="thematic">
    <div class="row">
        <div class="col-md-12">
            <div class="bar-progress">
                <div class="pull-right">
                    <div class="score-progress">
                        <h3>{{ 'Progress' | get_lang }}: <span id="div_result">{{ score_progress }}</span> %</h3>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table width="100%" class="table table-hover">
                    <tr>
                        <th style="width: 25%">{{ 'Thematic' | get_lang }}</th>
                        <th style="width: 40%">{{ 'ThematicPlan' | get_lang }}</th>
                        <th style="width: 35%">{{ 'ThematicAdvance' | get_lang }}</th>
                    </tr>
                    {% for item in data %}
                        <tr>
                            <td id="id-thematic-{{ item.id }}">
                                {% if session_star is empty %}
                                    <h3>{{ item.title }}</h3>
                                {% else %}
                                    <h3>{{ item.title }} {{ session_star }}</h3>
                                {% endif %}
                                {{ item.content }}
                                <div class="btn-group btn-group-sm">
                                    {{ item.toolbar }}
                                </div>
                            </td>
                            <td>
                                {% if tutor %}
                                <div class="pull-right">
                                    <a title="{{ 'EditThematicPlan' | get_lang }}" href="index.php?{{ _p.web_cid_query }}&origin=thematic_details&action=thematic_plan_list&thematic_id={{ item.id }}&width=700&height=500'" class="btn btn-default">
                                        <i class="fa fa-pencil" aria-hidden="true"></i>
                                    </a>
                                </div>
                                {% endif %}
                                <div class="thematic_plan_{{ item.id }}">
                                    {% if item.thematic_plan is empty %}
                                    <div class="alert-thematic">
                                        <div class="alert alert-info" role="alert">{{ 'StillDoNotHaveAThematicPlan' | get_lang }}</div>
                                    </div>
                                    {% else %}
                                        {% for subitem in item.thematic_plan %}
                                        <h4>{{ subitem.title }}</h4>
                                        <p>{{ subitem.description }}</p>
                                        {% endfor %}
                                    {% endif %}
                                </div>
                            </td>
                            <td>
                                {% if tutor %}
                                <div class="pull-right">
                                    <a title="{{ 'NewThematicAdvance' | get_lang }}" href="index.php?{{ _p.web_cid_query }}&action=thematic_advance_add&thematic_id={{ item.id }}" class="btn btn-default">
                                        <i class="fa fa-plus-circle" aria-hidden="true"></i>
                                    </a>
                                </div>
                                {% endif %}
                                <div class="thematic-advance">
                                <table width="100%" class="table">
                                {% if item.thematic_advance is not empty %}
                                {% for advance in item.thematic_advance %}
                                <tr>
                                    <td style="width: 90%" class="thematic_advance_content" id="thematic_advance_content_id_{{ advance.id }}">
                                        <div id="thematic_advance_{{ advance.id }}">
                                        <strong>{{ advance.start_date | format_date }}</strong>
                                        {{ advance.content }}
                                        </div>
                                        {% if tutor %}
                                            <div class="toolbar-actions">
                                                <div id="thematic_advance_tools_{{ advance.id }}" class="thematic_advance_actions">
                                                    <div class="btn-group btn-group-sm">
                                                        <a class="btn btn-default btn-sm" href="index.php?{{ _p.web_cid_query }}&action=thematic_advance_edit&thematic_id={{ item.id }}&thematic_advance_id={{ advance.id }}" title="{{ 'Edit' |get_lang }}">
                                                            <i class="fa fa-pencil" aria-hidden="true"></i>
                                                        </a>
                                                        <a class="btn btn-default btn-sm" onclick="javascript:if(!confirm('{{ 'AreYouSureToDelete' | get_lang }}')) return false;" href="index.php?{{ _p.web_cid_query }}&action=thematic_advance_delete&thematic_id={{ item.id }}&thematic_advance_id={{ advance.id }}" title="{{ 'Delete' |get_lang }}">
                                                            <i class="fa fa-trash" aria-hidden="true"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        {% endif %}
                                        </div>
                                    </td>
                                    {% if advance.done_advance == 1 %}
                                        {% set color = "background-color:#E5EDF9;" %}
                                    {% else %}
                                        {% set color = "background-color:#FFFFFF;" %}
                                    {% endif %}
                                    {% if tutor %}
                                        <td style="width: 10%; {{ color }}" id="td_done_thematic_{{ advance.id }}">
                                        {% set check = ""  %}
                                        {% if item.last_done == advance.id %}
                                            {% set check = "checked"  %}
                                        {% endif %}
                                        <center>
                                            <input type="radio" class="done_thematic" id="done_thematic_{{ advance.id }}" name="done_thematic" value="{{ advance.id }}" {{ check }} onclick="update_done_thematic_advance(this.value)">
                                        </center>
                                    {% else %}
                                        </td>
                                    {% endif %}
                                </tr>
                                {% endfor %}
                                {% else %}
                                    <div class="alert alert-info" role="alert">{{ 'ThereIsNoAThematicAdvance' | get_lang }}</div>
                                {% endif %}
                                </table>
                                </div>
                            </td>
                        </tr>
                    {% endfor %}

                </table>
            </div>
        </div>
    </div>
</div>
{% else %}
    <div class="alert alert-info" role="alert">{{ 'ThereIsNoAThematicSection' | get_lang }}</div>
{% endif %}