{% import 'default/macro/macro.tpl' as display %}

<div class="details">
    <div class="row">
        <div class="col-md-4">
            {{ display.panel('', display.reporting_user_box(user), '') }}
        </div>
        <div class="col-md-8">
            <div class="list-card">
                {{ display.card_widget('FirstLoginInPlatform'|get_lang, user.first_connection, 'calendar') }}
                {{ display.card_widget('LatestLoginInPlatform'|get_lang, user.last_connection, 'calendar') }}
                {{ display.card_widget('LatestLoginInAnyCourse'|get_lang, user.last_connection_in_course, 'calendar') }}
                {% if user.legal %}
                    {{ display.card_widget('LegalAccepted'|get_lang, user.legal.datetime, 'gavel', user.legal.icon) }}
                {% endif %}
            </div>
            {% if social_tool %}
                <div class="list-box-widget">
                    {{ display.box_widget('Friends'|get_lang, user.social.friends, 'users') }}
                    {{ display.box_widget('InvitationSent'|get_lang, user.social.invitation_sent, 'paper-plane') }}
                    {{ display.box_widget('InvitationReceived'|get_lang, user.social.invitation_received, 'smile-o') }}
                    {{ display.box_widget('WallMessagesPosted'|get_lang, user.social.messages_posted, 'comments') }}
                    {{ display.box_widget('MessagesSent'|get_lang, user.social.messages_sent, 'envelope') }}
                    {{ display.box_widget('MessagesReceived'|get_lang, user.social.message_received, 'envelope-open-o') }}
                </div>
            {% endif %}
        </div>
    </div>
</div>
