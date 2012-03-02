<div id="menu" class="menu well">
	<div class="menusection">
		<span class="menusectioncaption">
		{"Login"|get_lang}
		</span>	
	</div>
	{$login_language_form}
	{$login_form}
	{$login_failed}	
	{$login_options}	
    
    {if !empty($plugin_login)}                
        <div id="plugin_login">
            {$plugin_login}
        </div>
    {/if}
</div>