{% import 'default/macro/macro.tpl' as display %}
<div class="details">
    <div class="row">
        <div class="col-md-4">
            {% set content %}
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
            {% endset %}
            {{ display.panel('',content,'') }}
        </div>
        <div class="col-md-8">
            <div class="list-card">

                {{ display.card_widget('FirstLoginInPlatform'|get_lang ,user.first_connection ,'calendar') }}

                {{ display.card_widget('LatestLoginInPlatform'|get_lang ,user.last_connection ,'calendar') }}

                {{ display.card_widget('LegalAccepted'|get_lang ,user.legal.datetime ,'gavel' , user.legal.icon) }}
                
            </div>
            {% if social_tool %}
            <div class="list-box-widget">

                {{ display.box_widget('Friends'|get_lang ,user.social.friends ,'users') }}

                {{ display.box_widget('InvitationSent'|get_lang ,user.social.invitation_sent ,'paper-plane') }}

                {{ display.box_widget('InvitationReceived'|get_lang ,user.social.invitation_received ,'smile-o') }}

                {{ display.box_widget('WallMessagesPosted'|get_lang ,user.social.messages_posted ,'comments') }}

                {{ display.box_widget('MessagesSent'|get_lang ,user.social.messages_sent ,'envelope') }}

                {{ display.box_widget('MessagesReceived'|get_lang ,user.social.message_received ,'envelope-open-o') }}

            </div>
            {% endif %}

        </div>
    </div>
</div>