<?PHP
/* (c) 2012-2020 Etienne Bagnoud <etienne@artnum.ch>
   This file is part of saddr project. saddr is under the MIT license.

   See LICENSE file
 */
function saddr_list(&$saddr, $module, $attrs=array())
{
   $smarty_entries=array();

   /* If name is not asked, entry is counted as invalid */
   if(!empty($attrs) && !isset($attrs['name'])) $attrs[]='name';

   if(!saddr_isModuleAvailable($saddr, $module)) return array();
   
   $ldap_attrs=saddr_getLdapAttr($saddr, $attrs, $module);
   if(empty($ldap_attrs)) return array();

   $oc=saddr_getClass($saddr, $module);
   $ldap_search_filter='(objectclass='.$oc['structural'].')';

   $ldap=saddr_getLdap($saddr);
   $bases=saddr_getModuleBase($saddr, $module);
  
   foreach($bases as $base) {
      print_r($ldap_search_filter);
      $results = $ldap->search($base, $ldap_search_filter, $ldap_attrs, 'one');
      foreach ($results as $rset) {
         for($entry = $rset->firstEntry(); $entry; $entry = $rset->nextEntry()) {
            $_sent=saddr_makeSmartyEntry($saddr, $entry);
            //if(isset($_sent['name'])) {
               $smarty_entries[]=$_sent;
            //}
         }
      }
      $results = null;
   }
   usort($smarty_entries, 'cmp');
   return $smarty_entries;
}

?>
