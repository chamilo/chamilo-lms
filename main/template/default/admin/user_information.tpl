<div class="details">
    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="user">
                        <div class="avatar">
                            <img width="128px" src="{{ user.avatar }}" class="img-responsive" >
                        </div>
                        <div class="name">
                            <h3>{{ user.complete_name }}</h3>
                            <p class="email">{{ user.email }}</p>
                        </div>
                        <div class="parameters">
                            <dl class="dl-horizontal">
                                <dt>{{ 'Tel'|get_lang }}</dt>
                                <dd>{{ user.phone == '' ? 'NoTel'|get_lang : user.phone }}</dd>
                                <dt>{{ 'OfficialCode'|get_lang }}</dt>
                                <dd>{{ user.official_code == '' ? 'NoOfficialCode'|get_lang : user.official_code }}</dd>
                                <dt>{{ 'OnLine'|get_lang }}</dt>
                                <dd>{{ user.user_is_online }}</dd>
                                <dt>{{ 'Status'|get_lang }}</dt>
                                <dd>{{ user.status }}</dd>
                            </dl>
                            <div class="create">{{ user.created }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="list-card">

                <div class="card card-first-date">
                    <div class="card-body">
                        <div class="stat-widget-five">
                            <div class="stat-icon">
                                <i class="fa fa-calendar" aria-hidden="true"></i>
                            </div>
                            <div class="stat-content">
                                <div class="text-left">
                                    <div class="stat-text">
                                        {{ user.first_connection }}
                                    </div>
                                    <div class="stat-heading">
                                        {{ 'FirstLoginInPlatform'|get_lang }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-last-date">
                    <div class="card-body">
                        <div class="stat-widget-five">
                            <div class="stat-icon">
                                <i class="fa fa-calendar" aria-hidden="true"></i>
                            </div>
                            <div class="stat-content">
                                <div class="text-left">
                                    <div class="stat-text">
                                        {{ user.last_connection }}
                                    </div>
                                    <div class="stat-heading">
                                        {{ 'LatestLoginInPlatform'|get_lang }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-legal">
                    <div class="card-body">
                        <div class="stat-widget-five">
                            <div class="stat-icon">
                                <i class="fa fa-gavel" aria-hidden="true"></i>
                                <span class="active-icon">{{ user.legal.icon }}</span>
                            </div>
                            <div class="stat-content">
                                <div class="text-left">
                                    <div class="stat-text">
                                        {{ user.legal.datetime }}
                                    </div>
                                    <div class="stat-heading">
                                        {{ 'LegalAccepted'|get_lang }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            {% if social_tool %}
            <div class="list-box-widget">

                <div class="card box-widget">
                    <div class="card-body">
                        <div class="stat-widget-five">
                            <i class="fa fa-users" aria-hidden="true"></i>
                            {{ user.social.friends }}
                            <div class="box-name">
                                {{ 'Friends'|get_lang }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card box-widget">
                    <div class="card-body">
                        <div class="stat-widget-five">
                            <i class="fa fa-paper-plane" aria-hidden="true"></i>
                            {{ user.social.invitation_sent }}
                            <div class="box-name">
                                {{ 'InvitationSent'|get_lang }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card box-widget">
                    <div class="card-body">
                        <div class="stat-widget-five">
                            <i class="fa fa-smile-o" aria-hidden="true"></i>
                            {{ user.social.invitation_received }}
                            <div class="box-name">
                                {{ 'InvitationReceived'|get_lang }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card box-widget">
                    <div class="card-body">
                        <div class="stat-widget-five">
                            <i class="fa fa-comments" aria-hidden="true"></i>
                            {{ user.social.messages_posted }}
                            <div class="box-name">
                                {{ 'WallMessagesPosted'|get_lang }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card box-widget">
                    <div class="card-body">
                        <div class="stat-widget-five">
                            <i class="fa fa-envelope" aria-hidden="true"></i>
                            {{ user.social.messages_posted }}
                            <div class="box-name">
                                {{ 'MessagesSent'|get_lang }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card box-widget">
                    <div class="card-body">
                        <div class="stat-widget-five">
                            <i class="fa fa-envelope-open-o" aria-hidden="true"></i>
                            {{ user.social.message_received }}
                            <div class="box-name">
                                {{ 'MessagesReceived'|get_lang }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            {% endif %}

        </div>
    </div>
</div>