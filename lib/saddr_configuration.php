<?PHP
/* (c) 2012-2020 Etienne Bagnoud <etienne@artnum.ch>
   This file is part of saddr project. saddr is under the MIT license.

   See LICENSE file
 */

function _saddr_mainConfEntryToConf(&$saddr, $entry) 
{
   foreach($entry->eachAttribute() as $attr => $value) {
      switch(strtolower($attr)) {
         /* Directory server enforce needed value if the right schema is used
            */
         case 'saddrbase':
            for($i = 0; $i < count($value); $i++) {
               saddr_setLdapBase($saddr, $value[$i]);
               $ret=TRUE;
            }
            break;
         case 'saddrdojowebpath':
            saddr_setJsDojoPath($saddr, $value[0]);
            break;
         case 'saddrdijitthemewebpath':
            saddr_setDijitThemePath($saddr, $value[0]);
            break;
         case 'saddrdijitthemename':
            saddr_setDijitThemeName($saddr, $value[0]);
            break;
         case 'saddrmodules':
            for($i = 0; $i < count($value); $i++) {
               saddr_loadModuleConfiguration($saddr, $value[$i]);
            }
            break;
      }
   }

   return true;
}

function saddr_loadModuleConfiguration(&$saddr, $dn)
{
   $ldap = saddr_getLdap($saddr);
   if($ldap) {
      $res = $ldap->search($dn, '(objectclass=saddrModuleConfiguration)', ['*'], 'base');
      foreach($res as $result) {
         for($entry = $result->firstEntry(); $entry; $entry = $result->nextEntry()) {
            $name = $entry->get('saddrconfigname');
            $hname = $entry->get('saddrmodulename');
            $path = $entry->get('saddrmodulepath');
            
            $base = $entry->get('saddrbase');
            $module = [
                  'name' => $name ? $name[0] : null,
                  'path' => $path ? $path[0] : null,
                  'human_name' => $hname ? $hname[0] : null,
                  'base' => $base ? $base : saddr_getLdapBase($saddr)
               ];
               
            if(!is_null($module['name']) && !is_null($module['path'])) {
               if(saddr_addModule($saddr, $module['name'], $module['path'],
                  $entry->dn(), $module['human_name'])) {
                  if(!is_null($module['base'])) {
                     saddr_setModuleBase($saddr, $module['name'],
                           $module['base']);
                  }
               }
            }
         }
      }
   }
   return TRUE;
}

function saddr_loadMainConfiguration(&$saddr)
{
   $ret=FALSE;

   $ldap=saddr_getLdap($saddr);
   if($ldap) {
      /* Try to get conf DN, if not try to find dn and naming context, try to
         get conf DN again, if not cannot load configuration ...
       */
      if(is_null($dn=saddr_getConfigurationDn($saddr))) {
         saddr_findNamingContext($saddr);
      }
      if(is_null($dn)) { $dn = saddr_getConfigurationDn($saddr); }
      
      $filter='(objectclass=saddrConfiguration)';
      $entry = null;
      if($dn) {
         $s_res = $ldap->search($dn, $filter, ['*'], 'base');
         foreach ($s_res as $result) {
            if($result->count() === 1) {
               $entry = $result->firstEntry();
            }
         }
      } 
      if($entry) {
         saddr_setConfigurationDn($saddr, $entry->dn());
         $ret = _saddr_mainConfEntryToConf($saddr, $entry);
      }
   }

   return $ret;
}

function saddr_findNamingContext(&$saddr)
{
   $filter='(objectclass=saddrConfiguration)';

   /* If conf and naming context has already been found, don't search again
    */
   if(saddr_getNamingContext($saddr)!=NULL &&
         saddr_getConfigurationDn($saddr)!=NULL) return TRUE;

   $ldap = saddr_getLdap($saddr);
   $ctxs = $ldap->getNamingContexts();
   foreach ($ctxs as $ctx) {
      $results = $ldap->search($ctx, $filter, ['*'], 'sub');     
      foreach ($results as $result) {
         if ($result->count() > 0) {
            $entry = $result->firstEntry();
            saddr_setNamingContext($saddr, $ctx);
            saddr_setConfigurationDn($saddr, $entry->dn());
            return true;
         }
      }
   }
   return false;
}

