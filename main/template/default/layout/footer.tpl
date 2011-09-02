<div id="footer"> <!-- start of #footer section -->
	<div id="bottom_corner"></div>
	<div class="copyright">
	    {if $show_administrator_data == 'true'}
	        {* Platform manager *}
	        <div align="right">
	            {$administrator_data}            
	        </div>
	    {/if}    
	    <div align="right">
	    	{$platform}
	    </div>    
	</div>  {* //copyright div *}
	
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