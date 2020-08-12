<?PHP
/* (c) 2012 Etienne Bagnoud
   This file is part of saddr project. saddr is under the MIT license.

   See LICENSE file
 */

function ext_saddr_country_get_fn_list()
{
   return array(
         'getClass'=>'ext_country_getClass',
         'getAttrs'=>'ext_country_getAttrs',
         'getTemplates'=>'ext_country_getTemplates',
         'getRdnAttributes'=>'ext_country_getRdnAttributes'
         );
}

function ext_country_getAttrs()
{
   return array(
      'businesscategory' => 'uniqueidentifier',
      'c'=>array('code', '_saddr_res1'),
      'description'=>array('name', '_saddr_res0'),
      );
}

function ext_country_getClass()
{
   return array(
         'search'=>'country',
         'structural'=>'country', 
         'auxiliary'=> array());
}

function ext_country_getTemplates()
{
   return array('view'=>'view_edit.tpl',
         'edit'=>'view_edit.tpl');
}

function ext_country_getRdnAttributes()
{
   return array('principal'=>'c');
}

?>
