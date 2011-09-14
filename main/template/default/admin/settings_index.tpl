
{foreach $blocks as $key => $block }
	<div class="admin_section">
	<h4>{$block.icon} {$block.label}</h4>
		<div style="list-style-type:none">
			{$block.search_form}
		</div>
		{if $block.items}
	    	<ul>
		    	{foreach $block.items as $url}
		    		<li><a href="{$url.url}">{$url.label}</a></li>	    	
				{/foreach}
			</ul>    	
    	{/if}
    </div>
{/foreach}