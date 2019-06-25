<?PHP

function saddr_fixImageSize(&$saddr, $img_data, $max_width, $max_height) 
{
   $ret = NULL;
   if(!is_null($img_data)) {
      $outfile=saddr_getTempDir($saddr) . '/' .
         sha1($img_data . rand() . time()) . '.img.jpeg';
      $gdimg = imagecreatefromstring($img_data);
      if($gdimg) {
         $width = imagesx($gdimg);
         $height = imagesy($gdimg);
         if($width>$height) {
            $ratio =  $max_width / $width;
         } else {
            $ratio = $max_height / $height;
         }
         $new_width = $width * $ratio;
         $new_height = $height * $ratio;

         $new_img=imagecreatetruecolor($new_width, $new_height);
         if($new_img) {
            if(imagecopyresampled($new_img, $gdimg, 0, 0, 0, 0, 
                     $new_width, $new_height, $width, $height)) {
               imagedestroy($gdimg);
               if(imagejpeg($new_img, $outfile, 75)) {
                  imagedestroy($new_img);
                  $ret = file_get_contents($outfile);
                  @unlink($outfile);
               } else {
                  imagedestroy($new_img);
               }
            
            } 
         }
      }
   }
   return $ret;
}

?>
