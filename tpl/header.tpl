<!-- HEADER -->
<div id="saddr_searchBoxes">
<form method="POST" action="{saddr_url}" name="saddr_search">
<span id="saddr_toolbox">
<span><a href="{saddr_url}" title="Back home">Home</a></span>
{if isset($saddr.op) && 
	isset($saddr.search)}
{if isset($saddr.search_results)}
<span><a href="{saddr_url op=$saddr.op search=$saddr.search}"
	title="Search again">{count($saddr.search_results)} result(s)</a></span>
{else}
<span><a href="{saddr_url op=$saddr.op search=$saddr.search}"
	title="Search again">0 result</a></span>
{/if}
{/if}
{if isset($saddr.search_results) &&
   isset($saddr.search_results.id)}
<span>
{if !isset($saddr.search_results.__edit)}
   <a href="{saddr_url op=addOrEdit id=$saddr.search_results.id}"
   title="Edit {$saddr.search_results.name.0}">Edit</a>
{else}
   <a href="{saddr_url op=view id=$saddr.search_results.id}"
   title="View {$saddr.search_results.name.0}">View</a>
{/if}
</span>
<span>
   <a href="{saddr_url op=copy id=$saddr.search_results.id}"
   title="Copy {$saddr.search_results.name.0}">Copy</a>
</span>

<!--
<span>
   {if ! isset($saddr.__selected) || ! $saddr.__selected}
	  <a href="{saddr_url op=select id=$saddr.search_results.id}"
	 title="Select {$saddr.search_results.name.0}">Select</a>
   {else}
         <a href="{saddr_url op=deselect id=$saddr.search_results.id}"
	 title="Deselect {$saddr.search_results.name.0}">Deselect</a>
   {/if}
</span>
-->
{/if}

{if isset($saddr.__delete)}
<span class="dangerous"><a href="{saddr_url op="delete" timed_id=$saddr.__delete}"
    title="Delete entry" class="dangerous">Delete</a></span>
{/if}

</span>

Search : <input type="text" name="saddrGlobalSearch" />
Tag : <input type="text" name="saddrTagSearch" />
<input type="submit" value="Search" name="saddrGoSearch" />
</form>
</div>
{if isset($saddr.handle.user_messages)}
<div id="saddr_messageContainer">
{foreach $saddr.handle.user_messages as $msg}
<div class="saddr_message {saddr_class_message errno=$msg.level}">{$msg.msg}</div>
{/foreach}
</div>
{/if}

