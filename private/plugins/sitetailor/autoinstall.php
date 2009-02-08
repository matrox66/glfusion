<?php
// +--------------------------------------------------------------------------+
// | Site Tailor Plugin - glFusion CMS                                        |
// +--------------------------------------------------------------------------+
// | autoinstall.php                                                          |
// |                                                                          |
// | glFusion Auto Installer module                                           |
// +--------------------------------------------------------------------------+
// | $Id::                                                                   $|
// +--------------------------------------------------------------------------+
// | Copyright (C)  2009 by the following authors:                            |
// |                                                                          |
// | Mark R. Evans          mark AT glfusion DOT org                          |
// +--------------------------------------------------------------------------+
// |                                                                          |
// | This program is free software; you can redistribute it and/or            |
// | modify it under the terms of the GNU General Public License              |
// | as published by the Free Software Foundation; either version 2           |
// | of the License, or (at your option) any later version.                   |
// |                                                                          |
// | This program is distributed in the hope that it will be useful,          |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with this program; if not, write to the Free Software Foundation,  |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.          |
// |                                                                          |
// +--------------------------------------------------------------------------+

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

global $_DB_dbms;

require_once $_CONF['path'].'plugins/sitetailor/functions.inc';
require_once $_CONF['path'].'plugins/sitetailor/sitetailor.php';
require_once $_CONF['path'].'plugins/sitetailor/sql/'.$_DB_dbms.'_install.php';

// +--------------------------------------------------------------------------+
// | Plugin installation options                                              |
// +--------------------------------------------------------------------------+

$INSTALL_plugin['sitetailor'] = array(
  'installer' => array('type' => 'installer', 'version' => '1', 'mode' => 'install'),

  'plugin' => array('type' => 'plugin', 'name' => $_ST_CONF['pi_name'],
        'ver' => $_ST_CONF['pi_version'], 'gl_ver' => $_ST_CONF['gl_version'],
        'url' => $_ST_CONF['pi_url'], 'display' => $_ST_CONF['pi_display_name']),

  array('type' => 'table', 'table' => $_TABLES['st_config'], 'sql' => $_SQL['st_config']),

  array('type' => 'table', 'table' => $_TABLES['st_menus'], 'sql' => $_SQL['st_menus']),

  array('type' => 'table', 'table' => $_TABLES['st_menus_config'], 'sql' => $_SQL['st_menus_config']),
  array('type' => 'table', 'table' => $_TABLES['st_menu_elements'], 'sql' => $_SQL['st_menu_elements']),

  array('type' => 'group', 'group' => 'sitetailor Admin', 'desc' => 'Users in this group can administer the Site Tailor plugin',
        'variable' => 'admin_group_id', 'addroot' => true),

  array('type' => 'feature', 'feature' => 'sitetailor.admin', 'desc' => 'Ability to manage Site Tailor',
        'variable' => 'admin_feature_id'),

  array('type' => 'mapping', 'group' => 'admin_group_id', 'feature' => 'admin_feature_id',
        'log' => 'Adding feature to the admin group'),

  array('type' => 'sql', 'sql' => $_SQL['default_config'] ),
  array('type' => 'sql', 'sql' => $_SQL['default_menus'] ),
  array('type' => 'sql', 'sql' => $_SQL['default_menu_config'] ),
  array('type' => 'sql', 'sql' => $_SQL['default_menu_elements'] ),

  array('type' => 'block', 'name' => 'blockmenu', 'title' => 'Navigation',
          'phpblockfn' => 'phpblock_getMenu(block)', 'block_type' => 'phpblock',
          'group_id' => 'admin_group_id','onleft' => 1, 'blockorder' => 0),
);


/**
* Puts the datastructures for this plugin into the glFusion database
*
* Note: Corresponding uninstall routine is in functions.inc
*
* @return   boolean True if successful False otherwise
*
*/
function plugin_install_sitetailor()
{
    global $INSTALL_plugin, $_ST_CONF;

    $pi_name            = $_ST_CONF['pi_name'];
    $pi_display_name    = $_ST_CONF['pi_display_name'];
    $pi_version         = $_ST_CONF['pi_version'];

    COM_errorLog("Attempting to install the $pi_display_name plugin", 1);

    $ret = INSTALLER_install($INSTALL_plugin[$pi_name]);
    if ($ret > 0) {
        return false;
    }

    return true;
}

