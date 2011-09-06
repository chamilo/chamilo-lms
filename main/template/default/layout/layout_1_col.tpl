{extends file="default/layout/main.tpl"}

{block name=header}
	{include file="default/layout/header.tpl"}	
{/block}

{block name=body}	
	{$content}
{/block}

{block name=footer}
	{include file="default/layout/footer.tpl"}	
{/block}