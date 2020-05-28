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
                </div>

                {% if meets %}
                    <div class="row">
                        {% for meet in meets %}
                            <div class="col-md-4">
                                <div class="info-meet">
                                    <div class="card card-meet">
                                        <div class="card-body">
                                            <div class="row-meet">
                                                <div class="col-auto">
                                                    <div class="icon icon-shape bg-gradient-green text-white rounded-circle shadow">
                                                        <i class="fa fa-video-camera" aria-hidden="true"></i>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="description">
                                                        <h4 class="title"> {{ meet.meet_name }} </h4>
                                                        <a class="btn btn-sm btn-meet" target="_blank" href="{{ meet.meet_url }}">
                                                            <i class="fa fa-share" aria-hidden="true"></i>
                                                            {{ 'AccessMeeting'|get_plugin_lang('GoogleMeetPlugin') }}
                                                        </a>
                                                    </div>
                                                    <div class="float-right">
                                                        <div class="btn-group btn-group-xs" role="group" aria-label="...">
                                                            <a class="btn btn-sm btn-default" target="_blank" href="{{ meet.meet_url }}">
                                                                <i class="fa fa-pencil" aria-hidden="true"></i>
                                                            </a>
                                                            <a class="btn btn-sm btn-default" target="_blank" href="{{ meet.meet_url }}">
                                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                                            </a>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        {% endfor %}
                    </div>

                {% else %}
                    <div class="alert alert-warning" role="alert">
                        {{ 'CourseDoesNotHaveAssociatedAccountMeet'|get_plugin_lang('GoogleMeetPlugin') }}
                    </div>
                {% endif %}
            </div>
        </div>
    </div>
</div>