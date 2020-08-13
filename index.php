<?PHP
/* (c) 2012-2019 Etienne Bagnoud
   This file is part of saddr project. saddr is under the MIT license.

   See LICENSE file
 */
require('vendor/autoload.php');

define('SADDR_DEBUG_FILE', '/var/log/saddr/' . date('d-m-Y') . '.log');
if(!file_exists(SADDR_DEBUG_FILE)) {
   file_put_contents(SADDR_DEBUG_FILE, '-- START FILE ' . date('c') . "\n", LOCK_EX);
}

$_runtime_start=gettimeofday();
define('SADDR__', 1);

include(dirname(__FILE__).'/saddrErrors.php');


/* My own library, must be includable */
$tchetch_path=getenv('SADDR_TCHETCH_PATH');
if(!empty($tchetch_path) && is_readable($tchetch_path)) {
   include($tchetch_path);
} else {
   include('lib/tchetch/tch.php');
}
if(!defined('TCH__')) die('Missing tchetch/tch.php');

/* in saddr tree */
include(dirname(__FILE__).'/lib/saddr.php');

/* Various options */
date_default_timezone_set('UTC');
setlocale(LC_ALL, 'en_US.UTF-8');

/* Code start */
$Saddr=saddr_init();

/* Guess saddr base filename */
$saddr_filename=basename(__FILE__);
saddr_setBaseFileName($Saddr, $saddr_filename);

/* Include local index file */
if(tch_isIncludable('conf/saddr.index.local.php')) {
   include('conf/saddr.index.local.php');
} else {
   $local_ldap_bind_file=getenv('SADDR_LOCAL_INDEX');
   if(!empty($local_ldap_bind_file)) {
      if(tch_isIncludable($local_ldap_bind_file)) {
         include($local_ldap_bind_file);
      }
   }
}

/* Try to get username/password for LDAP server
 */
if(isset($_SERVER['REMOTE_USER']) && is_null(saddr_getUser($Saddr))) {
   saddr_setUser($Saddr, $_SERVER['REMOTE_USER']);
}
if(isset($_SERVER['PHP_AUTH_PW']) && is_null(saddr_getPass($Saddr))) {
   saddr_setPass($Saddr, $_SERVER['PHP_AUTH_PW']);
}

/* INIT LDAP */
$Ldap=saddr_prepareLdapConnection($Saddr);
if($Ldap==NULL) {
   die('Cannot connect to any LDAP server');
}

if(! saddr_loadMainConfiguration($Saddr)) {
   die('Cannot load configuration from LDAP server');
}

/* Search proxy to generate encrypted search query */
if(isset($_POST['saddrGoSearch'])) {
   $append_url='';
   if(isset($_GET['selected'])) {
      foreach($_GET['selected'] as $sel) {
         $append_url.='&selected[]='.rawurlencode($sel);
      }
   }
   if(isset($_POST['saddrGlobalSearch']) &&
         !empty($_POST['saddrGlobalSearch'])) {
      header('Location: '.saddr_getBaseFileName($Saddr).'?op=doGlobalSearch&search='.
            saddr_urlEncrypt($Saddr, $_POST['saddrGlobalSearch']) . $append_url);
      exit(0);
   } else if(isset($_POST['saddrTagSearch']) &&
         !empty($_POST['saddrTagSearch'])) {
      header('Location: '.saddr_getBaseFileName($Saddr).'?op=doTagSearch&search='.
            saddr_urlEncrypt($Saddr, $_POST['saddrTagSearch']) . $append_url);
      exit(0);
   } else {
      header('Location: ' . $_SERVER['HTTP_REFERER']);
      exit(0);
   }
}

/* INIT SMARTY */
$Smarty=new Smarty();

$tpl_dir=getenv('SADDR_SMARTY_TPL_DIR');
if(is_string($tpl_dir) && is_dir($tpl_dir) && is_readable($tpl_dir)) {
   $Smarty->setTemplateDir($tpl_dir);
} else {
   $Smarty->setTemplateDir(dirname(__FILE__).'/tpl/');
}
unset($tpl_dir);

$tmp=saddr_getTempDir($Saddr);
if (@mkdir($tmp.'/compile/')) {
   $Smarty->setCompileDir($tmp.'/compile/');
} else {
   $Smarty->setCompileDir('/tmp/');
}
if (@mkdir($tmp.'/cache/')) {
   $Smarty->setCacheDir($tmp.'/cache/');
} else {
   $Smarty->setCacheDir($tmp);
}

