<div class ="row">
    
<div class ="span12" style="text-align:center">
    
    <a href="{{ conference_url }}" class="btn btn-primary btn-large">
        {{ 'EnterTheConference'|get_lang }} 	
    </a>
    <span id="users_online" class="label label-warning">{{ users_online }} user online</span>
</div>
<div class ="span12">    
<table class="table">
    <tr>
        <th>{{'id'|get_lang}}</th>
        <th>{{'Meeting'|get_lang}}</th>        
        <th>{{'Date'|get_lang}}</th>   
        <th>{{'Actions'|get_lang}}</th>
    </tr>
{% for meeting in meetings %}
    <tr>
        <td>{{ meeting.id }}</td>
        <td>{{ meeting.meeting_name }}</td>        
        <td>{{ meeting.created_at }}</td>                
        <td>                      
            {% if meeting.record == 1 %}
                {{ meeting.show_links }}
                <!-- <a href="{{ meeting.publish_url}} "> Publish </a>
                <a href="{{ meeting.unpublish_url}} "> UnPublish </a> -->
            {% endif %}
            
            {% if meeting.status == 1 %}                
                <a href="{{ meeting.end_url }} "> Close </a>                
            {% endif %}
                
            
        </td>
    </tr>
{% endfor %}
</table>
</div>
</div>