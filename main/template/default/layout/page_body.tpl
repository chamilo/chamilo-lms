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