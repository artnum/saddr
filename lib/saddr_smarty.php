<?PHP
/* (c) 2012 Etienne Bagnoud
   This file is part of saddr project. saddr is under the MIT license.

   See LICENSE file
 */

function s2s_ldapUrlGetUrl($ldap_url)
{
   $ret=explode(' ', $ldap_url, 2);
   return $ret[0];
}
function s2s_ldapUrlGetLabel($ldap_url)
{
   $ret=explode(' ', $ldap_url, 2);
   if(isset($ret[1])) {
      return $ret[1];
   } else {
      return '';
   }
}

function s2s_encUrl($params, $smarty)
{
   if(isset($params['value'])) {
      $saddr=$smarty->getTemplateVars('saddr');
      if(isset($saddr['handle'])) {
         $enc=saddr_urlEncrypt($saddr['handle'], $params['value']);
         if(isset($params['var'])) {
            $smarty->assign($params['var'], $enc);
            return;
         } else {
            return $enc;
         }
      }
   }

   return;
}

function s2s_dojoPath($params, $smarty)
{
   $saddr=$smarty->getTemplateVars('saddr');
   return saddr_getJsDojoPath($saddr['handle']);
}

function s2s_dijitThemePath($params, $smarty)
{
   $saddr=$smarty->getTemplateVars('saddr');
   return saddr_getDijitThemePath($saddr['handle']);
}

function s2s_dijitThemeName($params, $smarty)
{
   $saddr=$smarty->getTemplateVars('saddr');
   return saddr_getDijitThemeName($saddr['handle']);
}

function s2s_readEntry($params, $smarty)
{
   $saddr=$smarty->getTemplateVars('saddr');

   if(!isset($params['var'])) return;

   if(isset($params['id'])) {
      $id=$params['id'];
      $dn=saddr_urlDecrypt($saddr['handle'], $id);
      $attributes=array();
      if(isset($params['attributes'])) {
         $attributes=$params['attributes'];
      }
      $entry=saddr_read($saddr['handle'], $dn, $attributes);
      $smarty->assign($params['var'], $entry);
      return;
   }

   $smarty->assign($params['var'], array());
   return;
}

function s2s_availableModules($params, $smarty) {
   $saddr=$smarty->getTemplateVars('saddr');
   $modules=saddr_getModulesStates($saddr['handle']); 
   $smarty->assign($params['result'], $modules);
}  

function s2s_listModule($params, $smarty)
{
   if(isset($params['module'])) {
      $module=$params['module'];
      $attributes=array(); /* empty array is all attributes */
      if(isset($params['attributes'])) {
         $attributes=$params['attributes'];
      }
      if(!isset($params['var'])) {
         return;
      }
      $saddr=$smarty->getTemplateVars('saddr');
      if(!isset($saddr['handle'])) {
         $smarty->assign($params['var'], array());
         return;
      }
      $res=saddr_list($saddr['handle'], $module, $attributes);
      $smarty->assign($params['var'], $res);
      return;
   }
   if(isset($params['var'])) {
      $smarty->assign($params['var'], array());
   }
   return;
}

/* Display content of that block only if the module is available in saddr. It
   allows to create some sort of dependency between modules.
 */
function s2s_whenModuleAvailable($params, $content, $smarty)
{
   if(isset($params['module']) && is_string($params['module'])) {
      $saddr=$smarty->getTemplateVars('saddr');
      if(isset($saddr['handle'])) {
         if(saddr_isModuleAvailable($saddr['handle'], $params['module'])) {
            return $content;
         } 
      }
   }
}

/* Display content of the block only if at least one member of the group has
   been set. This allows to have, for examples, titles not displayed when 
   there's nothing below
 */
function s2s_ifGroup($params, $content, $smarty)
{
   if(!isset($params['group'])) return $content;
   if(!is_string($params['group'])) return $content;
   $saddr=$smarty->getTemplateVars('saddr');
   if(!isset($saddr['search_results'])) return $content;
   $e=$saddr['search_results'];
   if(isset($e['__edit'])) return $content;
   if(!isset($e['__group'])) return $content;
   if(!isset($e['__group'][$params['group']])) return $content;
   if(!isset($e['__group'][$params['group']]['nonempty'])) return $content;
   if($e['__group'][$params['group']]['nonempty']) return $content;
   return '';
}

