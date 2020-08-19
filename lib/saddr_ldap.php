<?PHP
/* (c) 2012-2020 Etienne Bagnoud <etienne@artnum.ch>
   This file is part of saddr project. saddr is under the MIT license.

   See LICENSE file
 */

/* Prepare, open and bind to LDAP directory. Protocol version, TLS and any
   other stuff are discovered on the fly. Only the host is needed
 */
function saddr_prepareLdapConnection(&$saddr)
{
   $ldap_handle = new artnum\LDAPHelper();

   $ldap_host = saddr_getLdapHost($saddr);
   $user = saddr_getUser($saddr);
   $pass = saddr_getPass($saddr);

   $opts = [];
   if (!empty($user) && !empty($pass)) {
      $opts = [ 'dn' => $user, 'password' => $pass];
   }
   if(is_string($ldap_host)) {
      $ldap_handle->addServer($ldap_host, 'simple', $opts);
   } else {
      /* Try connect without option in case ldap.conf is correctly configured
       */
      $ldap_handle->addServer('', 'simple', $opts);
   }
   saddr_setLdap($saddr, $ldap_handle);

   return $ldap_handle;
}

function saddr_reduceBases ($bases) {
   $out = [];
   foreach ($bases as $base) {
      if (empty($out)) {
         $out[] = $base;
      } else {
         foreach ($out as $k => $v) {
            if ($v === $base) { continue; }
            $lv = strlen($v);
            $lb = strlen($base);
            if ($lv > $lb) {
               if (strpos($v, $base) === $lv - $lb) {
                  if (in_array($base, $out)) {
                     unset($out[$k]);
                  } else {
                     $out[$k] = $base;
                  }
               } else {
                  if (!in_array($base, $out)) {
                     $out[] = $base;
                  }
               }
            } else {
               if (strpos($v, $base) !== $lv - $lb) {
                  if (!in_array($base, $out)) {
                     $out[] = $base;
                  }
               }
            }
         }
      }
   }
   return $out;
}

?>
