<?PHP

function _saddr_mainConfEntryToConf(&$saddr, $ldap, $entry) 
{
   $ret=FALSE;
   $ber=NULL;
   for(
         /* $ber is not needed */
         $attr=ldap_first_attribute($ldap, $entry, $ber);
         $attr!=FALSE;
         $attr=ldap_next_attribute($ldap, $entry, $ber)
      ) {

      $value=ldap_get_values($ldap, $entry, $attr);
      if($value && $value['count']>0) {
         switch(strtolower($attr)) {
            /* Directory server enforce needed value if the right schema is used
             */
            case 'saddrbase':
               for($i=0;$i<$value['count'];$i++) {
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
               for($i=0;$i<$value['count'];$i++) {
                  saddr_loadModuleConfiguration($saddr, $value[$i]);
               }
               break;
         }
      }
   }

   return $ret;
}

function saddr_loadModuleConfiguration(&$saddr, $dn)
{
   $ret=FALSE;

   $ldap=saddr_getLdap($saddr);
   if($ldap) {
      $res=ldap_read($ldap, $dn, '(objectclass=saddrModuleConfiguration)');
      if($res) {
         $entry=ldap_first_entry($ldap, $res);
         if($entry) {
            $module=array(
                  'name' => NULL,
                  'path' => NULL,
                  'human_name' => NULL,
                  'base' => NULL
                  );

            $dn=ldap_get_dn($ldap, $entry);
            for(
                  $attr=ldap_first_attribute($ldap, $entry);
                  $attr!=FALSE;
                  $attr=ldap_next_attribute($ldap, $entry)
               ) {

               $value=ldap_get_values($ldap, $entry, $attr);
               if($value && $value['count']>0) {
                  switch(strtolower($attr)) {
                     case 'saddrconfigname':
                        $module['name']=$value[0];
                        break;
                     case 'saddrmodulepath':
                        $module['path']=$value[0];
                        break;
                     case 'saddrmodulename':
                        $module['human_name']=$value[0];
                        break;
                     case 'saddrbase':
                        $module['base']=$value[0];
                        break;
                  }
               }
            }
            
            if(!is_null($module['name']) && !is_null($module['path'])) {
               if(saddr_addModule($saddr, $module['name'], $module['path'],
                  $dn, $module['human_name'])) {
                  if(!is_null($module['base'])) {
                     saddr_setModuleBase($saddr, $module['name'],
                           $module['base']);
                  }
                  $ret=TRUE;
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
      if(is_null($dn)) $dn=saddr_getConfigurationDn($saddr);

      $filter='(objectclass=saddrConfiguration)';
      if(!is_null($dn)) {
         $s_res=ldap_read($ldap, $dn, $filter);
         if($s_res && ldap_count_entries($ldap, $s_res)==1) {
            $entry=ldap_first_entry($ldap, $s_res);
         }
      } 
      if(isset($entry)) {
         saddr_setConfigurationDn($saddr, ldap_get_dn($ldap, $entry));
         $ret=_saddr_mainConfEntryToConf($saddr, $ldap, $entry);
      }
   }

   return $ret;
}

function saddr_findNamingContext(&$saddr)
{
   $ret=FALSE;
   $filter='(objectclass=saddrConfiguration)';

   /* If conf and naming context has already been found, don't search again
    */
   if(saddr_getNamingContext($saddr)!=NULL &&
         saddr_getConfigurationDn($saddr)!=NULL) return TRUE;

   $ldap=saddr_getLdap($saddr);
   if($ldap) {
      $rootDSE=saddr_getLdapRootDse($saddr);
      if(! $rootDSE) {
         $rootDSE=tch_getRootDSE($ldap);
         if($rootDSE) {
            saddr_setLdapRootDse($saddr, $rootDSE);
         }
      }
      /* rootDSE might be set now */
      if($rootDSE) {
         $bases=tch_getLdapBases($ldap, $rootDSE);
         foreach($bases as $base) {
            $s_res=ldap_search($ldap, $base, $filter);
            if($s_res) {
               /* We handle only one configuration, this might change */
               if(ldap_count_entries($ldap, $s_res) > 0) {
                  $entry=ldap_first_entry($ldap, $s_res);
                  saddr_setNamingContext($saddr, $base);
                  saddr_setConfigurationDn($saddr, ldap_get_dn($ldap, $entry));
                  $ret=TRUE;
                  break;
               }
            }
         }
      }
   }
   return $ret;
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
         $s_res=ldap_read($ldap, $cdn, $filter, array('saddrModules'));
         if($s_res) {
            $entry=ldap_first_entry($ldap, $s_res);
            if($entry) {
               $values=ldap_get_attributes($ldap, $entry);
               if($values && isset($values['saddrModules'])) {
                  for($i=0;$i<$values['saddrModules']['count'];$i++) {
                     $modules[]=$values['saddrModules'][$i];
                  }
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
         $s_res=ldap_search($ldap, $nctx, $filter,
               array('saddrModuleName', 'saddrConfigName'));
         if($s_res) {
            for(
                  $e=ldap_first_entry($ldap, $s_res);
                  $e!=FALSE;
                  $e=ldap_next_entry($ldap, $e)) {
               
               $module=array('module'=>NULL, 'name'=>NULL, 'dn'=>NULL);
               for(
                     $attr=ldap_first_attribute($ldap, $e);
                     $attr!=FALSE;
                     $attr=ldap_next_attribute($ldap, $e)) {
                  $value=ldap_get_values($ldap, $e, $attr);
                  if($value && $value['count']>0) {
                     $module['dn']=ldap_get_dn($ldap, $e);
                     switch(strtolower($attr)) {
                        case 'saddrmodulename':
                           $module['name']=$value[0];
                           break;
                        case 'saddrconfigname':
                           $module['module']=$value[0];
                           break;
                     }
                  }
               } 
               if(is_null($module['name'])) $module['name']=$module['module'];
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
