<script>
$(document).ready(function() {
$.ajax({    
url:'{{web_admin_ajax_url}}?a=version',
success:function(version){
$(".admin-block-version").html(version);
} 
});
});    
</script>

<div id="settings">
    <!--
    <ul>
        <li><a href="#tabs-1">Users</a></li>
        <li><a href="#tabs-2">Courses</a></li>
        <li><a href="#tabs-3">Platform</a></li>
        <li><a href="#tabs-4">Aenean lacinia</a></li>
        <li><a href="#tabs-5">Aenean lacinia</a></li>
        <li><a href="#tabs-6">Aenean lacinia</a></li>
        <li><a href="#tabs-7">Aenean lacinia</a></li>
        <li><a href="#tabs-8">Aenean lacinia</a></li>        
    </ul>
    -->        
    <div class="row">
    {% for block_item in blocks %}
        <div id="tabs-{{loop.index}}" class="span6">
            <div class="well_border">
                <h4>{{block_item.icon}} {{block_item.label}}</h4>                
                <div style="list-style-type:none">
                    {{ block_item.search_form }}
                </div>                           
                {% if block_item.items is not null %}
                    <ul>
    		    	{% for url in block_item.items %}
    		    		<li><a href="{{url.url}}">{{ url.label }}</a></li>	    	
    				{% endfor %}
                    </ul>    	
                {% endif %}
                
                {% if block_item.label == 'VersionCheck'|get_lang %}
                    <div class="admin-block-version"> 
                {% endif %}
                
                {% if block_item.label == 'VersionCheck'|get_lang %}
                    </div> 
                {% endif %}
                {% if block_item.extra is not null %}
                    <div>
                    {{ block_item.extra }}
                    </div>
                {% endif %}
                                
            </div>
        </div>        
    {% endfor %}
    </div>
</div>
