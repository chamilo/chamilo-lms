<div class ="row">
    
{% if bbb_status == true %}    
    
    <div class ="span12" style="text-align:center">
        <a href="{{ conference_url }}" class="btn btn-primary btn-large">
            {{ 'StartConference'|get_lang }} 	
        </a>
        <span id="users_online" class="label label-warning">{{ 'XUsersOnLine'| get_lang | format(users_online) }} </span>
    </div>

    <div class ="span12">    

        <div class="page-header">
            <h2>{{ 'RecordList'|get_lang }}</h2>
        </div>

        <table class="table">
            <tr>
                <th>#</th>                
                <th>{{'CreatedAt'|get_lang}}</th>
                <th>{{'Status'|get_lang}}</th>
                <th>{{'Records'|get_lang}}</th>
                <th>{{'Actions'|get_lang}}</th>
            </tr>    
            {% for meeting in meetings %}
            <tr>
                <td>{{ meeting.id }}</td>                     
                <td>{{ meeting.created_at }}</td>        
                <td>
                    {% if meeting.status == 1 %}                
                        <span class="label label-success">{{'MeetingOpened'|get_lang}}</span>
                    {% else %}
                        <span class="label label-info">{{'MeetingClosed'|get_lang}}</span>
                    {% endif %}
                </td>                
                <td>                      
                    {% if meeting.record == 1 %}
                        {# Record list #}
                        {{ meeting.show_links }}
                        <!-- <a href="{{ meeting.publish_url}} "> Publish </a>
                        <a href="{{ meeting.unpublish_url}} "> UnPublish </a> -->
                    {% endif %}
                </td>
                <td>
                {% if meeting.status == 1 %}                                        
                    <a class="btn" href="{{ meeting.end_url }} "> {{'CloseMeeting'|get_lang}}</a>                    
                {% endif %}
                </td>
            </tr>
            {% endfor %}
        </table>
    </div>
{% else %} 
    <div class ="span12" style="text-align:center">
        {{ 'ServerIsNotRunning' | return_message('warning') }}
    </div>
{% endif %}     
</div>