function saddr_getConfiguredModules(&$saddr)
{
   $modules=array();
   $filter='(objectclass=saddrConfiguration)';

   $ldap=saddr_getLdap($saddr);
   if($ldap) {
      $cdn=saddr_getConfigurationDn($saddr);
      if(is_null($cdn)) saddr_getNamingContext($saddr);
      $cdn=saddr_getConfigurationDn($saddr);
      
      if(!is_null($cdn)) {
         $s_res = $ldap->search($cdn, $filter, ['saddrModules'], 'base');
         foreach ($s_res as $result) {
            if ($result->count() <= 0) { continue; }
            $entry = $result->firstEntry();
            
            if(($value = $entry->get('saddrmodules')) !== null) {
               for($i = 0; $i < count($value); $i++) {
                  $modules[] = $value[$i];
               }
            }
            
         }
      }
   }
   return $modules;
}

/* Search the whole naming context in order to find any module in there */
function saddr_getAvailableModules(&$saddr)
{
   $modules=array();
   $filter='(objectclass=saddrModuleConfiguration)';

   $ldap=saddr_getLdap($saddr);
   if($ldap) {
      $nctx=saddr_getNamingContext($saddr);
      if(is_null($nctx))
         saddr_findNamingContext($saddr);
      $nctx=saddr_getNamingContext($saddr);

      if(!is_null($nctx)) {
         $s_res = $ldap->search($nctx, $filter, ['saddrmodulename', 'saddrconfigname'], 'sub');
         foreach ($s_res as $result) {
            for($e = $result->firstEntry(); $e; $e = $result->nextEntry()) {
               $confname = $e->get('saddrconfigname');
               $name = $e->get('saddrmodulename');
               
               $module = [
                  'module' => $confname ? $confname[0] : null, 
                  'name' => $name ? $name[0] : null, 
                  'dn' => $e->dn()
               ];
               
               if(is_null($module['name'])) { $module['name']=$module['module']; }
               $modules[]=$module;
            }
         }
      }
   }
   return $modules;
}

function saddr_getModulesStates(&$saddr)
{
   $states=array('configured'=>array(), 'available'=>array());
   $available_modules=saddr_getAvailableModules($saddr);
   $configured_modules=saddr_getConfiguredModules($saddr);
   
   foreach($available_modules as $module) {
      if(in_array($module['dn'], $configured_modules)) {
         $states['configured'][]=$module;
      } else {
         $states['available'][]=$module;
      }
   }

   return $states;
}

function saddr_unconfigureModule(&$saddr, $module)
{
   return saddr_doConfigureModule($saddr, $module, 1);
}

function saddr_configureModule(&$saddr, $module)
{
   return saddr_doConfigureModule($saddr, $module, 2);
}

function saddr_doConfigureModule(&$saddr, $module, $what)
{
   $ret=FALSE;
   if($module) {
      $modules=saddr_getModulesStates($saddr);
      $to_configure=NULL;
      switch($what) {
         case 1: $modules=$modules['configured']; break;
         case 2: $modules=$modules['available']; break;
         default: return FALSE;
      }

      foreach($modules as $mod) {
         if(strcmp($mod['dn'], $module)==0) {
            $to_configure=$mod['dn'];
            break;
         }
      }

      if(!is_null($to_configure)) {
         $ldap=saddr_getLdap($saddr);
         if($ldap) {
            /* if this point is reached, we have configuration dn set as it's
               needed in previous operation to succeed
             */
            $cdn=saddr_getConfigurationDn($saddr);
            switch($what) {
               case 1:
                  $ret=ldap_mod_del($ldap, $cdn,
                        array('saddrModules'=>$to_configure));
                  break;
               case 2:
                  $ret=ldap_mod_add($ldap, $cdn,
                        array('saddrModules'=>$to_configure));
                  break;
            }
         }
      }
   }

   return $ret;
}


?>
