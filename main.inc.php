<?php 
/*
Plugin Name: External Reference
Version: auto
Description: Add external reference to album
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=
Author: plg
Author URI: http://le-gall.net/pierrick
*/

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

global $prefixeTable;

// +-----------------------------------------------------------------------+
// | Define plugin constants                                               |
// +-----------------------------------------------------------------------+

defined('EXTREF_ID') or define('EXTREF_ID', basename(dirname(__FILE__)));
define('EXTREF_PATH' , PHPWG_PLUGINS_PATH.basename(dirname(__FILE__)).'/');
define('EXTREF_VERSION', 'auto');

add_event_handler('ws_add_methods', 'extref_add_methods');
function extref_add_methods($arr)
{
  $service = &$arr[0];

  $service->addMethod(
    'extref.categories.get',
    'ws_extref_categories_get',
    array(
      'category_id' => array('default' => null, 'type' => WS_TYPE_ID),
      'external_reference' => array('default' => null),
      'exact_match' => array('default'=>false, 'type'=>WS_TYPE_BOOL),
      'show_empty' => array('default'=>false, 'type'=>WS_TYPE_BOOL),
      ),
    'List external references on albums'
    );

  $service->addMethod(
    'extref.categories.set',
    'ws_extref_categories_set',
    array(
      'category_id' => array('default' => null, 'type' => WS_TYPE_ID),
      'external_reference' => array(),
      ),
    'Set external references on album',
    null,
    array('admin_only'=>true, 'post_only'=>true)
    );
}

function ws_extref_categories_get($params, &$service)
{
  $where = array('1=1'); // always true
  
  if (!empty($params['category_id']))
  {
    $where[] = 'id = '.$params['category_id'];
  }

  if (!empty($params['external_reference']))
  {
    if ($params['exact_match'])
    {
      $where[] = "external_reference = '".$params['external_reference']."'";
    }
    else
    {
      $where[] = "external_reference LIKE '%".$params['external_reference']."%'";
    }
  }
  
  if (!$params['show_empty'])
  {
    $where[] = 'external_reference IS NOT NULL';
  }

  $query = '
SELECT
    id,
    external_reference
  FROM '.CATEGORIES_TABLE.'
  WHERE '.implode(' AND ', $where).'
;';

  if ('rest' == $service->_responseFormat)
  {
    $categories = query2array($query);
  }
  else
  {
    $categories = query2array($query, 'id', 'external_reference');
  }
  
  return array('categories' => $categories);
}

function ws_extref_categories_set($params, &$service)
{
  // does the category really exist?
  $query = '
SELECT COUNT(*)
  FROM '. CATEGORIES_TABLE .'
  WHERE id = '.$params['category_id'].'
;';
  list($count) = pwg_db_fetch_row(pwg_query($query));
  if ($count == 0)
  {
    return new PwgError(404, 'category_id not found');
  }

  single_update(
    CATEGORIES_TABLE,
    array('external_reference' => $params['external_reference']),
    array('id' => $params['category_id'])
    );

  return true;
}
