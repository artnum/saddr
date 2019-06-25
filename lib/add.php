<?PHP
/* (c) 2012 Etienne Bagnoud
   This file is part of saddr project. saddr is under the MIT license.

   See LICENSE file
 */

/* add an entry (smarty entry) to the directory
 */
function saddr_add(&$saddr, $smarty_entry)
{
   if(!empty($smarty_entry) && isset($smarty_entry['module']) &&
            is_string($smarty_entry['module'])) {
      $ldap_entry=saddr_makeLdapEntry($saddr, $smarty_entry);

      foreach($ldap_entry as $attr=>$value) {
         $v=saddr_processAttributes($saddr, $smarty_entry['module'],
               array($attr, $value));
         if($v!==FALSE) {
            $ldap_entry[$attr]=$v;
         }
      }

      if(!empty($ldap_entry)) {
         $rdn_attrs=saddr_getRdnAttributes($saddr, $smarty_entry['module']);
         if(isset($rdn_attrs['principal']) &&
               is_string($rdn_attrs['principal'])) {
            if(isset($ldap_entry[$rdn_attrs['principal']])) {
               $rdn=$rdn_attrs['principal'].'='.
                     $ldap_entry[$rdn_attrs['principal']][0];

               $dn='';
               $bases=saddr_getModuleBase($saddr, $smarty_entry['module']);
               $e=@saddr_read($saddr, $rdn.','. $bases[0], array('name'));
               if($e==FALSE) {
                  $dn=$rdn.','.$bases[0];
               } else {
                  foreach($rdn_attrs['multi'] as $m_rdn) {
                     if(isset($ldap_entry[$m_rdn])) {
                        $rdn.='+'.$m_rdn.'='.$ldap_entry[$m_rdn][0];
                        if(!saddr_read($saddr, $rdn.','. $bases[0])){
                           $dn=$rdn.','.$bases[0];
                           break;
                        }
                     }
                  }
               }

               if($dn!='') {
                  $ctx = hash_init('sha256');
                  hash_update($ctx, time());
                  foreach($ldap_entry as $k => $v) {
                     hash_update($ctx, $k);
                     if(is_string($v)) {
                        hash_update($ctx, $v);
                     } else {
                        foreach($v as $_v) {
                           if (is_string($_v)) {
                              hash_update($ctx, $v);
                           }
                        }
                     }
                  }
                  $ldap_entry['uid'] = array(hash_final($ctx));
                  $dn = 'uid='.$ldap_entry['uid'][0].','.$bases[0];
                  $ret=array($dn, ldap_add(saddr_getLdap($saddr), $dn,
                           $ldap_entry));
                  if(! $ret[0]) {
                     $entry = '-- Error addition failed' . "\n";
                     $entry .= 'LDAP ERROR : ' . ldap_error(saddr_getLdap($saddr)) . "\n";
                     $entry .= "\n";
                     $entry .= var_export($ldap_entry) . "\n";
                     $entry .= "\n";
                     $entry .= $dn . "\n";
                     $entry .= '--' . "\n\n";
                     file_put_contents(SADDR_DEBUG_FILE, $entry,  FILE_APPEND | LOCK_EX);
                  }
                  return $ret;
               }
            }
         }
      }
   }

   return array('', FALSE);
}

?>
