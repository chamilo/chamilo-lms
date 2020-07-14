<div class="row">
    <div class="col-md-12">
        <div class="meets">
            <div class="">
                <div class="plugin_logo">
                    <img alt="" class="img-responsive" src="{{ _p.web_plugin }}google_meet/resources/img/svg/meet_room.svg">
                </div>
                <div class="tools text-center">
                    {% if is_admin or is_teacher %}
                        <a href="{{ url_add_room }}" class="btn btn-success btn-add-meet">
                            <i class="fa fa-video-camera" aria-hidden="true"></i>
                            {{ 'ManageMeetAccounts'|get_plugin_lang('GoogleMeetPlugin') }}
                        </a>
                    {% endif %}
                </div>
                {% if meets %}
                    <div class="meet-list">
                        {% for meet in meets %}
                            <div class="meet-item">
                                <div class="info-meet">
                                    <div class="card card-meet" style="border-left:  .25rem solid {{ meet.meet_color }}; background: {{ meet.meet_color }}14">
                                        <div class="card-body">
                                            <div class="row-meet">
                                                <div class="card-info">
                                                    <div style="background: {{ meet.meet_color }}" class="icon icon-shape text-white rounded-circle shadow">
                                                        <i class="fa fa-video-camera" aria-hidden="true"></i>
                                                    </div>
                                                    <div class="description">
                                                        <h4 class="title" title="{{ meet.meet_name }}"> {{ meet.meet_name }} </h4>
                                                        <div class="text">
                                                            {{ meet.meet_description }}
                                                        </div>
                                                        <a class="btn btn-sm btn-block btn-meet" target="_blank" href="{{ meet.meet_url }}">
                                                            <i class="fa fa-share" aria-hidden="true"></i>
                                                            {{ 'AccessMeeting'|get_plugin_lang('GoogleMeetPlugin') }}
                                                        </a>
                                                    </div>
                                                    <div class="float-right">
                                                        {% if is_admin or is_teacher %}
                                                        <div class="btn-group btn-group-xs" role="group" aria-label="...">
                                                            <a class="btn btn-sm btn-default" href="meets.php?action=edit&id_meet={{ meet.id }}&{{ _p.web_cid_query }}">
                                                                <i class="fa fa-pencil" aria-hidden="true"></i>
                                                            </a>
                                                            <a class="btn btn-sm btn-default"
                                                               onclick="javascript:if(!confirm('{{ 'AreYouSureToDelete' | get_lang }}')) return false;"
                                                               href="meets.php?action=delete&id_meet={{ meet.id }}&{{ _p.web_cid_query }}">
                                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                                            </a>
                                                        </div>
                                                        {% endif %}
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
                        {{ 'CourseDoesNotHaveAccountGoogleMeet'|get_plugin_lang('GoogleMeetPlugin') }}
                    </div>
                {% endif %}
            </div>
        </div>
    </div>
</div>