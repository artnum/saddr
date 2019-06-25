<?PHP
include('../../tch.php');


$var='test \\\' test';
tch_stripSlashes($var);
echo $var.PHP_EOL;

$var=array('test \\\' test',
      array('test \\\' test', 'test \\\' test'));
tch_stripSlashes($var);
print_r($var);

?>
