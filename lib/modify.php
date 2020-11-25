<?PHP
/* (c) 2012-2020 Etienne Bagnoud <etienne@artnum.ch>
   This file is part of saddr project. saddr is under the MIT license.

   See LICENSE file
 */

function saddr_modify(&$saddr, $smarty_entry)
{
   $ret=array('', FALSE);
   if(!empty($smarty_entry) && isset($smarty_entry['module']) &&
            is_string($smarty_entry['module']) && isset($smarty_entry['dn']) &&
            is_string($smarty_entry['dn'])) {
      $ldap_entry=saddr_makeLdapEntry($saddr, $smarty_entry);

      foreach($ldap_entry as $attr=>$value) {
         $v=saddr_processAttributes($saddr, $smarty_entry['module'],
               array($attr, $value));
         if($v!==FALSE) {
            $ldap_entry[$attr]=$v;
         }
      }

      $ret[0]=$smarty_entry['dn'];
      if(!empty($ldap_entry)) {
         
         $dn=$smarty_entry['dn'];
         $dn_parts=explode(',', $dn);
         $rdn_components=array();

         if(strstr($dn_parts[0], '+')) {
            $multi=explode('+', $dn_parts[0]);
            foreach($multi as $m) {
               $x=explode('=', $m);
               $rdn_components[strtolower($x[0])]=$x[1];
            }
         } else {
            $x=explode('=', $dn_parts[0]);
            $rdn_components[strtolower($x[0])]=$x[1];
         }

         $need_rename=FALSE;
         $skip_rename=FALSE;

         $ldap_attrs=saddr_getAttrs($saddr, $smarty_entry['module']);
         foreach($rdn_components as $attr=>$val) {
            if(!isset($ldap_attrs[$attr])) {
               $skip_rename=TRUE;
               break;
            }
         }
         
         if(!$skip_rename) {
            foreach($rdn_components as $attr=>$val) {
               if(!isset($ldap_entry[$attr])) {
                  $need_rename=TRUE; break;
               } else {
                  $ok=FALSE;
                  foreach($ldap_entry[$attr] as $_v) {
                     if($_v==$val) $ok=TRUE;
                  }
                  if(!$ok) {
                     $need_rename=TRUE;
                     break;
                  }
               }
            }
         }

         $ldap = saddr_getLdap($saddr);
         $results = $ldap->search($dn, '(objectclass=*)', ['*'], 'base');
         $old = null;
         foreach ($results as $rset) {
            if ($rset->count() === 1) {
               $old = $rset->firstEntry();
            }
         }
         if (!$old) { return false; }
         if(!$need_rename) {
            $attrs = [];
            foreach ($ldap_entry as $attr => $val) {
               /* attribute with name starting with '-' are ignored */
               if (substr($attr, 0, 1) === '-') {
                  $attrs[] = substr($attr, 1);
                  continue;
               }
               /* skip for now, would be ignored later */
               if (in_array($attr, $rdn_components)) { continue; }
               if (substr($attr, 0, 1) === '-') {
                  $_attr = substr($attr, 1);
                  if ($old->get($_attr)) {
                     $old->delete($_attr);
                  }
                  continue;
               }
               $attrs[] = $attr;
               $old_val = $old->get($attr);
               if ($old_val === null) {
                  $old->add($attr, $val);
               } else {
                  if (count($val) !== count($old_val)) {
                     $old->replace($attr, $val);
                  } else {
                     $replace = false;
                     foreach ($val as $v) {
                        foreach ($old_val as $v2) {
                           if ($v !== $v2) {
                              $old->replace($attr, $val);
                              $replace = true;
                              break;
                           }
                           if ($replace) { break; }
                        }
                     }
                  }
               }
            }
            foreach($old->eachAttribute() as $attr => $value) {
               if (in_array($attr, array_keys($rdn_components))) { continue; }
               if ($attr === 'objectclass') { continue; }
               /* seealso must not be modified as it has not complete support yet */
               if (strpos(strtolower($attr), 'seealso') === 0) { continue; }
               /* attributes used for import/export, don't touch */
               if (strpos(strtolower($attr), 'addrimported') === 0) { continue; }
               if (strpos(strtolower($attr), 'addrsyncid') === 0) { continue; }
               if (!in_array($attr, $attrs)) {
                  $old->delete($attr);
               }
            }
     
            $ret=array($dn, $old->commit());
            if(!$ret[1]) {
               $ret[0]=$smarty_entry['dn'];
            }
         } else {
            $ret=saddr_add($saddr, $smarty_entry);
            if($ret[1]) {
               $ldap->delete($old->dn());
            } else {
               $ret[0]=$smarty_entry['dn'];
            }
         }
      }
   }

   return $ret;
}

?>
