<?PHP
/* (c) 2012 Etienne Bagnoud
   This file is part of "tchetch PHP lib". The project is under MIT License.

   See LICENSE file
 */

/* Check if the file exists in the include path */
function tch_isIncludable($file)
{
   $inc_path=ini_get('include_path');
   if($inc_path!=NULL && $inc_path!=FALSE) {
      $paths=explode(PATH_SEPARATOR, $inc_path);
      foreach($paths as $p) {
         if(is_dir($p)) {
            if(file_exists($p.'/'.$file)) return TRUE;
         }
      }
   }
   return FALSE;
}

?>
