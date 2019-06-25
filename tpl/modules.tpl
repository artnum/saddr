{saddr_available_modules result=modules}
<h1>Configured module</h1>
<ul>
{foreach $modules.configured as $module}
  <li>{$module.name} 
  <a href="{saddr_url op="moduleUnconfigure" id=$module.dn encrypt=1}">Unconfigure</a>
  <a href="{saddr_url op="preAdd" module=$module.module}">Add entry</a>
  </li>
{/foreach}
</ul>
<h1>Available module</h1>
<ul>
{foreach $modules.available as $module}
  <li>{$module.name} 
  <a href="{saddr_url op="moduleConfigure" id=$module.dn encrypt=1}">Configure</a>
  <a href="{saddr_url op="preAdd" module=$module.module}">Add entry</a>
  </li>
{/foreach}
</ul>
