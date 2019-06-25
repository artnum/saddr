<div class="saddr_section saddr_sectionLeft">
{foreach $saddr.__home_display as $home}
<form method="post" action="{saddr_url op="preAdd"}">
<input type="hidden" name="module" value="{$home.1}" />
{include file=$home.0}
<input type="submit" name="goToAdd" value="Next" 
  data-dojo-type="dijit.form.Button" data-dojo-props="label: 'Next'" />
</form>
{/foreach}
</div>

<div class="saddr_section saddr_sectionRight">
<h1>Search</h1>
{foreach array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p',
  'q','r','s','t','u','v','w','x','z') as $letter}
<a href="{saddr_url op="doGlobalSearch" search="$letter" encrypt=1}">{$letter}</a>&nbsp;
{/foreach}
<h1>List</h1>
<ul>
{foreach $saddr.__home_display as $home}
<li><a href="{saddr_url op="list" module=$home.1 encrypt=1}">{$home.2}</a></li>
{/foreach}
</ul>
</div>
