{if isset($saddr.search_results.__edit)}
{if isset($saddr.search_results.name)}
<h1>Edit {$saddr.search_results.name.0}</h1> 
{else}
<h1>Add country</h1>
{/if}
{else}
{if isset($saddr.search_results.name)}
<h1>{$saddr.search_results.name.0}</h1> 
{/if}
{/if}

<div id="saddrNames" class="saddr_section saddr_sectionLeft">
{saddr_entry e="name" label="Name"}
{saddr_entry e="code" label="Code"}
</div>
