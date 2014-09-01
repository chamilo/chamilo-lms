<script type='text/javascript' src="../js/buycourses.js"></script>

<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="span12">
        <table id="courses_table" class="data_table">
            <tr class="row_odd">
                <th>{{ 'Title'|get_lang }}</th>
                <th>{{ 'OfficialCode'|get_lang }}</th>
                <th class="ta-center">{{ 'Visible'|get_lang }}</th>
                <th class="span2">{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th class="span1 ta-center">{{ 'Option'|get_lang }}</th>
            </tr>
            {% set i = 0 %}

            {% for course in courses %}
                {{ i%2 == 0 ? '<tr class="row_even">' : '<tr class="row_odd">' }}
                    {% set i = i + 1 %}
                    <td>
                        {{ visibility[course.visibility] }}
                        <a href="{{ server }}courses/{{course.code}}/index.php">{{course.title}}</a>
                        <span class="label label-info">{{ course.visual_code }}</span>
                    </td>
                    <td>
                        {{course.code}}
                    </td>
                    <td class="ta-center">
                        {% if course.visible == 1 %}
                            <input type="checkbox" name="visible" value="1" checked="checked" size="6" />
                        {% else %}
                            <input type="checkbox" name="visible" value="1" size="6" />
                        {% endif %}
                    </td>
                    <td><input type="text" name="price" value="{{course.price}}" class="span1 price" /> {{ currency }}</td>
                    <td class=" ta-center" id="course{{ course.id }}">
                        <div class="confirmed"><img src="{{ confirmation_img }}" alt="ok"/></div>
                        <div class="modified" style="display:none"><img id="{{course.course_id}}" src="{{ save_img }}" alt="save" class="cursor save"/></div>
                    </td>
                </tr>
            {% endfor %}
        </table>
    </div>
<div class="cleared"></div>
</div>