function s2s_classOfMessage($params, $smarty) 
{
   if(isset($params['errno'])) {
      switch($params['errno']) {
         case 0x0000: return 'saddr_msgGood';
         default:
         case 0x0001: return 'saddr_msgError';
         case 0x0002: return 'saddr_msgWarning';
         case 0x0004: return 'saddr_msgInfo';
         case 0x0008: return 'saddr_msgDebug';
      }
   } else {
      return 'saddr_msgError';
   }
}

/* Generate URL for each saddr operation. If encrypt is set, sensible arguments
   are encrypted
 */
function s2s_generateUrl($params, $smarty)
{
   $saddr=$smarty->getTemplateVars('saddr');
   $base_filename=saddr_getBaseFileName($saddr['handle']);

   if(isset($params['encrypt'])) {
      foreach(['id', 'search', 'attribute', 'module', 'leaf', 'leafvalue'] as $p) {
         if(isset($params[$p])) {
            $params[$p]=s2s_encUrl(array('value'=>$params[$p]), $smarty);
         }
      }
   }

   $url=$base_filename;
   if(isset($params['op'])) {
      switch($params['op']) {
         default: break;
         case 'moduleConfigure':
         case 'moduleUnconfigure':
            $url.='?op='.$params['op'].'&id='.$params['id'];
            break;
         case 'modules':
            $url.='?op='.$params['op'];
            break;
         case 'select':
            $url.='?op='.$params['op'].'&id='.$params['id'];
            break;
         case 'list':
            $url.='?op='.$params['op'].'&module='.$params['module'];
            break;
         case 'preAdd':
            if(isset($params['module'])) {
               $append_url='&module=' . $params['module'];
            }
         case 'doAddOrEdit':
            $url.='?op='.$params['op'];
            if(isset($append_url)) {
               $url.=$append_url;
            }
            break;
         case 'addOrEdit':
         case 'copy':
         case 'view':
            if(isset($params['id'])) {
               $url.='?op='.$params['op'].'&id='.$params['id'];
            }
            break;
         case 'doTagSearch':
         case 'doGlobalSearch':
         case 'doSearchForCompany':
            if(isset($params['search'])) {
               $url.='?op='.$params['op'].'&search='.$params['search'];
            }
            break;
         case 'doSearchByAttribute':
         case 'doSearchByRecursiveAttribute':
            if(isset($params['search']) && isset($params['attribute'])) {
               $url.='?op=' . $params['op'] . '&attribute='.$params['attribute'].
                  '&search='.$params['search'];
               if (!empty($params['leaf'])) {
                  $url .= '&leaf=' . $params['leaf'];
               }
               if (!empty($params['leafvalue'])) {
                  $url .= '&leafvalue=' . $params['leafvalue'];
               }
            }
            break;
         case 'doDelete':
         case 'delete':
            if(isset($params['timed_id'])) {
               $url.='?op='.$params['op'].'&timed_id='.$params['timed_id'];
            }
            break;
      }
   } 
   foreach($saddr['handle']['select'] as $s_id) {
      if($url==$base_filename) $url.='?selected[]=';
      else $url.='&selected[]=';
      $url.=s2s_encUrl(array('value' => $s_id), $smarty);
   } 
   return $url;
}

/* Generate HTML code, with Dojo add on, for any smarty entry. It handles
   generation of code for viewing and editing. 
   TODO Rewritte in a more structured way
 */
