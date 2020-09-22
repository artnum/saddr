<?PHP
/* (c) 2012-2020 Etienne Bagnoud <etienne@artnum.ch>
   This file is part of saddr project. saddr is under the MIT license.

   See LICENSE file
 */
function saddr_search(&$saddr, $search, $search_on=array(), $attrs=array(),
      $search_op='=')
{
   $smarty_entries=array();

   if(empty($attrs)) {
      $attrs=array('name', 'displayname', 'work_telephone', 'work_email', 
            'home_telephone', 'home_email', 'work_fax', 'home_fax',
            'work_mobile', 'home_mobile', 'branch', 'description', 'tags');
   }
   if(empty($search_on)) {
      $search_on=array('name', 'company', 'displayname', 'lastname', 'firstname', 
            'tags', 'home_email', 'work_email');
   }

   /* Attribute we search for added to attributes asked for */
   foreach($search_on as $sattr) {
      if(!in_array($sattr, $attrs)) {
         $attrs[]=$sattr;
      }
   }
   
   $ldap_attrs=saddr_getLdapAttr($saddr, $attrs);
   if(empty($ldap_attrs)) return array();

   $ldap_search_filter=saddr_getSearchFilter($saddr, $search_on, $search, 
      array(), $search_op);
   if(!empty($ldap_search_filter)) {
      
      $ldap=saddr_getLdap($saddr);
      $bases=saddr_getAllLdapBase($saddr);
      $bases = saddr_reduceBases($bases);
      foreach($bases as $base) {
         $results = $ldap->search($base, $ldap_search_filter, $ldap_attrs, 'sub');
         foreach ($results as $rset) {
            for ($entry = $rset->firstEntry(); $entry; $entry = $rset->nextEntry()) {

               $_sent=saddr_makeSmartyEntry($saddr, $entry);
               if(!empty($_sent['name'])) {
                  $smarty_entries[]=$_sent;
                  $smarty_entries = array_merge($smarty_entries, _subsearch($saddr, $ldap, $base, sprintf('(seealso=%s)', $_sent['dn']), $ldap_attrs));
               }
            }
         }
      }
   }
   
   usort($smarty_entries, 'cmp');

   return $smarty_entries;
}

function _subsearch($saddr, $ldap, $base, $filter, $ldap_attrs) {
   $subentries = [];
   $subsearch = $ldap->search($base, $filter, $ldap_attrs, 'sub');
   foreach ($subsearch as $subresults) {
      for ($subentry = $subresults->firstEntry(); $subentry; $subentry = $subresults->nextEntry()) {
         $subentry_smarty = saddr_makeSmartyEntry($saddr, $subentry);
         if (!empty($subentry_smarty['name'])) {
            $subentries[] = $subentry_smarty;
            $subentries = array_merge($subentries, _subsearch($saddr, $ldap, $base, sprintf('(seealso=%s)', $subentry_smarty['dn']), $ldap_attrs));
         }
      }
   }
   return $subentries;
}
?>
