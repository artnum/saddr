{if isset($saddr.search_results.__edit)}
{if isset($saddr.search_results.name)}
<h1>Edit {$saddr.search_results.name.0}</h1> 
{else}
<h1>Ajout catégorie</h1>
{/if}
{else}
{if isset($saddr.search_results.name)}
<h1>{$saddr.search_results.name.0}</h1> 
{/if}
{/if}

<div id="saddrNames" class="saddr_section saddr_sectionLeft">
{saddr_entry e="name" label="Nom"}
{saddr_entry e="description" label="Description"}
{saddr_entry e="parent" label="Activité mère"
  type="sselect" module="category"
  format="@name@" want="dn" multi=1 recurseOn="parent" labelonview=1}
</div>
