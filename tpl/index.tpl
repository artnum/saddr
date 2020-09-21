<!DOCTYPE html>
<html>
<head>
	<script src="conf/app.js"></script>
	<script src="js/gevent.js"></script>
	<script src="js/location.js"></script>
	<script src="js/multi.js"></script>
	<script src="node_modules/@popperjs/core/dist/umd/popper.min.js"></script>
	<script src="node_modules/aselect/src/aselect.js"></script>
	<title>saddr</title>
	<link rel="stylesheet" href="node_modules/@fortawesome/fontawesome-free/css/all.min.css" />
	<link rel="stylesheet" href="css/default/default.css" type="text/css" />	
</head>
<body>
{include file="header.tpl"}

<div id="saddr_content">
{if isset($saddr) && isset($saddr.display)}
	{if isset($saddr) && isset($saddr.search_results) && 
	  isset($saddr.search_results.__edit)}
     <div id="edit">
		<form method="post" action="{saddr_url op="doAddOrEdit"}" 
			data-dojo-type="dijit.form.Form" enctype="multipart/form-data"
			onsubmit="if(this.validate()) { return true; } else { return false; }">
		{if isset($saddr.search_results.id)}<input type="hidden" name="id" 
			value="{$saddr.search_results.id}" />{/if}
		{if isset($saddr.search_results.module)}<input type="hidden" 
			name="module" value="{$saddr.search_results.module}" />{/if}
		{include file=$saddr.display}
		<div id="saddr_buttonsContainer">
		<input type="submit" value="Ok" name="saddr_goAddEdit" />
		</div>
		</form>
      </div>
	{else}
		{include file=$saddr.display}
	{/if}
{else}
	{include file="home.tpl"}
{/if}
</div>

{include file="footer.tpl"}
</body>
</html>