{extends file="default/layout/main.tpl"}
{* Header *}
{block name="header"}
{if $show_header}
{include file="default/layout/main_header.tpl"}
{/if}	
{/block}
{* 1 column *}
{block name=body}
    {* Plugin top *}

    {if !empty($plugin_content_top)}         
        <div id="plugin_content_top" class="span12">
            {$plugin_content_top}
        </div>
    {/if}
    
    
    <div class="span12">            
        {include file="default/layout/page_body.tpl"}
        {if !empty($content)}
            <section id="main_content">
            {$content}
            &nbsp;
            </section>
        {/if}        
    </div>    
    
    {* Plugin bottom *}

    {if !empty($plugin_content_bottom)}               
        <div id="plugin_content_bottom" class="span12">
            {$plugin_content_bottom}
        </div>
    {/if}
{/block}

{* Footer *}
{block name=footer}
	{if $show_footer == 1}
		{include file="default/layout/main_footer.tpl"}
	{/if}	
{/block}