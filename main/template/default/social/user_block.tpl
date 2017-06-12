<div class="sidebar-avatar">
    <div class="panel-group" id="sn-avatar" role="tablist" aria-multiselectable="true">
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="heading-sn">
                <h4 class="panel-title">
                    <a role="button" data-toggle="collapse" data-parent="#sn-avatar" href="#sn-avatar-one" aria-expanded="true" aria-controls="sn-avatar-one">
                    {{ "Profile" | get_lang }}
                    </a>
                </h4>
            </div>
            <div id="sn-avatar-one" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="heading-sn">
                <div class="panel-body">
                    {{ social_avatar_block }}
                    <ul class="list-user-data">
                        <li class="item">
                            {{ user.complete_name }}
                        </li>
                        {% if show_full_profile  %}
                            <li class="item">
                                <a href="{{ _p.web }}main/messages/new_message.php">
                                <img src="{{ "instant_message.png" | icon }}" alt="{{ "Email" | get_lang }}">
                                {{ user.email}}
                                </a>
                            </li>
                            <li class="item">
                                <a href="{{ vcard_user_link }}">
                                <img src="{{ "vcard.png" | icon(16) }}" alt="{{ "BusinessCard" | get_lang }}" width="16" height="16">
                                {{ "BusinessCard" | get_lang }}
                                </a>
                            </li>

                            {% set skype_account = '' %}
                            {% set linkedin_url = '' %}
                            {% for extra in user.extra %}
                                {% if extra.value.getField().getVariable() == 'skype' %}
                                    {% set skype_account = extra.value.getValue() %}
                                {% endif %}

                                {% if extra.value.getField().getVariable() == 'linkedin_url' %}
                                    {% set linkedin_url = extra.value.getValue() %}
                                {% endif %}
                            {% endfor %}

                            {% if 'allow_show_skype_account'|api_get_setting == 'true' and not skype_account is empty %}
                                <li class="item">
                                    <a href="skype:{{ skype_account }}?chat">
                                        <span class="fa fa-skype fa-fw" aria-hidden="true"></span> {{ 'Skype'|get_lang }}
                                    </a>
                                </li>
                            {% endif %}

                            {% if 'allow_show_linkedin_url'|api_get_setting == 'true' and not linkedin_url is empty %}
                                <li class="item">
                                    <a href="{{ linkedin_url }}" target="_blank">
                                        <span class="fa fa-linkedin fa-fw" aria-hidden="true"></span> {{ 'LinkedIn'|get_lang }}
                                    </a>
                                </li>
                            {% endif %}
                        {% endif %}
                        {% if chat_enabled == 1 %}
                            {% if user.user_is_online_in_chat != 0 %}
                                {% if user_relation == user_relation_type_friend %}
                                    <li class="item">
                                        <a onclick="javascript:chatWith('{{ user.id }}', '{{ user.complete_name }}', '{{ user.user_is_online }}','{{ user.avatar_small }}')" href="javascript:void(0);">
                                            <img src="{{ "online.png" | icon }}" alt="{{ "Online" | get_lang }}">
                                            {{ "Chat" | get_lang }} ({{ "Online" | get_lang }})
                                        </a>
                                    </li>
                                {# else #}
                                    {# <img src="{{ "offline.png" | icon }}" alt="{{ "Online" | get_lang }}"> #}
                                    {# {{ "Chat" | get_lang }} ({{ "Offline" | get_lang }}) #}
                                {% endif %}
                            {% endif %}
                        {% endif %}

                    {% if not profile_edition_link is empty %}
                    <li class="item">
                        <a class="btn btn-link btn-sm btn-block" href="{{ profile_edition_link }}">
                        <em class="fa fa-edit"></em>{{ "EditProfile" | get_lang }}
                        </a>
                    </li>
                    {% endif %}
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>


