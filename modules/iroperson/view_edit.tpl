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
<div id="saddrPicture" class="saddr_section saddr_sectionRight">
{saddr_entry e="picture" label="Picture" type="jpeg"}
</div>

{saddr_ifgroup group="names"}
<div id="saddrNames" class="saddr_section saddr_sectionLeft">
{saddr_entry e="displayname" label="Nom d'affichage"}
{saddr_entry e="title" label="Titre"}
{saddr_entry e="firstname" label="Prénom" multi=1}
{saddr_entry e="lastname" label="Nom" searchable=1 must=1}
</div>
{/saddr_ifgroup}

<div id="saddrBusiness" class="saddr_section saddr_sectionLeft">
<h2>Professionnel</h2>
{saddr_entry e="function" label="Fonction"}
{saddr_entry e="principalbusiness" label="Activité principale"
  type="sselect" module="category"
  format="@name@" want="uniqueidentifier" recurseOn="parent" labelonview=1 leafOnly=1}

{saddr_entry e="business" label="Autres activités"
  type="sselect" module="category"
  format="@name@" want="uniqueidentifier" multi=1 recurseOn="parent" labelonview=1 leafOnly=1}

{saddr_entry e="company" label="Société" searchable=1 multi=1}
{saddr_entry e="work_address" label="Adresse" type="textarea"}
{saddr_entry e="work_npa" label="Code postal"}
{saddr_entry e="work_city" label="Localité" searchable=1}
{saddr_entry e="work_state" label="Canton / état" searchable=1}
{saddr_entry e="work_country" label="Pays"
  type="sselect" module="country"
  format="@name@" want="code"}
{saddr_entry e="work_telephone" label="Téléphone" labelonview=1 multi=1 type="phone"}
{saddr_entry e="work_fax" label="Fax" labelonview=1 multi=1}
</div>

{saddr_ifgroup group="private"}
<div id="saddrPersonnal" class="saddr_section saddr_sectionRight">
<h2>Personnel</h2>
{saddr_entry e="home_address" label="Adresse" type="textarea"}
{saddr_entry e="home_npa" label="Code postal"}
{saddr_entry e="home_city" label="Localité" searchable=1}
{saddr_entry e="home_state" label="Canton / état" searchable=1}
{saddr_entry e="home_country" label="Pays"
  type="sselect" module="country"
  format="@name@" want="code"}
{saddr_entry e="home_telephone" label="Téléphone" labelonview=1 multi=1 type="phone"}
{saddr_entry e="home_mobile" label="Mobile" labelonview=1 multi=1 type="phone"}
{saddr_entry e="birthday" label="Anniversaire" labelonview=1 type="date"}
</div>
{/saddr_ifgroup}

{saddr_ifgroup group="internet"}
<div id="saddrInternet" class="saddr_section saddr_sectionLeft">
<h2>Internet</h2>
{saddr_entry e="work_email" label="Email professionnel" labelonview=1 multi=1}
{saddr_entry e="url" label="URL" labelonview=1 multi=1}
</div>
{/saddr_ifgroup}

{saddr_when_module_available module="irobank"}
{saddr_ifgroup group="bank"}
<div id="saddrBanking" class="saddr_section saddr_sectionRight">
<h2>Banque</h2>
<p>
{saddr_entry e="bank" label="Établissement" 
   type="sselect" module="irobank"
   format="@company@[, @branch@]"}
{saddr_entry e="iban" label="IBAN" labelonview=1}
</div>
{/saddr_ifgroup}
{/saddr_when_module_available}

{saddr_ifgroup group="other"}
<div id="saddrOthers" class="saddr_section saddr_sectionRight">
<h2>Autre</h2>
{saddr_entry e="description" label="Description" type="textarea"}
{saddr_entry e="tags" label="Tags" labelonview=1 type="tag"}
{saddr_entry e="restricted_tags" label="Restricted tags" labelonview=1 type="tag"}
</div>
{/saddr_ifgroup}
