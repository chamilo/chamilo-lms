<div id="menu" class="menu well">
	<div class="menusection">
		<span class="menusectioncaption">
		{"Login"|get_lang}
		</span>	
	</div>
	
	{$login_language_form}
    {if !empty($plugin_login_top)}                
        <div id="plugin_login_top">
            {$plugin_login_top}
        </div>
    {/if}	
	{$login_form}
	{$login_failed}	
	{$login_options}	
    
    {if !empty($plugin_login_bottom)}                
        <div id="plugin_login_bottom">
            {$plugin_login_bottom}
        </div>
    {/if}
</div>