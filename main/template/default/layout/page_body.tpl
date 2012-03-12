{* Content top *}

{if !empty($plugin_content_top)}         
    <div id="plugin_content_top">
        {$plugin_content_top}
    </div>
{/if}
        
{* Actions *}
{if (!empty($actions)) }
    <div class="actions">
    {$actions}	
    </div>
{/if}

{* Page header*}
{if !empty($header) }
    <div class="page-header">
        <h1>{$header}</h1>
    </div>
{/if}

{* Show messages*}
<section id="messages">
{$message}
</section>

{* Main content*}
<section id="main_content">
{$content}
</section>

