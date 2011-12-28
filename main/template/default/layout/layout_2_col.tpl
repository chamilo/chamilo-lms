{extends file="default/layout/main.tpl"}

{block name=header}
	{include file="default/layout/header.tpl"}
{/block}

{block name=body}	

	{* Main content*}
	
	{if $show_sniff == 1 }
	 	{include file="default/layout/sniff.tpl"}
	{/if}
	
	<div id="maincontent" class="maincontent">
		
		{* Course plugin block*}		
		{$plugin_courses_block}
		
		{* ?? *}
		{$home_page_block}
		
		{* ?? *}
		{$sniff_notification}
		
		{* Show messages*}
		{$message}
		
		{* Main content*}
		{$content}
		
		{* Announcements *}
		{$announcements_block}
		
		{* Hot courses template *}
		
		{include file="default/layout/hot_courses.tpl"}
		
		
	</div>
		
	{* Right column *}
	<div id="menu-wrapper">
		
	    {*if user is not login show the login form*}
		{if $_u.logged == 0}
			{include file="default/layout/login_form.tpl"}
		{/if}

		{* My account - user picture *}
		{$profile_block}			
		{$account_block}		
		{$teacher_block}
		
		{* Notices *}
		{$notice_block}
		
		{* Links that are not added in the tabs*}
		{$navigation_course_links}
		
		{* Plugin courses sidebar *}
		{$plugin_courses_right_block}
		
		{* Reservation block *}
		{$reservation_block}
		
		{* Search (xapian)*}
		{$search_block}
		
		{* Classes *}
		{$classes_block}
		
		{* Skills*}
		{$skills_block}
	</div>
{/block}

{* Footer *}
{block name=footer}	
	{include file="default/layout/footer.tpl"}	
{/block}