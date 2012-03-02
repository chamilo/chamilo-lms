{extends file="default/layout/main.tpl"}
{* Header *}
{block name=header}
{if $show_header == 1 }
{include file="default/layout/main_header.tpl"}
{/if}
{/block}

{block name=body}	

	{* Main content*}
	
	{if $show_sniff == 1 }
	 	{include file="default/layout/sniff.tpl"}
	{/if}
	
	<div class="span9">	
		{* Content top*}
        
        {if !empty($plugin_content_top)}         
            <div id="plugin_content_top">
                {$plugin_content_top}
            </div>
        {/if}        
		
		{* ?? *}
        <section id="home_page">
		{$home_page_block}
        </section>
		
		{* ?? *}
		{$sniff_notification}
		
        {include file="default/layout/page_body.tpl"}		
		
		{* Main content*}
        <section id="main_content">
		{$content}
        </section>
		
		{* Announcements *}
        <section id="announcements_page">
		{$announcements_block}
        </section>
		
		{* Hot courses template *}
		<section id="hot_courses">
		{include file="default/layout/hot_courses.tpl"}
        {* fix the bug where the login is at the left side*}
        &nbsp;
        </section>
        
        {if !empty($plugin_content_bottom)}            
            <div id="plugin_content_bottom" class="span12">
                {$plugin_content_bottom}
            </div>
        {/if}        
	</div>
		
	{* Right column *}
	<div class="span3">
		
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
		
		{* Reservation block *}
		{$reservation_block}
		
		{* Search (xapian)*}
		{$search_block}
		
		{* Classes *}
		{$classes_block}
		
		{* Skills*}
		{$skills_block}        
        	
		{* Plugin courses sidebar *}		
        {*  Plugins for footer section *}		
        {if !empty($plugin_menu)}
            <div id="plugin_menu">
                {$plugin_menu}
            </div>
        {/if}        
	</div>
{/block}

{* Footer *}
{block name=footer}
    {if $show_footer == 1 }
        {include file="default/layout/main_footer.tpl"}
    {/if}
{/block}