/* Register saddr plugin */
$Smarty->registerPlugin('modifier', 'ldap_url_get_url', 's2s_ldapUrlGetUrl');
$Smarty->registerPlugin('modifier', 'ldap_url_get_label','s2s_ldapUrlGetLabel');
$Smarty->registerPlugin('function', 'saddr_encrypt', 's2s_encUrl');
$Smarty->registerPlugin('function', 'saddr_list_module', 's2s_listModule');
$Smarty->registerPlugin('function', 'saddr_read_entry', 's2s_readEntry');
$Smarty->registerPlugin('function', 'saddr_class_message', 's2s_classOfMessage');
$Smarty->registerPlugin('function', 'saddr_entry', 's2s_displaySmartyEntry');
$Smarty->registerPlugin('block', 'saddr_when_module_available', 
      's2s_whenModuleAvailable');
$Smarty->registerPlugin('block', 'saddr_ifgroup', 's2s_ifgroup');
$Smarty->registerPlugin('function', 'saddr_dojo', 's2s_dojoPath');
$Smarty->registerPlugin('function', 'saddr_dijit_theme_path', 's2s_dijitThemePath');
$Smarty->registerPlugin('function', 'saddr_dijit_theme_name', 's2s_dijitThemeName');
$Smarty->registerPlugin('function', 'saddr_url', 's2s_generateUrl');
$Smarty->registerPlugin('function', 'saddr_available_modules', 's2s_availableModules');

saddr_setSmarty($Saddr, $Smarty);

$saddr_results=array(
      'display'=>'home.tpl',
      'handle'=>&$Saddr
      );

/* Selection management */
if(isset($_GET['selected'])) {
   $no_select=FALSE;
   if(isset($_GET['op'])) {
      if($_GET['op']=='clearSelect') $no_select=TRUE;
      if($_GET['op']=='deselect') {
         if(isset($_GET['id'])) {
            $deselect=saddr_urlDecrypt($Saddr, $_GET['id']);
         }
      }
   }
   if(! $no_select) {
      foreach($_GET['selected'] as $sel) {
         if(!isset($deselect) || $deselect != $sel) {
            saddr_selectID($Saddr, saddr_urlDecrypt($Saddr, $sel));
         }
      }
   }
}

