{extends file="default/layout/main.tpl"}

{block name=header}
	{include file="default/layout/header.tpl"}	
{/block}

{block name=body}
	<h1>My experimental Template!!</h1>	
	{$content}
{/block}

{block name=footer}
	{include file="default/layout/header.tpl"}	
{/block}