function s2s_displaySmartyEntry($params, $smarty)
{
   $saddr=$smarty->getTemplateVars('saddr');
   if(isset($saddr['search_results'])) {
      $entry=$saddr['search_results'];
   } else {
      $entry=array();
   }
   if(!isset($params['e'])) { return; }

   $type=array('dijitTextBox', 'dijit.form.TextBox');
   if(isset($params['type'])) {
      switch(strtolower($params['type'])) {
         default:
         case 'textbox': $type=array('dijitTextBox', 'dijit.form.TextBox'); 
                         break;
         case 'phone': $type = array('dijitTextBox', 'dijit.form.TextBox', 'tel:'); break;                        
         case 'textarea': $type=array('dijitTextArea', 'dijit.form.Textarea');
                          break;
         case 'date': $type=array('dijitDateTextBox', 'dijit.form.TextBox');
                      break;
         case 'tag': $type=array('saddrTagsArea', 'dijit.form.Textarea'); break;
         case 'sselect': $type=array('saddrSelect',
                               'dijit.form.FilteringSelect'); break;
         case 'jpeg': $type=array('saddrJpegImage', 'dojox.form.Uploader'); break;
      }
   }

   $edit_only=FALSE;
   if(isset($params['edit_only'])) {
      $edit_only=TRUE;
   }

   $label = null;
   if(isset($params['label']) && is_string($params['label'])) {
      $label=$params['label'];
   }

   $module = null;
   if(isset($params['module']) && is_string($params['module'])) {
      $module=$params['module'];
   }

   $required='';
   if(isset($params['must'])) {
      $required='required="true" data-dojo-props="required:true"';
   }

   $want='dn';
   if(isset($params['want'])) {
      $want=$params['want'];
   }

   $display_label_on_view = false;
   if(isset($params['labelonview'])) { $display_label_on_view = true; }

   $multi = false;
   $htmlMulti='';
   if(isset($params['multi'])) {
      $multi = true;
      $htmlMulti = ' data-multi="1" ';
   }
   

   $entryClass = " saddr_$params[e] ";
   if (!empty($params['module'])) {
      $entryClass .= "saddr_$params[module] ";
   }

   $html='<div class="saddr_groupOfValue">';
   if(isset($entry['__edit']) || $edit_only) {
      $name=$params['e'];
      if($multi) $name=$name.'[]';

      if(isset($entry[$params['e']])) {
         $v_entry=$entry[$params['e']];
      } else {
         $v_entry=array();
      }
      $v_entry[]='';
      
      $html.='<label for="'.
         $name.'" class="saddr_label">'.$label.'</label>';
      switch($type[0]) {
         case 'dijitDateTextBox':
         case 'dijitTextBox':
            foreach($v_entry as $index => $v) {
               $html.='<input type="text" name="'.$name.'" '. $htmlMulti .
                  'value="'.$v.'" '.
                  $required.' '.
                  'class="saddr_value saddr_textbox" />';
               if(!$multi) break;
               $required='';
            }
            break;
         case 'dijitTextArea':
            foreach($v_entry as $index => $v) {
               $html.='<textarea name="'.$name.'" '. $htmlMulti .
                  'class="saddr_value saddr_textarea" '.
                  $required.'>'.
                  $v.'</textarea>';
               if(!$multi) break;
               $required=''; /* Required value need, at least, 1 value */
            }
            break;
         case 'saddrTagsArea':
            $html.='<textarea name="'.$name.'" '. $htmlMulti . 
               'class="saddr_value saddr_textarea" '.
                $required.'>';
            foreach($v_entry as $index => $v) {
               $html.=$v;
               if($v!='') $html.=', ';
            }
            $html.='</textarea>';
            break;
         case 'saddrSelect':
            if($module !== null) { return; }
            $res=saddr_list($saddr['handle'], $module);
            foreach($v_entry as $index => $v) {
               if(isset($params['format']) &&
                     is_string($params['format'])) {
                  $html.='<select name="'.$name.'"'. $htmlMulti .
                     ' class="saddr_value saddr_select"'.
                     ' '.$required.'>';
                  $html.='<option value=""></option>';
                  foreach($res as $select) {
                     if($want=='dn') {
                        $svalue=$select[$want];
                     } else {
                        $svalue=$select[$want][0];
                     }
                     if (!empty($params['leafOnly']) && !empty($params['recurseOn'])) {
                        if (!empty(saddr_search($saddr['handle'], $svalue, [$params['recurseOn']]))) {
                           continue;
                        }
                     }
                     $html.='<option value="'.$svalue.'"';
                     if($svalue==$v) { $html.=' selected="1"'; }
                     $html.=' >';   
                     $html.= saddr_sprint($saddr['handle'], $params['format'], $select);
                     $path = [];
                     if (!empty($params['recurseOn'])) {
                        $parent = $select;
                        while (!empty($parent[$params['recurseOn']])) {
                           if ($parent[$params['recurseOn']][0] === $parent['dn']) { break; }
                           $parent = saddr_read($saddr['handle'], $parent[$params['recurseOn']][0]);
                           if (empty($parent)) { break; }
                           array_unshift($path, saddr_sprint($saddr['handle'], $params['format'], $parent));
                        }
                     }
                     if (!empty($path)) { $html .= ' (' . implode(' > ', $path) . ')'; }
                     $html.='</option>';
                  }
                  $html.='</select>';
               }
               if(!$multi) break;
               $required='';
            }
            break;
         case 'saddrJpegImage':
            if (isset($entry[$params['e']])) {
               $img_data = saddr_fixImageSize($saddr['handle'], $entry[$params['e']][0], 300, 80);
               if ($img_data !== null) {
                  $html.='<img src="data:image/jpeg;base64,'.base64_encode($img_data).'"  class="saddr_image '. $entryClass . '"/>';
               }
               $html.='<br/><label>Conserver l\'image <input type="checkbox" name="-'.$name.'" checked></label><br/>';
            }
            $html.='<input type="file" multiple="false" name="'.$name.'" label="Image to upload" ' . $htmlMulti . ' />';
            break;
      }
   } else {
      if(!isset($entry[$params['e']])) return;
      
      if($display_label_on_view) {
         if($label !== null) {
            $html.='<div class="saddr_label">'.$label.'</div>';
            $with_label=TRUE;
         }
      }

      $v_entry=$entry[$params['e']];
      $v_entry[]='';

      switch($type[0]) {
         default:
            foreach($entry[$params['e']] as $index => $v) {
               $linkedValue = null;
               if (isset($entry['__relation'][$params['e']])) {
                  if (!empty($entry['__relation'][$params['e']][$index])) {
                     $entryClass .= ' relation ' . $entry['__relation'][$params['e']][$index]['type'][0];
                     $linkedValue =  $entry['__relation'][$params['e']][$index]['source'];
                  }
               }
               if($type[0]=='dijitTextArea') $v=nl2br($v);
               $html.='<div class="saddr_value saddr_'.$type[0] . $entryClass;
               if(!isset($with_label)) {
                  $html.=' saddr_valueNoLabel';
               }
               $html.='">';
               if(!isset($type[2]) && isset($params['searchable'])) {
                  $html.='<a href="';
                  $html.=s2s_generateUrl(array('op'=>'doSearchByAttribute',
                           'attribute'=>$params['e'],
                           'search'=>$v,
                           'encrypt'=>1), $smarty);
                  $html.='" title="Search for '.$v.'">';

               }
               if (isset($type[2])) {
                  $html .= '<a class=" href="' . $type[2] . $v . '">';
               }
               $html.=$v;
               if(isset($type[2]) || isset($params['searchable'])) { $html.='</a>'; };
               
               if ($linkedValue !== null) {
                  $begin = $entry['__relation'][$params['e']][$index]['type'][1];
                  $end = $entry['__relation'][$params['e']][$index]['type'][2];
                  $title = '';
                  if ($begin !== null) {
                     $title .= 'Du ' . $begin->format('d.m.Y');
                  }
                  if ($end !== null) {
                     if (empty($title)) {
                        $title .= 'Jusqu\'au ';
                     } else {
                        $title .= ' jusqu\'au ';
                     }
                     $title .= $end->format('d.m.Y');
                  }
                  $html .= ' <a title="' . $title . '" class="relation" href="' . s2s_generateUrl([ 'op' => 'view', 'id' => $linkedValue], $smarty) . '"><i class="fas fa-link"></i></a>';
               }

               $html.='&nbsp;</div>';
               if(!$multi) break;
            }
            break;
         case 'saddrTagsArea':
            $html.='<div class="saddr_value saddr_'.$type[0] . $entryClass;
            if(!isset($with_label)) {
               $html.=' saddr_valueNoLabel';
            }
            $html.='">';
            foreach($entry[$params['e']] as $index => $v) {
               $html.='<a href="';
               $html.=s2s_generateUrl(array('op'=>'doSearchByAttribute',
                        'attribute'=>$params['e'],
                        'search'=>$v,
                        'encrypt'=>1), $smarty);
               $html.='" title="Search for '.$v.'">';
               $html.=$v;
               $html.='&nbsp;</a>';
            }
            $html.='</div>';
            break;
         case 'saddrSelect':
               foreach($entry[$params['e']] as $index => $v) {
                  $linkedValue = null;
                  if (isset($entry['__relation'][$params['e']])) {
                     if (!empty($entry['__relation'][$params['e']][$index])) {
                        $entryClass .= ' relation ' . $entry['__relation'][$params['e']][$index]['type'][0];
                        $linkedValue =  $entry['__relation'][$params['e']][$index]['source'];
                     }
                  }
                  $html.='<div class="saddr_value saddr_'.$type[0] . $entryClass;
                  if(!isset($with_label)) {
                     $html.=' saddr_valueNoLabel';
                  }
                  $html.='">';
                  if($want=='dn') {
                     $e=saddr_read($saddr['handle'], $v);
                     $html.='<a href="';
                     $html.=s2s_generateUrl(array('op'=>'view',
                              'id'=>$e['id']), $smarty);
                     $html.='">';
                     $html.=saddr_sprint($saddr['handle'], $params['format'],
                           $e);
                     $html.='</a>';
                  } else {
                     $res=saddr_list($saddr['handle'], $module);
                     foreach($res as $r) {
                        if($r[$want][0]==$v) {
                           $path = '';
                           if (!empty($params['recurseOn'])) {
                              $parent = $r;
                              while (!empty($parent[$params['recurseOn']])) {
                                 if ($parent[$params['recurseOn']][0] === $parent['dn']) { break; }
                                 $pid = $parent[$params['recurseOn']][0];
                                 $parent = saddr_read($saddr['handle'], $parent[$params['recurseOn']][0]);
                                 if (empty($parent)) { break; }
                                 $p = '';
                                 $p .= '<a href="' . s2s_generateUrl([
                                    'op' => 'doSearchByRecursiveAttribute',
                                    'search' => $pid,
                                    'attribute' => $params['recurseOn'],
                                    'leafvalue' => $want,
                                    'leaf' => $params['e'],
                                    'encrypt' => 1], $smarty) . '" title="Recherche \'' . saddr_sprint($saddr['handle'], $params['format'], $parent);
                                 $p .= '\'">' . saddr_sprint($saddr['handle'], $params['format'], $parent) . '</a> &gt; ';
                                 $path = $p . $path;
                              }
                           }
                           $html .= $path;
                           $html.='<a href="';
                           $html.=s2s_generateUrl(
                                 array('op'=>'doSearchByAttribute',
                                 'attribute'=>$want,
                                 'search'=>$v,
                                 'encrypt'=>1), $smarty);
                           $html.='" title="Recherche \'';
                           $html.=saddr_sprint($saddr['handle'], $params['format'], $r);
                           $html.='\'">';
                           $html.=saddr_sprint($saddr['handle'], $params['format'], $r);
                           $html.='</a>';
                           break;
                        }
                     }
                  }
                  if ($linkedValue !== null) {
                     $begin = $entry['__relation'][$params['e']][$index]['type'][1];
                     $end = $entry['__relation'][$params['e']][$index]['type'][2];
                     $title = '';
                     if ($begin !== null) {
                        $title .= 'Du ' . $begin->format('d.m.Y');
                     }
                     if ($end !== null) {
                        if (empty($title)) {
                           $title .= 'Jusqu\'au ';
                        } else {
                           $title .= ' jusqu\'au ';
                        }
                        $title .= $end->format('d.m.Y');
                     }
                     $html .= ' <a title="' . $title .'" class="relation" href="' . s2s_generateUrl([ 'op' => 'view', 'id' => $linkedValue], $smarty) . '"><i class="fas fa-link"></i></a>';
                  }
                  $html.='</div>';
                  if(!$multi) break;
               }
               break;
         case 'saddrJpegImage':
               $img_data = saddr_fixImageSize($saddr['handle'],
                     $entry[$params['e']][0], 300, 80);
               if ($img_data !== null) {
                  $html.='<img src="data:image/jpeg;base64,'.base64_encode($img_data).'"  class="saddr_image '. $entryClass . '"/>';
               }
            break;
      }
   }
   $html.='</div>';
   return $html;
}

?>
