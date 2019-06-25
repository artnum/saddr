<?PHP
function tch_stripSlashes(&$value)
{
   if(is_string($value)) {
      $value=stripslashes($value);
   } else if(is_array($value)) {
      foreach($value as & $d) {
         tch_stripSlashes($d);
      }
   }
}
?>
