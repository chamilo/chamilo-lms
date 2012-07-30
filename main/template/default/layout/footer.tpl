<footer> <!-- start of #footer section -->	    
    <div class="container">
        <div class="row">            
            <div id="footer_left" class="span4">                
                {% if session_teachers is not null %}
                    <div id="session_teachers">
                        {{ session_teachers }}           
                    </div>
                {% endif %}

                {% if teachers is not null %}
                    <div id="teachers">
                        {{ teachers }}            
                    </div>
                {% endif %}
                
                {#  Plugins for footer section #}                                             
                {% if plugin_footer_left is not null %}
                    <div id="plugin_footer_left">
                        {{ plugin_footer_left }}               
                    </div>                
                {% endif %}
                 &nbsp;
            </div>
            
            <div id="footer_center" class="span4">
                {#   Plugins for footer section  #}		
                {% if plugin_footer_center is not null %}
                    <div id="plugin_footer_center">
                        {{ plugin_footer_center }}                
                    </div>
                {% endif %}
                 &nbsp;
            </div>
            
            <div id="footer_right" class="span4">                
                {% if administrator_name is not null %}
                    <div id="admin_name">
                        {{ administrator_name }}          
                    </div>
                {% endif %}
                
                <div id="software_name">	    	
                    {{ "Platform" | get_lang }} <a href="{{_p.web}}" target="_blank">{{_s.software_name}} {{_s.system_version}}</a>
                    &copy; {{ "now"|date("Y") }}   	
                </div>
                {#   Plugins for footer section  #}		
                {% if plugin_footer_right is not null %}                                
                    <div id="plugin_footer_right">
                        {{ plugin_footer_right }}                
                    </div>                
                {% endif %}
                &nbsp;
            </div><!-- end of #footer_right -->
        </div><!-- end of #row -->        
    </div><!-- end of #container -->
</footer>

{{ footer_extra_content }}

{% raw %}
<script>
/* Makes row highlighting possible */
$(document).ready( function() {
    //Chosen select
    $(".chzn-select").chosen();     
    
    //Table highlight
    $("form .data_table input:checkbox").click(function() {
        if ($(this).is(":checked")) {
            $(this).parentsUntil("tr").parent().addClass("row_selected");
                        
        } else {
            $(this).parentsUntil("tr").parent().removeClass("row_selected");
        }    
    });
    
    //Tool tip (in exercises)
    var tip_options = {
        placement : 'right'
    }
    $('.boot-tooltip').tooltip(tip_options);
   
});
</script>
{% endraw %}
{{ execution_stats }}