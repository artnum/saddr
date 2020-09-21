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
               $seealso = $entry->getAll('seealso');
               if(!empty($seealso)) {
                  $deepness--;
                  $resolved_seealso=array();
                  $workAt = null;
                  $rel_entry = [];
                  foreach ($seealso as $see) {
                     foreach($see['value'] as $see_dn) {
                        $rel_entry=saddr_read($saddr, $see_dn, array(), $deepness);
                        if($rel_entry!==FALSE) {
                           $resolved_seealso[]=$rel_entry;
                           if (saddr_isCurrentlyWorking($see['name'])) {
                              $workAt = $rel_entry;
                           }
                        }
                     }
                  }
                  $ret['seealso'] = $rel_entry;

                  if ($workAt !== null) {
                     foreach ($workAt as $k => $v) {
                        $waId = $workAt['id'];
                        /* coming from deeper is resolved as the last step in order to 
                         * keep link logical. If E0[name] <-- E1[name] <-- E2[name]="The Name"
                         * we want to go from E0 to E1 and on E1 see that it come from E2.
                         */
                        if ($deepness === 1) {
                           $waId = $ret['id'];
                           foreach ($workAt['__relation'] as &$v) {
                              if (!is_array($v)) { continue; }
                              for ($i = 0; $i < count($v); $i++) {
                                 $v[$i]['source'] = $waId;
                              }
                           }
                        }
                        if (empty($ret[$k])) {
                           $ret[$k] = $v;
                           if (!isset($ret['__relation'][$k])) {
                              $ret['__relation'][$k] = [];
                           }
                           $ret['__relation'][$k][] = [
                              'type' => 'worker',
                              'source' => $waId
                           ];
                        }
                     }
                  }
               }
            }
         }
      }
   }

   return $ret;
}

?>
