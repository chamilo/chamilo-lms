
<h3 style="text-align: center; text-transform: uppercase; font-size: 20px; font-weight: bold;">{{ data.name }}</h3>
<br>
<p>{{ 'Candidate' | get_lang }} : {{ data.candidate }}</p>
<p>{{ 'ScormStartAttemptDate' | get_lang }} : {{ data.start_date }}</p>
<br>
<table style="width: 100%; font-size: 12px; font-weight: normal;">
    <tr>
        <th style="text-align: center; height: 25px;  background: #222222; color: #FFFFFF; border: 2px solid #FFFFFF;">
            {{ 'Attempt'|get_lang }}
        </th>
        <th style="text-align: center; background: #222222; color: #FFFFFF; border: 2px solid #FFFFFF;">
            {{ 'Score'|get_lang }}
        </th>
        <th style="text-align: center; background: #222222; color: #FFFFFF; border: 2px solid #FFFFFF;">
            {{ 'Duration'|get_lang }}
        </th>
        <th style="text-align: center; background: #222222; color: #FFFFFF; border: 2px solid #FFFFFF;">
            {{ 'StartTime'|get_lang }}
        </th>
        <th style="text-align: center; background: #222222; color: #FFFFFF; border: 2px solid #FFFFFF;">
            {{ 'EndTime'|get_lang }}
        </th>
    </tr>
    <tr>
        <td style="text-align: center; height: 50px; background: #d9d9d9; padding: 5px; display: block; border: 2px solid #FFFFFF;">
            {{ data.attempt }}
        </td>
        <td style="text-align: center; background: #d9d9d9; padding: 5px; display: block; border: 2px solid #FFFFFF;">
            {{ data.score }}
        </td>
        <td style="text-align: center; background: #d9d9d9; padding: 5px; display: block; border: 2px solid #FFFFFF;">
            {{ data.duration }}
        </td>
        <td style="text-align: center; background: #d9d9d9; padding: 5px; display: block; border: 2px solid #FFFFFF;">
            {{ data.start_time }}
        </td>
        <td style="text-align: center; background: #d9d9d9; padding: 5px; display: block; border: 2px solid #FFFFFF;">
            {{ data.end_time }}
        </td>
    </tr>
</table>
<br>

<table style="width: 100%; font-size: 12px;">
    <tr>
        <th style="text-align: center; height: 25px;  background: #222222; color: #FFFFFF; border: 2px solid #FFFFFF;">
            {{ 'Domains'|get_lang }}
        </th>
        <th style="text-align: center;  background: #222222; color: #FFFFFF; border: 2px solid #FFFFFF;">
            {{ 'Score'|get_lang }}
        </th>
        <th style="text-align: center;  background: #222222; color: #FFFFFF; border: 2px solid #FFFFFF;">
            {{ 'Percentage'|get_lang }}
        </th>
    </tr>

    {% for item in categories %}
    <tr>
        <td style="height:40px; width: 40%; text-align: left; padding: 5px; display: block; border-bottom: 1px solid #cdcdcd;">
            {{ item.name }}
        </td>
        <td style="text-align: center; padding: 5px; display: block; border-bottom: 1px solid #cdcdcd;">
            {% if item.score_numeric == 0 %}
                <span style="color: red;">{{ item.score }}</span>
            {% else %}
                <span>{{ item.score }}</span>
            {% endif %}
        </td>
        <td style="text-align: left; padding: 5px; display: block; border-bottom: 1px solid #cdcdcd;">
            <img src="{{ "bar_progress.png"|icon(22) }}" width="{{ item.score_numeric }}px" height="16px" alt="{{ "Percentage"|get_lang }}"/>
            {% if item.score_numeric == 0 %}
                <span style="color: red;">{{ item.score_percentage }}</span>
            {% else %}
                <span>{{ item.score_percentage }}</span>
            {% endif %}
        </td>
    </tr>
    {% endfor %}

    {% for item in general_score %}
        <tr>
            <td style="text-align: left; padding: 5px; height:30px; background: #222222; color: #FFFFFF; display: block;">
                <b>{{ 'GeneralTotal' | get_lang }}</b>
            </td>
            <td style="text-align: center; padding: 5px; display: block;background-color: #f6ffe2;">
                {{ item.score }}
            </td>
            <td style="text-align: left; padding: 5px; display: block; background-color: #f6ffe2;">
                <img src="{{ "bar_progress.png"|icon(22) }}" width="{{ item.score_numeric }}px" height="16px" alt="{{ "Percentage"|get_lang }}"/>
                {% if item.score_numeric == 0 %}
                    <span style="color: red;">{{ item.score_percentage }}</span>
                {% else %}
                    <span>{{ item.score_percentage }}</span>
                {% endif %}
            </td>
        </tr>
{#        {% if global_total %}#}
{#            <tr>#}
{#                <td>#}

{#                </td>#}
{#                <td>#}
{#                    {{ 'GlobalTotal'|get_lang }}#}
{#                </td>#}
{#                <td>#}
{#                    {{ global_total }}#}
{#                </td>#}
{#            </tr>#}
{#        {% endif %}#}
    {% endfor %}
</table>