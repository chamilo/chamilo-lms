<div id="login_block" class="well sidebar-nav">
        
	{{ login_language_form }}
        
    {% if plugin_login_top is not null %}
        <div id="plugin_login_top">
            {{ plugin_login_top }}
        </div>
    {% endif %}
    
	{{login_form}}
        
	{{login_failed}}
        
	{{login_options}} 
       
    {% if plugin_login_bottom is not null %}        
        <div id="plugin_login_bottom">
            {{ plugin_login_bottom }}
        </div>
    {% endif %}
</div>