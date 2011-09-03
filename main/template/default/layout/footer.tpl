<div id="footer"> <!-- start of #footer section -->
	<div id="bottom_corner"></div>
	<div class="copyright">		
	    {if $show_administrator_data == 'true'}
	        <div align="right">
	            {$administrator_name}            
	        </div>
	    {/if}    
	    <div align="right">
	    	{$platform_name}
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

{literal}
<script>
$(document).ready( function() {
    $(".chzn-select").chosen();
});
</script>
{/literal}
{$execution_stats}