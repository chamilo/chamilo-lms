<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="plugin_logo">
                    <img alt="" class="img-responsive" src="{{ _p.web }}plugin/googlemeet/resources/img/svg/meet_room.svg">
                </div>

                <div class="tools text-center">
                    {% if is_admin or is_teacher %}
                        <a href="{{ url_add_room }}" class="btn btn-primary">
                            <i class="fa fa-video-camera" aria-hidden="true"></i>
                            {{ 'ManageMeetAccounts'|get_plugin_lang('GoogleMeetPlugin') }}
                        </a>
                    {% endif %}
                    {% if is_admin or is_teacher %}
                        {% if room %}
                            <a href="{{ url_change_room }}" class="btn btn-danger">
                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                {{ 'RemoveAndChangeRoomMeet'| get_plugin_lang('GoogleMeetPlugin') }}
                            </a>
                        {% endif %}
                    {% endif %}
                </div>


                {% if room %}
                    <div class="alert alert-info" role="alert">
                        {{ 'CourseDoesNotHaveAssociatedAccountMeet'|get_plugin_lang('GoogleMeetPlugin') }}
                    </div>
                    <div class="info-room">
                        <div class="well">
                            <dl class="dl-horizontal">
                                <dt>{{ 'MeetingIDZoom'|get_plugin_lang('GoogleMeetPlugin') }}</dt>
                                <dd>{{ room.room_id }}</dd>
                                <dt>{{ 'RoomNameZoom'|get_plugin_lang('GoogleMeetPlugin') }}</dt>
                                <dd>{{ room.room_name }}</dd>
                                <dt>{{ 'InstantMeetingURL'|get_plugin_lang('GoogleMeetPlugin') }}</dt>
                                <dd>
                                    <a href="{{ room.room_url }}" target="_blank">
                                        {{ room.room_url }}
                                    </a>
                                </dd>
                                {% if room.room_pass %}
                                    <dt>{{ 'HostKey'|get_plugin_lang('GoogleMeetPlugin') }}</dt>
                                    <dd>{{ room.room_pass }}</dd>
                                {% endif %}

                                {% if is_teacher %}
                                    {% if not view_pass %}
                                        <dt>{{ 'AccountEmailZoom'|get_plugin_lang('GoogleMeetPlugin') }}</dt>
                                        <dd>{{ room.zoom_email }}</dd>
                                        <dt>{{ 'Password'|get_lang }}</dt>
                                        <dd>{{ room.zoom_pass }}</dd>
                                    {% endif %}
                                {% endif %}
                            </dl>
                            <div class="text-center">
                                <a href="{{ room.room_url }}" target="_blank" class="btn btn-success btn-lg">
                                    <i class="fa fa-video-camera" aria-hidden="true"></i>
                                    {{ 'JoinTheMeetingZoom' | get_plugin_lang('GoogleMeetPlugin') }}
                                </a>
                            </div>
                        </div>
                    </div>
                {% else %}
                    <div class="alert alert-warning" role="alert">
                        {{ 'CourseDoesNotHaveAssociatedAccountMeet'|get_plugin_lang('GoogleMeetPlugin') }}
                    </div>
                    {% if is_student %}
                        <div class="alert alert-info" role="alert">
                            {{ 'MessageMeetingZoom'|get_plugin_lang('GoogleMeetPlugin') }}
                        </div>
                    {% endif %}
                {% endif %}

                {{ form_zoom }}

            </div>
        </div>
    </div>
</div>