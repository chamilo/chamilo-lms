<div class="row">
    <div class="col-md-12">
        <div class="section-profile"><i class="fa fa-square"></i> {{ 'Profile'|get_lang }}</div>
    </div>
</div>
<div class="row">
    <div class="col-md-7">
        <div class="block">
            <dl class="dl-horizontal">
                <dt>{{ "Email" | get_lang }}</dt>
                <dd>{{ user.email}}</dd>

                {% for extra_field in user.extra %}
                    <dt>{{ extra_field.value.getField().getDisplayText() }}</dt>
                    <dd>
                        {% if extra_field.option %}
                            {{ extra_field.option.getDisplayText() }}
                        {% else %}
                            {{ extra_field.value.getValue() }}
                        {% endif %}
                    </dd>
                {% endfor %}
            </dl>
            <div class="tool-profile">
                <a href="{{ profileEditionLink }}" class="btn btn-press btn-sm">{{ "EditProfile" | get_lang }}</a>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="profile-user">
            <div class="username">{{ user.complete_name }}</div>
            {{ socialAvatarBlock }}
            <div class="points">189 Puntos</div>
        </div>
    </div>
</div>
