{extends file="default/layout/main.tpl"}

{block name=header}
	{include file="default/layout/header.tpl"}
{/block}

{block name=body}	
	{if $show_sniff == 1 }
	 	{include file="default/layout/sniff.tpl"}
	{/if}
	<div id="maincontent" class="maincontent">
		
		{$plugin_courses_block}
		{$home_page_block}
		{$sniff_notification}
		{$message}
		{$content}
		{$announcements_block}

                {if !(empty($hot_courses)) }	
                     <h3>Hottest courses</h3>
                    {foreach $hot_courses as $hot_course}
                        <div class="categories-block-course">
                            <div class="categories-content-course">
                                Course id: {$hot_course.c_id}
                            </div>
                        </div>
                    {/foreach}
                {/if}
	</div>
	
	<div id="menu-wrapper">
	    
		{if $_u.logged == 0}
			{include file="default/layout/login_form.tpl"}
		{/if}

		{$profile_block}			
		{$account_block}		
		{$teacher_block}
		{$notice_block}
		{$navigation_course_links}
		{$plugin_courses_right_block}
		{$reservation_block}
		{$search_block}
		{$classes_block}
		{$skills_block}
	</div>
{/block}

{block name=footer}
	{include file="default/layout/footer.tpl"}	
{/block}