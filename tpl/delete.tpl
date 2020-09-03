{if isset($saddr.search_results)}
{assign var=e value=$saddr.search_results}
<h1 class="dangerous">Confirmez la suppression</h1>
<p>Confirmez la suppression de <a href="{saddr_url op="view" id=$e.id}" 
title="View {$e.name.0}">{if isset($e.displayname)}{$e.displayname.0}{else}
{$e.name.0}{/if}</a>. Cette opération est définitive.</p>

<p>Êtes-vous certain de vouloir supprimer cette entrée ?</p>

<p><a href="{saddr_url op="doDelete" timed_id=$saddr.__delete}" 
  title="Delete {$e.name.0}">Oui </a>
<a href="{saddr_url op="view" id=$e.id}" title="View {$e.name.0}">Non</a></p>
{/if}
