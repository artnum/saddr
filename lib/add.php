<?PHP
/* (c) 2012-2020 Etienne Bagnoud <etienne@artnum.ch>
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
         if (substr($attr, 0, 1) === '-') { unset($ldap_entry[$attr]); continue; }
         $v=saddr_processAttributes($saddr, $smarty_entry['module'],
               array($attr, $value));
         if($v!==FALSE) {
            $ldap_entry[$attr]=$v;
         }
      }

      if(!empty($ldap_entry)) {
         $hAttr = saddr_getHashGeneratedAttributes($saddr, $smarty_entry['module']);
         if ($hAttr) {
            $ctx = hash_init('sha256');
            hash_update($ctx, time());
            foreach($ldap_entry as $k => $v) {
               hash_update($ctx, $k);
               if(is_string($v)) {
                  hash_update($ctx, $v);
               } else {
                  foreach($v as $_v) {
                     if (is_string($_v)) {
                        hash_update($ctx, $_v);
                     }
                  }
               }
            }
       
            $hash = hash_final($ctx);
            if (is_string($hAttr)) {
               $hAttr = [$hAttr];
            }
            foreach ($hAttr as $attr) {
               $ldap_entry[$attr] = [$hash];
            }
         }
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

               if(!empty($dn)) {
                  $entry = new artnum\LDAPHelperEntry(saddr_getLdap($saddr));
                  $entry->dn($dn);
                  
                  foreach ($ldap_entry as $attr => $value) {
                     $entry->add($attr, $value);
                  }
                  $ret = $entry->commit();
                  if(!$ret) {
                     $msg = '-- Error addition failed' . "\n";
                     $msg .= 'LDAP ERROR : ' . $entry->lastError() . "\n";
                     $msg .= "\n";
                     $msg .= var_export($ldap_entry, true) . "\n";
                     $msg .= "\n";
                     $msg.= $dn . "\n";
                     $msg.= '--' . "\n\n";
                     file_put_contents(SADDR_DEBUG_FILE, $msg,  FILE_APPEND | LOCK_EX);
                  }
                  return [$dn, $ret];
               }
            }
         }
      }
   }

   return ['', false];
}

?>
