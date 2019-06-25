<?PHP

function cmp($a, $b) {
   $va = isset($a[0]) ? $a[0] : '';
   $vb = isset($b[0]) ? $b[0] : '';

   if(isset($a['_saddr_res0'])) { $va = $a['_saddr_res0']; }
   else if(isset($a['name'])) { $va = $a['name']; }

   if(isset($b['_saddr_res0'])) { $vb = $b['_saddr_res0']; }
   else if(isset($b['name'])) { $vb = $b['name']; }

   if(is_array($va)) { $va = $va[0]; }
   if(is_array($vb)) { $vb = $vb[0]; }

   return strcasecmp(trim($va), trim($vb));
}

?>
