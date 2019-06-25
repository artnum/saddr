<?PHP

function _saddr_set_entry(&$stack, $entry, $start, $end)
{
   $pos=array('start'=>$start, 'end'=>$end);
   $v=str_replace('@', '',  $entry);
   if(isset($stack[$v])) {
      $stack[$v][]=$pos;
   } else {
      $stack[$v]=array($pos);
   }
}

/* Return a string with smarty entry element replaced. Allows to have "optional"
   section which are not kept if entry inside are not available : 

     Location is @location@[ telephone is @telephone@] 
   
   returns 
     
     Location is My Location

   if there's no telephone
 */
function saddr_sprint(&$saddr, $format, $entry)
{
   $replace=array();
   $state = 0;

   $start = 0;
   $param = "";
   $opts_cnt=0;
   $opts_last_open=array();
   $opts=array();
   for($i=0;$i<strlen($format);$i++) {
      if($format{$i} == '@' || $format{$i}=='[' || $format{$i} == ']') {
         /* First char means we are closed ($state==0) and can't have escape
            char before
          */
         if($i == 0) {
            switch($format{$i}) {
               case '@':
                  $state = 1;
                  $start = 0;
                  break;
               case '[':
                  $opts_cnt++;
                  array_unshift($opts_last_open, $opts_cnt);
                  $opts[$opts_cnt-1]=array('start'=>0, 'end'=>-1, 'child'=>NULL);
                  break;
            }
         } else {
            if($format{$i-1} != '\\') {
               switch($format{$i}) {
                  case '@':
                     if($state) {
                        /* closing state */
                        _saddr_set_entry($replace, $param.$format{$i}, $start, $i);
                        $param="";
                        $start=0;
                        $state=0;
                     } else {
                        $start=$i;
                        $state=1;
                     }
                     break;
                  case '[':
                     $opts_cnt++;
                     if(isset($opts_last_open[0])) {
                        $opts[$opts_last_open[0]-1]['child']=$opts_cnt;
                     }
                     array_unshift($opts_last_open, $opts_cnt);
                     $opts[$opts_cnt-1]=array('start'=>$i, 'end'=>-1, 'child'=>NULL);
                     break;
                  case ']':
                     $x=array_shift($opts_last_open);
                     $opts[$x-1]['end']=$i;
                     break;
               }
            }
         }
      }
      if($state) {
         $param.=$format{$i};
      }
   }
   /* Syntax is wrong */
   if(count($opts_last_open)>0 || $state) return '';

   /* Remove part not available */
   $to_remove=array();
   foreach($replace as $key => $val) {
      if(!isset($entry[$key])) {
         $to_remove[]=$val;
         unset($replace[$key]);
      }
   }

   $opts_to_remove=array();
   $res_format=$format;
  
   $offset=0; 
   foreach($to_remove as $pos_s) {
      foreach($pos_s as $pos) {
         foreach($opts as $key => $opt) {
            $keep=TRUE;
            if(!in_array($key, $opts_to_remove)) {
               if($opt['start'] < $pos['start'] &&
                     $opt['end'] > $pos['start']) {
                  $keep=FALSE;
                  $opts_to_remove[]=$key;
                  $res_format=substr_replace($res_format, '', $opt['start'],
                        $opt['end']-$opt['start']+1);
                  $offset+=$opt['end']-$opt['start']+1;
                  $_opt=$opt;
                  while($_opt['child'] != NULL) {
                     $opts_to_remove[]=$_opt['child']-1;
                     $_opt=$opts[$_opt['child']-1];
                  }
               }
            } else {
               $keep=FALSE;
            }
            if($keep) { 
               $opts[$key]['start']-=$offset;
               $opts[$key]['end']-=$offset;
            }
         }   
      }
   }
   
   foreach($opts_to_remove as $o) unset($opts[$o]);

   foreach($opts as &$opt) {
      $res_format=substr_replace($res_format, '', $opt['start'], 1);
      foreach($opts as $k => $opt2) {
         if($opt['start']<$opt2['start']) $opts[$k]['start']--;
         if($opt['start']<$opt2['end']) $opts[$k]['end']--;
      }
      $res_format=substr_replace($res_format, '', $opt['end'], 1);
      foreach($opts as $k => $opt2) {
         if($opt['end']<$opt2['start']) $opts[$k]['start']--;
         if($opt['end']<$opt2['end']) $opts[$k]['end']--;
      }
   }

   foreach($replace as $k => $v) {
      if(is_array($entry[$k])) {
         $res_format=str_replace('@'.$k.'@', $entry[$k][0], $res_format, count($v));
      } else {
         $res_format=str_replace('@'.$k.'@', $entry[$k], $res_format, count($v));
      }
   }

   $res_format=str_replace('\\]', ']', $res_format);
   $res_format=str_replace('\\[', '[', $res_format);
   $res_format=str_replace('\\@', '@', $res_format);

   return $res_format;
}

?>
