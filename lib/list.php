<?PHP
/* (c) 2012-2020 Etienne Bagnoud <etienne@artnum.ch>
   This file is part of saddr project. saddr is under the MIT license.

   See LICENSE file
 */
function saddr_list(&$saddr, $module, $attrs = [])
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

/* to get relations from an entry, so seealso of this entry must be examined and 
 * the directory must be searched for entry DN in their seealso 
 */
function saddr_listRelation (&$saddr, $dn) {
   $smarty_entries = [];
   $attrs = ['name', 'displayname', 'work_telephone', 'work_email', 
   'home_telephone', 'home_email', 'work_fax', 'home_fax',
   'work_mobile', 'home_mobile', 'branch', 'description', 'tags'];
   $ldap_attrs =  saddr_getLdapAttr($saddr, $attrs);
   $ldap_attrs[] = 'seealso';


   $ldap = saddr_getLdap($saddr);
   $result = $ldap->search($dn, '(objectclass=*)', $ldap_attrs, 'base');
   if (empty($result) || !($entry = $result[0]->firstEntry())) {
      return [];
   }

   $smarty_entries['__parent'] = saddr_makeSmartyEntry($saddr, $entry);
   foreach($entry->getAll('seealso') as $seealso) {
      if (($relation = saddr_isCurrentlyInRelation($seealso['name']))) {
         foreach ($seealso['value'] as $relDn) {
            $entry = saddr_read($saddr, $relDn, $attrs, 0);
            if (!empty($entry['name'])) {
               $relation['direction'] = '>';
               $entry['__relation'] = $relation;
               $smarty_entries[] = $entry;
            }
         }
      }
   }   


   $bases = saddr_reduceBases(saddr_getAllLdapBase($saddr));
   foreach($bases as $base) {
      $results = $ldap->search($base, sprintf('(seealso=%s)', $dn), $ldap_attrs, 'sub');
      foreach ($results as $rset) {
         for($entry = $rset->firstEntry(); $entry; $entry = $rset->nextEntry()) {
            foreach ($entry->getAll('seealso') as $seealso) {
               if (($relation = saddr_isCurrentlyInRelation($seealso['name']))) {
                  $_sent=saddr_makeSmartyEntry($saddr, $entry);
                  if(!empty($_sent['name'])) {
                     $relation['direction'] = '<';
                     $_sent['__relation'] = $relation;
                     $smarty_entries[]=$_sent;
                  }
               }
            }
         }
      }
      $results = null;
   }

   uasort($smarty_entries, 'cmp');
   return $smarty_entries;
}

?>
