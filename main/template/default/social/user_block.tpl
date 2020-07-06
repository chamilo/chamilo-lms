<div class="sidebar-avatar">
    <div class="panel-group" id="sn-avatar" role="tablist" aria-multiselectable="true">
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="heading-sn">
                <h4 class="panel-title">
                    <a role="button" data-toggle="collapse" data-parent="#sn-avatar"
                       href="#sn-avatar-one" aria-expanded="true" aria-controls="sn-avatar-one">
                    {{ "Profile" | get_lang }}
                    </a>
                    {% if _u.is_admin == 1 %}
                        <div class="pull-right">
                            <a class="btn btn-default btn-sm btn-social-edit"
                               title="{{ "Edit"|get_lang }}"
                               href="{{ _p.web }}main/admin/user_edit.php?user_id={{ user.id }}"
                            >
                                <i class="fa fa-pencil" aria-hidden="true"></i>
                            </a>
                        </div>
                    {% endif %}
                </h4>
            </div>
            <div id="sn-avatar-one" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="heading-sn">
                <div class="panel-body">
                    <div class="area-avatar">
                        {{ social_avatar_block }}
                        {% if user.icon_status %}
                            <!-- User icon -->
                            <div class="avatar-icon">
                                {{ user.icon_status_medium }}
                            </div>
                            <!-- End user icon -->
                        {% endif %}

                        {% if show_language_flag %}
                        <!-- Language flag -->
                        <div class="avatar-lm">
                            {% if user.language %}
                                {% if user.language.code == 'fr' %}
                                    <img src="{{ _p.web }}web/assets/flag-icon-css/flags/4x3/fr.svg" width="36px">
                                {% elseif user.language.code == 'de' %}
                                    <img src="{{ _p.web }}web/assets/flag-icon-css/flags/4x3/de.svg" width="36px">
                                {% elseif user.language.code == 'es' %}
                                    <img src="{{ _p.web }}web/assets/flag-icon-css/flags/4x3/es.svg" width="36px">
                                {% elseif user.language.code == 'it' %}
                                    <img src="{{ _p.web }}web/assets/flag-icon-css/flags/4x3/it.svg" width="36px">
                                {% elseif user.language.code == 'pl' %}
                                    <img src="{{ _p.web }}web/assets/flag-icon-css/flags/4x3/pl.svg" width="36px">
                                {% endif %}
                            {% endif %}
                        </div>
                        <!-- End language flag -->

                        <!-- Language cible -->
                        <div class="avatar-lc">
                            {% for item in extra_info %}
                                {% if item.variable == 'langue_cible' %}
                                    {% if item.value == 'French2' %}
                                        <img src="{{ _p.web }}web/assets/flag-icon-css/flags/4x3/fr.svg" width="36px">
                                    {% elseif item.value == 'German2' %}
                                        <img src="{{ _p.web }}web/assets/flag-icon-css/flags/4x3/de.svg" width="36px">
                                    {% elseif item.value == 'Spanish' %}
                                        <img src="{{ _p.web }}web/assets/flag-icon-css/flags/4x3/es.svg" width="36px">
                                    {% elseif item.value == 'Italian' %}
                                        <img src="{{ _p.web }}web/assets/flag-icon-css/flags/4x3/it.svg" width="36px">
                                    {% elseif item.value == 'Polish' %}
                                        <img src="{{ _p.web }}web/assets/flag-icon-css/flags/4x3/pl.svg" width="36px">
                                    {% elseif item.value == 'English' %}
                                        <img src="{{ _p.web }}web/assets/flag-icon-css/flags/4x3/gb.svg" width="36px">
                                    {% endif %}
                                {% endif %}
                            {% endfor %}
                        </div>
                        <!-- End language cible -->
                        {% endif %}
                    </div>
                    {# Ofaj #}
                    <ul class="list-user-data">
                        <li class="item item-name">
                            <h5>{{ user.complete_name }} </h5>
                        </li>

                        {% if show_full_profile %}
                            {% if user.email %}
                            <li class="item">
                                <a href="{{ _p.web }}main/messages/new_message.php">
                                    {{ "sn-message.png"|img(22, "Email" | get_lang) }}
                                    <div class="email-overflow">{{ user.email }}</div>
                                </a>
                            </li>
                            {% endif %}

                            {% if vcard_user_link %}
                                <li class="item">
                                    <a href="{{ vcard_user_link }}">
                                        {{ "vcard.png"|img(22, "BusinessCard" | get_lang) }}
                                        {{ "BusinessCard" | get_lang }}
                                    </a>
                                </li>
                            {% endif %}

                            {% set skype_account = '' %}
                            {% set linkedin_url = '' %}
                            {% for extra in user.extra %}
                                {% if extra.value.getField().getVariable() == 'skype' %}
                                    {% set skype_account %}
                                    <a href="skype:{{ extra.value.getValue() }}?chat">
                                        <span class="fa fa-skype fa-fw" aria-hidden="true"></span> {{ 'Skype'|get_lang }}
                                    </a>
                                    {% endset %}
                                {% endif %}
                                {% if extra.value.getField().getVariable() == 'linkedin_url' %}
                                    {% set linkedin_url %}
                                        <a href="{{ extra.value.getValue() }}" target="_blank">
                                            <span class="fa fa-linkedin fa-fw" aria-hidden="true"></span> {{ 'LinkedIn'|get_lang }}
                                        </a>
                                    {% endset %}
                                {% endif %}
                            {% endfor %}

                            {% if 'allow_show_skype_account'|api_get_setting == 'true' and not skype_account is empty %}
                                <li class="item">
                                    {{ skype_account | remove_xss}}
                                </li>
                            {% endif %}

                            {% if 'allow_show_linkedin_url'|api_get_setting == 'true' and not linkedin_url is empty %}
                                <li class="item">
                                    {{ linkedin_url | remove_xss}}
                                </li>
                            {% endif %}
                        {% endif %}
                        {% if chat_enabled == 1 %}
                            {% if user.user_is_online_in_chat != 0 %}
                                {% if user_relation == user_relation_type_friend %}
                                    <li class="item">
                                        <a
                                            onclick="javascript:chatWith('{{ user.id }}', '{{ user.complete_name }}', '{{ user.user_is_online }}','{{ user.avatar_small }}')"
                                            href="javascript:void(0);"
                                        >
                                            <img src="{{ "online.png" | icon }}" alt="{{ "Online" | get_lang }}">
                                            {{ "Chat" | get_lang }} ({{ "Online" | get_lang }})
                                        </a>
                                    </li>
                                {% endif %}
                            {% endif %}
                        {% endif %}
                    <dl class="list-info">
                        {% for item in extra_info %}
                            {% if item.variable != 'langue_cible' %}
                            <dt>{{ item.label }}:</dt>
                            <dd>{{ item.value | remove_xss }}</dd>
                            {% endif %}
                        {% endfor %}
                    </dl>

                    {% if not profile_edition_link is empty %}
                        <li class="item">
                            <a class="btn btn-default btn-sm btn-block" href="{{ profile_edition_link }}">
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


