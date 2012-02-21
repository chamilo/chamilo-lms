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
        {* Actions *}
        {if (!empty($actions) ) }
            <div class="actions">
            {$actions}	
            </div>
        {/if}

        {* Notifications*}	
        {$message}
        <section id="main_content">
        {* Main content *}
        {$content}
        </section>
    </div>       
{/block}

{* Footer *}
{block name=footer}
	{if $show_footer == 1}
		{include file="default/layout/main_footer.tpl"}
	{/if}	
{/block}