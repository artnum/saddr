<?PHP
/* (c) 2012 Etienne Bagnoud
   This file is part of "tchetch PHP lib". The project is under MIT License.

   See LICENSE file
 */

/* *** Define LDAP API constant that might not be defined *** */
/* Error code */
if(!defined('LDAP_SUCCESS')) define('LDAP_SUCCESS', 0x00);
if(!defined('LDAP_OPERATION_ERROR')) define('LDAP_OPERATION_ERROR', 0x01);
if(!defined('LDAP_PROTOCOL_ERROR')) define('LDAP_PROTOCOL_ERROR', 0x02);
if(!defined('LDAP_TIMELIMIT_EXCEEDED')) define('LDAP_TIMELIMIT_EXCEEDED', 0x03);
if(!defined('LDAP_SIZELIMIT_EXCEEDED')) define('LDAP_SIZELIMIT_EXCEEDED', 0x04);

if(!defined('LDAP_PARTIAL_RESULTS')) define('LDAP_PARTIAL_RESULTS', 0x09);


if(!defined('LDAP_OTHER')) define('LDAP_OTHER', 0x50);

/* Controls */
if(!defined('LDAP_CONTROL_MANAGEDSAIT')) define('LDAP_CONTROL_MANAGEDSAIT', '2.16.840.1.113730.3.4.2');
if(!defined('LDAP_CONTROL_PROXY_AUTHZ')) define('LDAP_CONTROL_PROXY_AUTHZ', '2.16.840.1.113730.3.4.18');
if(!defined('LDAP_CONTROL_SUBENTRIES')) define('LDAP_CONTROL_SUBENTRIES', '1.3.6.1.4.1.4203.1.10.1');
if(!defined('LDAP_CONTROL_VALUESRETURNFILTER')) define('LDAP_CONTROL_VALUESRETURNFILTER', '1.2.826.0.1.3344810.2.3');
if(!defined('LDAP_CONTROL_ASSERT')) define('LDAP_CONTROL_ASSERT', '1.3.6.1.1.12');
if(!defined('LDAP_CONTROL_PRE_READ')) define('LDAP_CONTROL_PRE_READ', '1.3.6.1.1.13.1');
if(!defined('LDAP_CONTROL_POST_READ')) define('LDAP_CONTROL_POST_READ', '1.3.6.1.1.13.2');
if(!defined('LDAP_CONTROL_SORTREQUEST')) define('LDAP_CONTROL_SORTREQUEST', '1.2.840.113556.1.4.473');
if(!defined('LDAP_CONTROL_SORTRESPONSE')) define('LDAP_CONTROL_SORTRESPONSE', '1.2.840.113556.1.4.474');

/* ... non-standard */
if(!defined('LDAP_CONTROL_PAGEDRESULTS')) define('LDAP_CONTROL_PAGEDRESULTS', '1.2.840.113556.1.4.319');

/* Extended operation */
if(!defined('LDAP_EXOP_START_TLS')) define('LDAP_EXOP_START_TLS', '1.3.6.1.4.1.1466.20037');

/* Read the rootDSE entry of a directory. This entry contains a lot of 
   information to discover features of the directory
 */
function tch_getRootDSE($ldap_handle)
{
   if(is_resource($ldap_handle)) {
      $rootDSE=ldap_read($ldap_handle, '', '(objectclass=*)', array('+'));
      if($rootDSE) {
         $dse=ldap_get_entries($ldap_handle, $rootDSE);
         if($dse!=FALSE && $dse['count']==1) {
            return $dse;
         }
      }
   }
   return FALSE;
}

/* Extract all bases (namingContext) that the directory manage */
function tch_getLdapBases($ldap_handle, $rootDSE)
{
   $bases=array();
   
   if(isset($rootDSE[0])) $rootDSE=$rootDSE[0];
   else return FALSE;

   if(is_array($rootDSE) && isset($rootDSE['namingcontexts']) &&
         isset($rootDSE['namingcontexts']['count']) &&
         $rootDSE['namingcontexts']['count']>0) {
      for($i=0;$i<$rootDSE['namingcontexts']['count'];$i++) {
         $bases[]=$rootDSE['namingcontexts'][$i];
      }
   }
   if(empty($bases)) $bases=FALSE;
   return $bases;
}

/* Find the protocol version used by the directory (take the highest one if
   there's several value)
 */
function tch_getLdapVersion($ldap_handle, $rootDSE)
{
   $ldap_version=0;

   if(isset($rootDSE[0])) $rootDSE=$rootDSE[0];
   else return FALSE;

   if(is_array($rootDSE) && isset($rootDSE['supportedldapversion']) &&
         isset($rootDSE['supportedldapversion']['count']) &&
         $rootDSE['supportedldapversion']['count']>0) {
      for($i=0;$i<$rootDSE['supportedldapversion']['count'];$i++) {
         if(intval($rootDSE['supportedldapversion'][$i])>$ldap_version) {
            $ldap_version=intval($rootDSE['supportedldapversion'][$i]);
         }
      }
   }
   if($ldap_version==0) $ldap_version=FALSE;
   return $ldap_version;
}

