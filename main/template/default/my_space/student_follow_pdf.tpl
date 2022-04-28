<div style="position: absolute; top: 40px; right: 50px; width: 250px">
    {{ logo }}
</div>
<h1>{{ 'StudentDetails'|get_lang }}</h1>

<div class="user-info">
    <div class="user-infor__col user-info__col">
        <img src="{{ user_info.avatar }}" alt="{{ user_info.complete_name }}">
        <h2>{{ user_info.complete_name }} <small>{{ user_info.username }}</small></h2>
        <p>{{ user_info.email }}</p>
    </div>
    <div class="user-info__col user-info__col">
        <dl>
            <dt>{{ 'Status'|get_lang }}</dt>
            <dd>{{ user_info.status }}</dd>
            <dt>{{ 'OfficialCode'|get_lang }}</dt>
            <dd>{{ user_info.official_code }}</dd>
            <dt>{{ 'Tel'|get_lang }}</dt>
            <dd>{{ user_info.phone }}</dd>

            {% if user_info.timezone %}
                <dt>{{ 'Timezone'|get_lang }}</dt>
                <dd>{{ user_info.timezone }}</dd>
            {% endif %}
        </dl>
    </div>
    <div class="user-info__col user-info__col">
        <dl>
            <dt>{{ 'FirstLoginInPlatform'|get_lang }}</dt>
            <dd>{{ user_info.first_login }}</dd>
            <dt>{{ 'LatestLoginInPlatform'|get_lang }}</dt>
            <dd>{{ user_info.last_login }}</dd>
            <dt>{{ 'LatestLoginInPlatform'|get_lang  }}</dt>
            <dd>{{ user_info.last_course_connection }}</dd>
        </dl>
    </div>

    <hr>
</div>

{{ careers }}

{{ skills }}

<br><br>

{{ classes }}

{% for course_info in courses_info %}
    <pagebreak>

    {{ course_info }}
{% endfor %}
