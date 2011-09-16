<!-- Topbar -->

{if $_u.status == 1}
	<div class="topbar">
	    <div class="topbar-inner">
	        <div class="container">
		        <h3><a href="#">Chamilo</a></h3>
		        <ul class="nav">
		            <li class="active"><a href="#">Home</a></li>
		            <li><a href="#">Profile</a></li>
		            <li><a href="#">Messages</a></li>
		            
		            <li><a href="#">Administration</a></li>
		            <li class="dropdown">
		                <a class="dropdown-toggle" href="#">Dropdown</a>
		            <ul class="dropdown-menu">
		                <li><a href="#">Secondary link</a></li>
		                <li><a href="#">Something else here</a></li>
		                <li class="divider"></li>
		                <li><a href="#">Another link</a></li>
		                </ul>
		                </li>
		            </ul>
		            <form action="">
		            <input type="text" placeholder="Search">
		            </form>
		            <ul class="nav secondary-nav">
		                <li class="dropdown">
		                <a class="dropdown-toggle" href="#"><img src="{$_u.avatar_small}"/>  {$_u.complete_name}</a>
		                <ul class="dropdown-menu">
		                    <li><a href="#">Secondary link</a></li>
		                    <li><a href="#">Help</a></li>
		                    <li class="divider"></li>
		                    <li><a href="#">Logout</a></li>
		                </ul>
		            </li>
		            </ul>
			</div>
		</div><!-- /topbar-inner -->
	</div><!-- /topbar -->
	<div id="topbar_push"></div>
{/if}
    
<div id="header">
	{* header *}
	{$header1}
	
	{* header right *}
	{$header2}
	
	{* menu *}
	{$header3}
	
	{* breadcrumb *}
	{$header4}
</div>	