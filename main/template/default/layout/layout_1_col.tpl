{extends file="default/layout/main.tpl"}
{* Header *}
{block name="header"}
{if $show_header}
{include file="default/layout/main_header.tpl"}
{/if}	
{/block}
{* 1 column *}
{block name=body}
    <div class="span12">        
        {include file="default/layout/page_body.tpl"}
        {* Content bottom *}

        {if !empty($plugin_content_bottom)}   
            <div class="clear"></div>
            <div id="plugin_content_bottom">
                {$plugin_content_bottom}
            </div>
        {/if}
    </div>    
{/block}

{* Footer *}
{block name=footer}
	{if $show_footer == 1}
		{include file="default/layout/main_footer.tpl"}
	{/if}	
{/block}