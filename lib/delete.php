<?PHP
/* (c) 2012-2020 Etienne Bagnoud <etienne@artnum.ch>
   This file is part of saddr project. saddr is under the MIT license.

   See LICENSE file
 */

function saddr_delete(&$saddr, $dn)
{
   return saddr_getLdap($saddr)->delete($dn);
}

?>