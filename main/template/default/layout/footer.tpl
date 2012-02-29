<div id="footer"> <!-- start of #footer section -->	
    <div class="container">        
        <div class="copyright">		
            {if $administrator_name }
                <div class="admin_name">
                    {$administrator_name}            
                </div>
            {/if}            
            <div class="software_name">	    	
                {"Platform"|get_lang} <a href="{$_p.web}" target="_blank">{$_s.software_name} {$_s.system_version}</a>
                &copy; {$smarty.now|date_format:"%Y"}	    	
            </div> 
        </div>
            
        <div class="footer_emails">	
            {if $session_teachers}
                <div class="session_teachers">
                    {$session_teachers}            
                </div>
            {/if} 
            
            {if $teachers }
                <div class="teachers">
                    {$teachers}            
                </div>
            {/if} 
            
            {*  Plugins for footer section *}		
            <div id="plugin-footer">
                {$plugin_footer}
            </div>		
            <div style="clear:both"></div>
        </div>
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