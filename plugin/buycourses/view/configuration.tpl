<script type='text/javascript' src="../js/buycourses.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/css/style.css"/>

{% if sessions_are_included %}
    <ul class="nav nav-tabs buy-courses-tabs" role="tablist">
        <li role="presentation" class="active">
            <a href="#courses" aria-controls="courses" role="tab" data-toggle="tab">{{ 'Courses'|get_lang }}</a>
        </li>
        <li role="presentation">
            <a href="#sessions" aria-controls="sessions" role="tab" data-toggle="tab">{{ 'Sessions'|get_lang }}</a>
        </li>
    </ul>
{% endif %}

<div class="tab-content">
    <div role="tabpanel" class="tab-pane fade in active" id="courses">
        <div class="table-responsive">
            <table id="courses_table" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>{{ 'Title'|get_lang }}</th>
                        <th class="text-center">{{ 'OfficialCode'|get_lang }}</th>
                        <th class="text-center">{{ 'Visible'|get_lang }}</th>
                        <th class="text-right" width="200">{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th class="text-center">{{ 'Option'|get_lang }}</th>
                    </tr>
                </thead>

                <tbody>
                    {% for course in courses %}
                        <tr>
                            <td>
                                {{ visibility[course.visibility] }}
                                <a href="{{ _p.web_course ~ course.code ~ '/index.php' }}">{{course.title}}</a>
                                <span class="label label-info">{{ course.visual_code }}</span>
                            </td>
                            <td class="text-center">
                                {{course.code}}
                            </td>
                            <td class="text-center">
                                {% if course.visible == 1 %}
                                    <input type="checkbox" name="visible" value="1" checked="checked" size="6">
                                {% else %}
                                    <input type="checkbox" name="visible" value="1" size="6">
                                {% endif %}
                            </td>
                            <td width="200">
                                {% if currency %}
                                    <div class="input-group">
                                        <span class="input-group-addon" id="price-{{ course.course_id }}">{{ currency }}</span>
                                        <input type="number" name="price" value="{{course.price}}" step="0.01" class="text-right form-control" aria-describedby="price-{{ course.course_id }}">
                                    </div>
                                {% else %}
                                    <input type="number" name="price" value="{{course.price}}" step="0.01" class="text-right form-control">
                                {% endif %}
                            </td>
                            <td class=" text-center" id="course{{ course.id }}">
                                <div class="confirmed"><img src="{{ _p.web_plugin ~ 'buycourses/resources/img/32/accept.png' }}" alt="ok"/></div>
                                <div class="modified" style="display:none">
                                    <img id="{{course.course_id}}" src="{{ _p.web_plugin ~ 'buycourses/resources/img/32/save.png' }}" alt="save" class="cursor save"/>
                                </div>
                            </td>
                        </tr>
                    {% endfor %}
                </tbody>
            </table>
        </div>
    </div>

    {% if sessions_are_included %}
        <div role="tabpanel" class="tab-pane" id="sessions">
            <div class="table-responsive">
                <table id="courses_table" class="table">
                    <thead>
                        <tr>
                            <th>{{ 'Title'|get_lang }}</th>
                            <th class="text-center">{{ 'StartDate'|get_lang }}</th>
                            <th class="text-center">{{ 'EndDate'|get_lang }}</th>
                            <th class="text-center">{{ 'Visible'|get_lang }}</th>
                            <th class="text-right">{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                            <th class="text-center">{{ 'Option'|get_lang }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% for session in sessions %}
                            <tr>
                                <td>
                                    {{ visibility[session.visibility] }}
                                    <a href="{{ _p.web_main ~ 'session/index.php?' ~ {'session_id': session.id}|url_encode() }}">{{session.name}}</a>
                                </td>
                                <td class="text-center">
                                    {{ session.access_start_date }}
                                </td>
                                <td class="text-center">
                                    {{ session.access_end_date }}
                                </td>
                                <td class="text-center">
                                    {% if session.visible == 1 %}
                                        <input type="checkbox" name="visible" value="1" checked="checked" size="6" />
                                    {% else %}
                                        <input type="checkbox" name="visible" value="1" size="6" />
                                    {% endif %}
                                </td>
                                <td class="text-right" width="200">
                                    {% if currency %}
                                        <div class="input-group">
                                            <span class="input-group-addon" id="price-{{ session.id }}">{{ currency }}</span>
                                            <input type="number" name="price" value="{{session.price}}" step="0.01" class="text-right form-control" aria-describedby="price-{{ session.id }}">
                                        </div>
                                    {% else %}
                                        <input type="number" name="price" value="{{session.price}}" step="0.01" class="text-right form-control">
                                    {% endif %}
                                </td>
                                <td class=" text-center" id="session{{ session.id }}">
                                    <div class="confirmed">
                                        <img src="{{ _p.web_plugin ~ 'buycourses/resources/img/32/accept.png' }}" alt="ok"/>
                                    </div>
                                    <div class="modified" style="display:none">
                                        <img id="{{session.id}}" src="{{ _p.web_plugin ~ 'buycourses/resources/img/32/save.png' }}" alt="save" class="cursor save"/>
                                    </div>
                                </td>
                            </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
        </div>
    {% endif %}
</div>
