{{ organization }}

<h2 align="center"> {{ pdf_title }} </h2>

{% if pdf_description != null %}
    {{ pdf_description }}
    <br /><br />
{% endif %}

<table align="center" width="100%">
    <tr>
        <td>
         <strong>{{ "Teacher" | get_lang }}:</strong> {{ pdf_teachers }}
        </td>
    </tr>    
    {% if pdf_session != null %}
    <tr>
        <td>
          <strong>{{ "Session" | get_lang }}:</strong> {{ pdf_session }}
        </td>
    </tr>
    {% endif %}    
    <tr>
        <td>
         <strong>{{ "Course" | get_lang }}:</strong> {{ pdf_course }}
        </td>
    </tr>    
    <tr>
        <td>
         <strong>{{ "Date" | get_lang }}:</strong> {{ pdf_date }}
        </td>
    </tr>    
</table>

<br />

{{ pdf_table }}

{% if add_signatures == true %}
    <br />
    <br />

    <table style="text-align:center" width="100%">
        <tr>
            <td>
                _____________________________
                <br />
                {{ "Drh" | get_lang }}
            </td>
            <td>
                _____________________________
                <br />
                {{ "Teacher" | get_lang }}
                </td>
            <td>
                _____________________________
                <br />
                {{ "Date" | get_lang }}        
            </td>
        </tr>
    </table>
{% endif %}