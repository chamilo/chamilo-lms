{extends file="default/layout/main.tpl"}

{* Header *}
{block name="main_header"}
	{if $show_header == 1}
		{include file="default/layout/main_header.tpl"}
	{/if}	
{/block}

{* 1 column *}
{block name=body}

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
{/block}

{* Footer *}
{block name=footer}
	{if $show_footer == 1}
		{include file="default/layout/main_footer.tpl"}
	{/if}	
{/block}