/**
* When the install went through, give the plugin a chance for any
* plugin-specific post-install fixes
*
* @return   boolean     true = proceed with install, false = an error occured
*
*/
function plugin_postinstall_sitetailorXXX()
{
    global $_CONF, $_TABLES, $_SQL_DEF;

    if ( is_array($_SQL_DEF) ) {
        foreach ($_SQL_DEF AS $sql) {
            DB_query($sql,1);
            COM_errorLog("Ran query: " . $sql );
        }
    } else {
        COM_errorLog("***** No post install defaults found ***** ");
    }
    return true;
}


/**
* Automatic uninstall function for plugins
*
* @return   array
*
* This code is automatically uninstalling the plugin.
* It passes an array to the core code function that removes
* tables, groups, features and php blocks from the tables.
* Additionally, this code can perform special actions that cannot be
* foreseen by the core code (interactions with other plugins for example)
*
*/
function plugin_autouninstall_sitetailor ()
{
    $out = array (
        /* give the name of the tables, without $_TABLES[] */
        'tables' => array('st_config','st_menus','st_menus_config','st_menu_elements'),
        /* give the full name of the group, as in the db */
        'groups' => array('sitetailor Admin'),
        /* give the full name of the feature, as in the db */
        'features' => array('sitetailor.admin'),
        /* give the full name of the block, including 'phpblock_', etc */
        'php_blocks' => array('phpblock_getMenu(block)'),
        /* give all vars with their name */
        'vars'=> array()
    );
    return $out;
}

/**
* Removes the data structures for this plugin from the glFusion database.
* This routine will get called from the Plugin install program if user select De-Install or if Delete is used in the Plugin Editor.
* The Plugin Installer will also call this routine upon and install error to remove anything it has created.
* The Plugin installer will pass the optional parameter which will then double check that plugin has first been disabled.
*
* For this plugin, this routine will also remove the Block definition.
*
* Returns True if all Plugin related data is removed without error
*
* @param    string   $installCheck     Default is blank but if set, check if plugin is disabled first
*
* @return   boolean True if successful false otherwise
*
*/
function plugin_uninstall_sitetailorZZZ() {
    global $_CONF, $_DB_table_prefix, $_TABLES, $LANG_ST00, $NEWFEATURE;
    global $pi_name, $pi_version, $gl_version, $pi_url, $base_path;

    $TABLES     = array ( 'st_config','st_menus','st_menu_config','st_menus_config','st_menu_elements');

    // Unregister the plugin with glFusion
    COM_errorLog('Attempting to unregister the ' . $pi_name . ' Plugin from glFusion',1);
    DB_query("DELETE FROM {$_TABLES['plugins']} WHERE pi_name = '" . $pi_name . "'",1);

    // Drop Menu Editor tables
    foreach($TABLES as $table) {
        COM_errorLog("Removing Table $table",1);
        DB_query("DROP TABLE " . $_DB_table_prefix . $table,1);
    }

    // Remove Security for this plugin
    $grp_id  = DB_getItem($_TABLES['vars'], 'value', "name = '{$pi_name}_gid'");
    $cgrp_id = DB_getItem($_TABLES['vars'],'value', "name = '{$pi_name}_cid'");

    COM_errorLog("Removing $pi_name Admin Group", 1);
    DB_query("DELETE FROM {$_TABLES['groups']} WHERE grp_id = $grp_id",1);
    DB_query("DELETE FROM {$_TABLES['vars']} WHERE name = '{$pi_name}_gid'");
    DB_query("DELETE FROM {$_TABLES['groups']} WHERE grp_id = $cgrp_id",1);
    DB_query("DELETE FROM {$_TABLES['vars']} WHERE name = '{$pi_name}_cid'");

    COM_errorLog("Removing root users from admin of $pi_name");
    DB_query("DELETE FROM {$_TABLES['group_assignments']} WHERE ug_main_grp_id = $grp_id",1);
    DB_query("DELETE FROM {$_TABLES['group_assignments']} WHERE ug_main_grp_id = $cgrp_id",1);

    COM_errorLog("Removing comments for " . $pi_name);
    DB_query("DELETE FROM {$_TABLES['comments']} WHERE type='" . $pi_name . "'",1);

    COM_errorLog('...success',1);
    return true;
}
?>