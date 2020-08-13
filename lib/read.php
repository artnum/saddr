<?PHP
/* (c) 2012-2020 Etienne Bagnoud <etienne@artnum.ch>
   This file is part of saddr project. saddr is under the MIT license.

   See LICENSE file
 */
function saddr_read(&$saddr, $dn, $attrs=array(), $deepness=0)
{
   $ret=FALSE;
   $se=array();

   $module=FALSE;
   $oc=FALSE;
  
   $ldap_attrs=array('*');
   if(!empty($attrs)) {
      $ldap_attrs=saddr_getLdapAttr($saddr, $attrs);
   } 

   $ldap=saddr_getLdap($saddr);
   if($ldap!=NULL && $ldap!=FALSE) {
      $results = $ldap->search($dn, '(objectclass=*)', $ldap_attrs, 'base');
      foreach ($results as $rset) {
         if ($rset->count() > 0) {
         $entry = $rset->firstEntry();
            $ret=saddr_makeSmartyEntry($saddr, $entry);
            if($deepness>0) {
               if(isset($ret['seealso'])) {
                  $deepness--;
                  $resolved_seealso=array();
                  foreach($ret['seealso'] as $see_dn) {
                     $rel_entry=saddr_read($saddr, $see_dn, array(), $deepness);
                     if($rel_entry!==FALSE) {
                        $resolved_seealso[]=$rel_entry;
                     }
                  }
                  $ret['seealso']=$rel_entry;
               }
            }
         }
      }
   }

   return $ret;
}

?>
