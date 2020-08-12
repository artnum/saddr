{if isset($saddr.search_results.__edit)}
{if isset($saddr.search_results.name)}
<h1>Edit {$saddr.search_results.name.0}</h1> 
{else}
<h1>Add</h1>
{/if}
{else}
{if isset($saddr.search_results.name)}
<h1>{$saddr.search_results.name.0}</h1> 
{/if}
{/if}
{if isset($saddr.search_results.dn)}
<input type="hidden" name="dn" value="{$saddr.search_results.dn}" />
{/if}
<div id="saddrBusiness" class="saddr_section saddr_sectionLeft">
<h2>Professionnel</h2>
{saddr_entry e="company" label="Société" searchable=1 must=1}
{saddr_entry e="branch" label="Succursale"}
{saddr_entry e="business" label="Domaine d'activité"
  type="sselect" module="category"
  format="@name@" want="uniqueidentifier" multi=1}
{saddr_entry e="work_address" label="Adresse" type="textarea"}
{saddr_entry e="work_npa" label="Code postal"}
{saddr_entry e="work_city" label="Localité" searchable=1}
{saddr_entry e="work_state" label="Canton / État" searchable=1}
{saddr_entry e="work_country" label="Pays"
  type="sselect" module="country"
  format="@name@" want="code"}
{saddr_entry e="work_telephone" label="Téléphone" labelonview=1 multi=1}
{saddr_entry e="work_mobile" label="Mobile" labelonview=1 multi=1}
{saddr_entry e="work_fax" label="Fax" labelonview=1 multi=1}
</div>

<div id="saddrOthers" class="saddr_section saddr_sectionRight">
<h2>Autre</h2>
{saddr_entry e="description" label="Description" type="textarea"}
{saddr_entry e="tags" label="Tags" labelonview=1 type="tag"}
{saddr_entry e="restricted_tags" label="Restricted tags" labelonview=1 type="tag"}
</div>

<div id="saddrInternet" class="saddr_section saddr_sectionRight">
<h2>Internet</h2>
{saddr_entry e="work_email" label="Email professionnel" labelonview=1 multi=1}
{saddr_entry e="url" label="URL" labelonview=1 multi=1}
</div>