/* Get all objectclasses available in the directory */
function tch_getLdapObjectClasses($ldap_handle, $rootDSE)
{
   $objectclasses=array();

   if(isset($rootDSE[0])) $rootDSE=$rootDSE[0];
   else return FALSE;

   if(is_array($rootDSE) && isset($rootDSE['subschemasubentry']) &&
         isset($rootDSE['subschemasubentry']['count']) &&
         $rootDSE['subschemasubentry']['count']>0) {
      /* Don't know if there can be many subschemaentry, but it doesn't hurt */
      for($i=0;$i<$rootDSE['subschemasubentry']['count'];$i++) {
         $subschemas=ldap_read($ldap_handle, $rootDSE['subschemasubentry'][$i],
               '(objectclass=*)', array('+'));
         if($subschemas) {
            $subschema=ldap_get_entries($ldap_handle, $subschemas);
            if($subschema) {
               $subschema=$subschema[0];
               for($j=0;$j<$subschema['objectclasses']['count'];$j++) {
                  /* Should match objectclass name */
                  $matches=array();
                  if(preg_match('/.*NAME\s*\'([a-zA-Z0-9]+)\'.*/',
                        $subschema['objectclasses'][$j], $matches)==1) {
                     $objectclasses[]=strtolower($matches[1]);
                  }
               }
            }
         }
      }
   }

   if(empty($objectclasses)) $objectclasses=FALSE;
   return $objectclasses;
}

function tch_canTryStartTls($ldap_handle, $rootDSE)
{
   return tch_ldapSupport($ldap_handle, LDAP_EXOP_START_TLS, 'features',
         $rootDSE);
}

function tch_ldapSupport($ldap_handle, $oid, $what, $rootDSE) 
{
   $ret = FALSE;
   if(!isset($rootDSE[0])) return $ret;

   $attr='';
   switch($what) {
      case 'features': $attr='supportedfeatures'; break;
      case 'control': $attr='supportedcontrol'; break;
      default: return $ret;
   }

   if(is_array($rootDSE[0]) && isset($rootDSE[0][$attr]) &&
         isset($rootDSE[0][$attr]['count']) &&
         $rootDSE[0][$attr]['count']>0) {
      for($i=0;$i<$rootDSE[0][$attr]['count'];$i++) {
         if($oid==$rootDSE[0][$attr][$i]) {
            $ret=TRUE; break;
         }
      }
   }
   return $ret;
}

function tch_ldapSupportPagedResultControl($ldap_handle, $rootDSE) 
{
   return tch_ldapSupport($ldap_handle, LDAP_CONTROL_PAGEDRESULTS, 'control',
         $rootDSE);
}

function tch_ldapCreatePagedResultControl($cookie='', $page_size=100, 
      $critical=FALSE, &$serverctrls)
{
   if(!is_array($serverctrls)) $serverctrls=array();
   if(!is_string($cookie) || is_null($cookie)) $cookie='';
   if(!is_integer($page_size)) $page_size=100;
   if($critical) $critical=TRUE; else $critical=FALSE;

   $ctrl=array('oid'=>LDAP_CONTROL_PAGEDRESULTS,
         'iscritical'=>$critical,
         'value'=>array('type'=>'sequence', 'value'=>array(
               array('type'=>'integer', 'value'=>$page_size),
               array('type'=>'octetstring', 'value'=>$cookie)
               )));
   $serverctrls[]=$ctrl;

   return TRUE;
}

function tch_ldapParsePagedResultControl(&$cookie, &$estimated, $serverctrls)
{
   if(!is_array($serverctrls)) return FALSE;

   foreach($serverctrls as $ctrl) {
      if($ctrl['oid']==LDAP_CONTROL_PAGEDRESULTS) {
         if(isset($ctrl['value']) && isset($ctrl['value']['type']) &&
               $ctrl['value']['type']=='sequence' &&
               isset($ctrl['value']['value']) &&
               isset($ctrl['value']['value'][0]) &&
               isset($ctrl['value']['value'][0]['type']) &&
               isset($ctrl['value']['value'][0]['value']) &&
               isset($ctrl['value']['value'][1]) &&
               isset($ctrl['value']['value'][1]['type']) &&
               isset($ctrl['value']['value'][1]['value'])) {
            $est=$ctrl['value']['value'][0];
            $cook=$ctrl['value']['value'][1];
            if($est['type']=='integer' && $cook['type']=='octetstring') {
               $estimated=$est['value'];
               $cookie=$cook['value'];
               return TRUE;
            }
         }
      }
   }

   return FALSE;
}

?>
