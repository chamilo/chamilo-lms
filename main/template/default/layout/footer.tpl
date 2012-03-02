<div id="footer"> <!-- start of #footer section -->	    
    <div class="container">  
        <div class="row">            
            <div id="footer_left" class="span4">
                {if $session_teachers}
                    <div id="session_teachers">
                        {$session_teachers}            
                    </div>
                {/if} 

                {if $teachers }
                    <div id="teachers">
                        {$teachers}            
                    </div>
                {/if}
                {*  Plugins for footer section *}		
                {if !empty($plugin_footer_left)}                                
                    <div id="plugin_footer_left">
                        {$plugin_footer_left}                
                    </div>                
                {/if}
                &nbsp;
            </div>
            
            <div id="footer_center" class="span4">
                {*  Plugins for footer section *}		
                {if !empty($plugin_footer_center)}                                
                    <div id="plugin_footer_center">
                        {$plugin_footer_center}                
                    </div>                
                {else} 
                    &nbsp;
                {/if}
            </div>
            
            <div id="footer_right" class="span4">        
                {if $administrator_name }
                    <div id="admin_name">
                        {$administrator_name}            
                    </div>
                {/if}
                
                <div id="software_name">	    	
                    {"Platform"|get_lang} <a href="{$_p.web}" target="_blank">{$_s.software_name} {$_s.system_version}</a>
                    &copy; {$smarty.now|date_format:"%Y"}	    	
                </div>
                {*  Plugins for footer section *}		
                {if !empty($plugin_footer_right)}                                
                    <div id="plugin_footer_right">
                        {$plugin_footer_right}                
                    </div>                
                {/if}
                &nbsp;
            </div><!-- end of #footer_right -->
        </div><!-- end of #row -->        
    </div><!-- end of #container -->
</div><!-- end of #footer -->

{$footer_extra_content}

{literal}
<script type="text/javascript">
$(document).ready( function() {
    $(".chzn-select").chosen();     
    $("form .data_table input:checkbox").click(function() {
        if ($(this).is(":checked")) {
            $(this).parentsUntil("tr").parent().addClass("row_selected");
                        
        } else {
            $(this).parentsUntil("tr").parent().removeClass("row_selected");
        }    
    });
});
</script>
{/literal}
{$execution_stats}