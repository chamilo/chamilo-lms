<h4>{{ 'UserRegistered'|get_lang }}</h4>
<ul>
    {% if is_western_name_order %}
        <li>{{ 'FirstName'|get_lang }}: {{ user.getFirstName() }}</li>
        <li>{{ 'LastName' }}: {{ user.getLastName() }}</li>
    {% else  %}
        <li>{{ 'LastName' }}: {{ user.getLastName() }}</li>
        <li>{{ 'FirstName' }}: {{ user.getFirstName() }}</li>
    {% endif %}
    <li>{{ 'Email'|get_lang }}: {{ user.getEmail() }}</li>
    <li>{{ 'Status'|get_lang }}: {{ user.getStatus() }}</li>
</ul>
<p>{{ 'ManageUser'|get_lang }}: <a href="{{ manageUrl }}">{{ manageUrl }}</a></p>