/* Operations */
if(isset($_GET['op'])) {
   switch($_GET['op']) {
      case 'moduleConfigure':
      case 'moduleUnconfigure':
         if(isset($_GET['id'])) {
            $module=saddr_urlDecrypt($Saddr, $_GET['id']);
            if($_GET['op']=='moduleUnconfigure') {
               saddr_unconfigureModule($Saddr, $module);
            } else {
               saddr_configureModule($Saddr, $module);
            }
            header('Location: ' . saddr_getBaseFileName($Saddr) . '?op=modules');
            exit(0); 
         }
      case 'modules':
         $saddr_results['display']='modules.tpl';
         break;
      case 'list':
         if(isset($_GET['module'])) {
            $module=saddr_urlDecrypt($Saddr, $_GET['module']);
            if(is_string($module)) {
               $search=saddr_list($Saddr, $module);
               $saddr_results['display']='results.tpl';
               if(!empty($search)) {
                  $saddr_results['search_results']=$search;
               }
            }
         }
         break;
      case 'doTagSearch':
         if(isset($_GET['search'])) {
            $search_string=saddr_urlDecrypt($Saddr, $_GET['search']);
            if(is_string($search_string)) {
               $search=saddr_search($Saddr, $search_string,
                     array('tags', 'restricted_tags'));
               $saddr_results['op']=$_GET['op'];
               /* Reencrypt or you experience some troubles with the browser */
               $saddr_results['search']=saddr_urlEncrypt($Saddr, 
                     $search_string);
               $saddr_results['display']='results.tpl';
               if(!empty($search)) {
                  $saddr_results['search_results']=$search;
               }
            }
         }
         break;
      case 'doGlobalSearch':
         if(isset($_GET['search'])) {
            $search_string=saddr_urlDecrypt($Saddr, $_GET['search']);
            if(is_string($search_string)) {
               $search_op='=';
               if(strlen($search_string) > 0 && $search_string[0] == '%') {
                  $search_op='~=';
                  $search_string = substr($search_string, 1);
               }
               $search=saddr_search($Saddr, $search_string, array(),
                     array(), $search_op);
               $saddr_results['op']=$_GET['op'];
               /* Reencrypt or you experience some troubles with the browser */
               $saddr_results['search']=saddr_urlEncrypt($Saddr, 
                     $search_string);
               $saddr_results['display']='results.tpl';
               if(!empty($search)) {
                  $saddr_results['search_results']=$search;
               }
            }
         }
         break;
      case 'doSearchForCompany':
         $search_op='=';
      case 'doSearchByAttribute':
         if(!isset($search_op)) $search_op='=';

         $attribute='company';
         if(isset($_GET['attribute'])) {
            $attribute=saddr_urlDecrypt($Saddr, $_GET['attribute']);
         }
         if(isset($_GET['search'])) {
            $search_string=saddr_urlDecrypt($Saddr, $_GET['search']);
            if(is_string($search_string)) {
               $search=saddr_search($Saddr, $search_string, array($attribute),
                     array(), $search_op);
               $saddr_results['op']=$_GET['op'];
               /* Reencrypt or you experience some troubles with the browser */
               $saddr_results['search']=saddr_urlEncrypt($Saddr, 
                     $search_string);
               $saddr_results['display']='results.tpl';
               if(!empty($search)) {
                  $saddr_results['search_results']=$search;
               }
            }
         }
         break;
      case 'doAddOrEdit':
         $smarty_entry=array();

         foreach($_POST as $k=>$v) {
            if(!empty($v) && is_string($v) && $v!='') {
               $smarty_entry[$k]=$v;
            } else if(is_array($v) && !empty($v)) {
               $smarty_entry[$k]=array();
               foreach($v as $_v) {
                  if(is_string($_v) && !empty($_v)) {
                     $smarty_entry[$k][]=$_v;
                  }
               }
               if(empty($smarty_entry[$k])) { unset($smarty_entry[$k]); }
            }
         }
         foreach($_FILES as $k=>$v) {
            /* Dojo uploader add a 's' at the end of the field name !!! */
            if($k[strlen($k)-1] == 's') {
               $key = substr($k, 0, strlen($k)-1);
            } else {
               $key = $k;
            }
            if(!empty($v['tmp_name'])) {
               if($v['error'][0]==UPLOAD_ERR_OK) {
                  $smarty_entry[$key]=file_get_contents($v['tmp_name']);
               }
            }
         }

         if(isset($smarty_entry['id']) && is_string($smarty_entry['id'])) {
            $dn=saddr_urlDecrypt($Saddr, $smarty_entry['id']);
            $smarty_entry['dn']=$dn;    
            $ret=saddr_modify($Saddr, $smarty_entry);
            if($ret[1]) {
               saddr_setUserMessage($Saddr, 'Modification successful', 
                     SADDR_MSG_GOOD);
            } else {
               saddr_setUserMessage($Saddr, 'Modification failed');
            }
         } else {
            $ret=saddr_add($Saddr, $smarty_entry);
            if($ret[1]) {
               saddr_setUserMessage($Saddr, 'Addition successful', 
                     SADDR_MSG_GOOD);
            } else {
               saddr_setUserMessage($Saddr, 'Addition failed');
            }
         }

         if(!empty($ret[0])) {
            $dn=$ret[0];
            $entry=saddr_read($Saddr, $dn);
            if(!empty($entry)) {
               $tpl=saddr_getTemplates($Saddr, $entry['module']);
               $saddr_results['display']=saddr_getModuleDirectory($Saddr).'/'.
                  $entry['module'].'/'.$tpl['view'];
               $saddr_results['search_results']=$entry;
               $saddr_results['__delete']=saddr_urlEncrypt($Saddr, 
                     time() . " $dn");
            }
         }
         break;
      case 'select':
         if(isset($_GET['id'])) {
            saddr_selectID($Saddr, saddr_urlDecrypt($Saddr, $_GET['id']));
         }
      case 'view':
         $dn=saddr_urlDecrypt($Saddr, $_GET['id']);
         $entry=saddr_read($Saddr, $dn);
         if(!empty($entry)) {
            $tpl=saddr_getTemplates($Saddr, $entry['module']);
            $saddr_results['display']=saddr_getModuleDirectory($Saddr).'/'.
               $entry['module'].'/'.$tpl['view'];
            $saddr_results['search_results']=$entry;
            $saddr_results['__delete']=saddr_urlEncrypt($Saddr, 
                  time() . " $dn");
            if(saddr_isIDSelected($Saddr, $dn)) {
               $saddr_results['__selected']=TRUE;
            }
         }  
         break;
      case 'preAdd':
         $preadd_entry=array();
         foreach($_REQUEST as $name=>$value) {
            if($name!='id' && $name!='module') {
               if(!is_array($value)) {
                  $preadd_entry[$name][]=$value;
               } else {
                  foreach($value as $v) {
                     $preadd_entry[$name][]=$v;
                  }
               }
            }
         }
      case 'copy':
         if(isset($_GET['id'])) {
            $dn = saddr_urlDecrypt($Saddr, $_GET['id']);
            $preadd_entry = saddr_read($Saddr, $dn);
            unset($preadd_entry['id']);
            unset($preadd_entry['dn']);
            unset($_GET['id']);
         }
      case 'addOrEdit':
         $entry=array();
         if(isset($_GET['id'])) {
            /* we edit, not just add */
            $dn=saddr_urlDecrypt($Saddr, $_GET['id']);
            $entry=saddr_read($Saddr, $dn);
            $entry['__edit']=TRUE;
            if(!empty($entry)) {
               $saddr_results['search_results']=$entry;
               $saddr_results['__delete']=saddr_urlEncrypt($Saddr, 
                     time() . " $dn");
               if(saddr_isIDSelected($Saddr, $dn)) {
                  $saddr_results['__selected']=TRUE;
               }
            }
         } else {
            if(!empty($preadd_entry)) $entry=$preadd_entry;
            $entry['__edit']=TRUE;
            $module=saddr_getDefaultModuleName($Saddr);
            if(isset($_GET['module'])) $module=$_GET['module'];
            else if(isset($_POST['module'])) $module=$_POST['module'];
            else if(isset($entry['module'])) $module=$entry['module'];
            if(!saddr_isModuleAvailable($Saddr, $module)) {
               $entry['module']=saddr_getDefaultModuleName($Saddr);
            } else {
               $entry['module']=$module;
            }
            $saddr_results['search_results']=$entry;
         }

         $tpl=saddr_getTemplates($Saddr, $entry['module']);
         $saddr_results['display']=saddr_getModuleDirectory($Saddr).'/'.
            $entry['module'].'/'.$tpl['edit'];
         break;
      case 'doDelete':
         $delete_confirmed=TRUE;
         /* no break */
      case 'delete':
         if(isset($_GET['timed_id'])) {
            $tid=saddr_urlDecrypt($Saddr, $_GET['timed_id']);
            if(is_string($tid)) {
               $time_id=explode(' ', $tid, 2);
               if(is_numeric($time_id[0])) {
                  if(isset($time_id[1]) && is_string($time_id[1])) {
                     if(time()-$time_id[0]<=300) {
                        if(!isset($delete_confirmed)) {
                           $entry=saddr_read($Saddr, $time_id[1]);
                           if(!empty($entry)) {
                              $saddr_results['__delete']=saddr_urlEncrypt(
                                    $Saddr, time() . " ". $time_id[1]);
                              $saddr_results['search_results']=$entry;
                              $saddr_results['display']='delete.tpl';
                           }
                        } else {
                           if(saddr_delete($Saddr, $time_id[1])) {
                              saddr_setUserMessage($Saddr, 
                                    'Deletion successful', 
                                    SADDR_MSG_GOOD);
                           } else {
                              saddr_setUserMessage($Saddr, 
                                    'Deletion failed'); 
                           }
                           $saddr_results['display']='home.tpl';
                        }
                     }
                  }
               }
            }
         }
         break;
   }
}

if($saddr_results['display']=='home.tpl') {
   $home_display=array();
   $modules=saddr_getModulesStates($Saddr);
   foreach($modules['configured'] as $mod) {
      $tpl=saddr_getTemplates($Saddr, $mod['module']);
      if(isset($tpl['home'])) {
         $home_display[]=array(saddr_getModuleDirectory($Saddr).'/'.
            $mod['module'].'/'.$tpl['home'], $mod['module'], $mod['name']);
      }
   }
   $saddr_results['__home_display'] = $home_display;
}
$_runtime_stop=gettimeofday();
$saddr_results['__runtime']=round(((($_runtime_stop['sec'] * 1000) + ($_runtime_stop['usec'] / 1000)) -
      (($_runtime_start['sec'] *  1000) + ($_runtime_start['usec'] / 1000))), 3);
saddr_getSmarty($Saddr)->assign('saddr', $saddr_results);

/* DISPLAY */

saddr_getSmarty($Saddr)->display('index.tpl');
?>
