<h2 align="center"> {{ pdf_title }} </h2>

{% if pdf_description %}
    {{ pdf_description }}
    <br /><br />
{% endif %}

<table align="center" width="100%" class="full-width border-thin">
    {% if pdf_student_info %}
    <tr>
        <td style="background-color: #E5E5E5; text-align: left; width:130px; ">
            <strong>{{ "Student" | get_lang }}:</strong>
        </td>
        <td>
            {{ pdf_student_info.complete_name }}
        </td>
    </tr>
    {% endif %}
    {% if pdf_teachers %}
        <tr>
            <td style="background-color: #E5E5E5; text-align: left; width:130px;">
                <strong>{{ "Teacher" | get_lang }}:</strong>
            </td>
            <td>
                {{ pdf_teachers }}
            </td>
        </tr>
    {% endif %}

    {% if pdf_session_info %}
        <tr>
            <td style="background-color: #E5E5E5; text-align: left; width:130px;">
                <strong>{{ "Session" | get_lang }}:</strong> {{ pdf_session_info.name }}
            </td>

            {% if pdf_session_info.description %}
            <td>
                <strong>{{ "Description" | get_lang }}:</strong> {{ pdf_session_info.description }}
            </td>
            {% endif %}
        </tr>

        {% if pdf_session_info.access_start_date != '' and pdf_session_info.access_end_date is not empty and pdf_session_info.access_end_date != '0000-00-00' %}
        <tr>
            <td style="background-color: #E5E5E5; text-align: left; width:130px;">
                <strong>{{ "PeriodToDisplay" | get_lang }}:</strong>
            </td>
            <td>
                {{ "FromDateXToDateY"| get_lang | format(pdf_session_info.access_start_date, pdf_session_info.access_end_date ) }}
            </td>
        </tr>
        {% endif %}
    {% endif %}

    {% if pdf_course_info %}
    <tr>
        <td style="background-color: #E5E5E5; text-align: left; width:130px;">
            <strong>{{ "Course" | get_lang }}:</strong>
        </td>
        <td>
            {{ pdf_course_info.title }} ({{ pdf_course_info.code }})
        </td>
    </tr>
        {% if pdf_course_category %}
            <tr>
                <td> <strong>{{ "Category" | get_lang }}:</strong></td>
                <td> {{ pdf_course_category }} </td>
            </tr>
        {% endif %}
    {% endif %}

    {% if pdf_date %}
    <tr>
        <td style="background-color: #E5E5E5; text-align: left; width:130px;">
            <strong>{{ "Date" | get_lang }}:</strong>
        </td>
        <td>
            {{ pdf_date }}
        </td>
    </tr>
    {% endif %}
</table>
<br />

{% if show_grade_generated_date == true %}
<h5 align="right">
    {{ 'GradeGeneratedOnX' | get_lang | format("now"| date("d/m/Y")) }}
</h5>
{% endif %}

{{ pdf_content }}

{% if not add_signatures is empty %}
    <br />
    <br />

    <table class="full-width">
        <tr>
            {% for signature in add_signatures %}
                <td class="text-center">
                    _____________________________
                    <br />
                    {{ signature|get_lang }}
                </td>
            {% endfor %}
        </tr>
    </table>
{% endif %}

