<?PHP
/* (c) 2020 Etienne Bagnoud
   This file is part of saddr project. saddr is under the MIT license.

   See LICENSE file
 */

function ext_saddr_category_get_fn_list()
{
   return array(
         'getClass'=>'ext_category_getClass',
         'getAttrs'=>'ext_category_getAttrs',
         'getTemplates'=>'ext_category_getTemplates',
         'getRdnAttributes'=>'ext_category_getRdnAttributes',
         'getHashGeneratedAttributes' => 'ext_category_getHashGeneratedAttributes'
         );
}

function ext_category_getAttrs()
{
   return array(
       'uniqueidentifier' => 'uniqueidentifier',
      'description'=>array('description', '_saddr_res1'),
      'cn'=>array('name', '_saddr_res0'),
      );
}

function ext_category_getClass()
{
   return array(
         'search'=>'category',
         'structural'=>'category', 
         'auxiliary'=> array());
}

function ext_category_getTemplates()
{
   return array('view'=>'view_edit.tpl',
         'edit'=>'view_edit.tpl');
}

function ext_category_getRdnAttributes()
{
   return array('principal'=>'uniqueidentifier');
}

function ext_category_getHashGeneratedAttributes() {
   return ['uniqueidentifier'];
}

?>