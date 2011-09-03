{extends file="default/layout/main.tpl"}

{block name=header}
	{include file="default/layout/header.tpl"}	
{/block}

{block name=body}
	<h1>{"WelcomeUserXToTheSiteX"|get_lang} </h1>	
	<div id="left_col">		
		{$announcements_block}
	</div>
		
	<div id="center_col">
		{$content}
	</div>	
	<div id="right">
		{$user_complete_name}
			
		{$login_block}		
		{$profile_block}			
		{$account_block}		
		{$teacher_block}
		{$notice_block}
		{$navigation_course_links}
		{$plugin_courses_right_block}
		{$reservation_block}
		{$search_block}
		{$classes_block}
	</div>
{/block}

{block name=footer}
	{include file="default/layout/footer.tpl"}	
{/block}