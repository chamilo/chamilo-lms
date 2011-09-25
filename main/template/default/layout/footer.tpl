<div id="footer"> <!-- start of #footer section -->
	<div id="bottom_corner"></div>
	<div class="copyright">		
	    {if $show_administrator_data == 'true'}
	        <div align="right">
	            {$administrator_name}            
	        </div>
	    {/if}    
	    <div align="right">	    	
	    	{"Platform"|get_lang} <a href="{$_p.web}" target="_blank">{$_s.software_name} {$_s.system_version}</a> &copy; {$smarty.now|date_format:"%Y"}	    	
	    </div>    
	</div>
	
	<div class="footer_emails">		
		{*  Plugins for footer section *}		
		<div id="plugin-footer">
			{$plugin_footer}
		</div>		
		<div style="clear:both"></div>
	</div>
</div> <!-- end of #footer -->
{$footer_extra_content}
{literal}
<script>
$(document).ready( function() {
    $(".chzn-select").chosen();
});
</script>
{/literal}
{$execution_stats}
