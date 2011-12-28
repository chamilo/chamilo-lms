{extends file="default/layout/main.tpl"}

{* Header *}
{block name=header}
	{if $show_header == 1}
		{include file="default/layout/header.tpl"}
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
	
	{* Main content *}
	{$content}
{/block}

{* Footer *}
{block name=footer}
	{if $show_header == 1}
		{include file="default/layout/footer.tpl"}
	{/if}	
{/block}