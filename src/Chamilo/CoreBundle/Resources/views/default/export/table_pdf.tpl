{{ organization }}

<h2 align="center"> {{ pdf_title }} </h2>

{% if pdf_description != null %}
    {{ pdf_description }}
    <br /><br />
{% endif %}

<table align="center" width="100%">
    <tr>
        <td>
         <strong>{{ "Teacher" | trans }}:</strong> {{ pdf_teachers }}
        </td>
    </tr>
    {% if pdf_session != null %}
    <tr>
        <td>
          <strong>{{ "Session" | trans }}:</strong> {{ pdf_session }}
        </td>
    </tr>
    {% endif %}
    <tr>
        <td>
         <strong>{{ "Course" | trans }}:</strong> {{ pdf_course }}

         {% if pdf_course_category %}
            <strong>{{ "Category" | trans }}:</strong> {{ pdf_course_category }}
         {% endif %}

        </td>
    </tr>
    <tr>
        <td>
         <strong>{{ "Date" | trans }}:</strong> {{ pdf_date }}
        </td>
    </tr>
</table>

<br />

{{ pdf_content }}

{% if add_signatures == true %}
    <br />
    <br />

    <table style="text-align:center" width="100%">
        <tr>
            <td>
                _____________________________
                <br />
                {{ "Drh" | trans }}
            </td>
            <td>
                _____________________________
                <br />
                {{ "Teacher" | trans }}
                </td>
            <td>
                _____________________________
                <br />
                {{ "Date" | trans }}
            </td>
        </tr>
    </table>
{% endif %}
