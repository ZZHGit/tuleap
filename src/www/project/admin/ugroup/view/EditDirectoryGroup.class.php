<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once 'Binding.class.php';
require_once('pre.php');
require_once('www/project/admin/ugroup_utils.php');
require_once('www/project/admin/project_admin_utils.php');
require_once('common/plugin/PluginManager.class.php');
require_once('../../../../plugins/ldap/include/LDAP_UserGroupManager.class.php');

class Project_Admin_UGroup_View_EditDirectoryGroup extends Project_Admin_UGroup_View_Binding {
    public function getContent(){

    $content = '';
    // Import very long user group may takes very long time.
    ini_set('max_execution_time', 0);

    //
    // Verify common requirement
    //

    // LDAP plugin enabled
    $pluginManager = PluginManager::instance();
    $ldapPlugin = $pluginManager->getPluginByName('ldap');
    if ($ldapPlugin && $pluginManager->isPluginAvailable($ldapPlugin)) {
        $pluginPath = $ldapPlugin->getPluginPath();
    } else {
        exit_error($GLOBALS['Language']->getText('global','error'), 'No ldap plugin');
    }

    // User group id exists
    $vUgroupId = new Valid_UInt('ugroup_id');
    $vUgroupId->required();
    $request = HTTPRequest::instance();
    if($request->valid($vUgroupId)) {
        $ugroupId = $request->get('ugroup_id');
    } else {
        exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('project_admin_editugroup','ug_not_found'));
    }

    // Do not try to modify ugroups of project 100
    $res = ugroup_db_get_ugroup($ugroupId);
    if($res && !db_error($res) && db_numrows($res) == 1) {
        $row = db_fetch_array($res);
        session_require(array('group'=>$row['group_id'],'admin_flags'=>'A'));
        if($row['group_id'] == 100) {
             exit_error($GLOBALS['Language']->getText('global','error'), "Cannot modify this ugroup with LDAP plugin");
        }
    } else {
        exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('project_admin_editugroup','ug_not_found',array($ugroupId,db_error())));
    }
    $group_id = $row['group_id'];

    $vFunc = new Valid_String('func', array('bind_with_group'));
    $vFunc->required();
    if(!$request->valid($vFunc)) {
        $GLOBALS['Response']->redirect('/project/admin/ugroup.php?group_id='.$group_id);
    }

    $ldapUserGroupManager = new LDAP_UserGroupManager($ldapPlugin->getLdap());
    $ldapUserGroupManager->setGroupName($request->get('bind_with_group'));
    $ldapUserGroupManager->setId($ugroupId);

    // Check if user have choosen the preserve members option.
    $bindOption = LDAP_GroupManager::BIND_OPTION;
    if($request->exist('preserve_members') && $request->get('preserve_members') == 'on') {
        $bindOption = LDAP_GroupManager::PRESERVE_MEMBERS_OPTION;
    }

    // Check if user has checked the Synchronization option.
    $synchro = LDAP_GroupManager::NO_SYNCHRONIZATION;
    if ($request->existAndNonEmpty('synchronize')) {
        $synchro = LDAP_GroupManager::AUTO_SYNCHRONIZATION;
    }


    $hp = Codendi_HTMLPurifier::instance();

    $btn_update = $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_btn_update');
    $btn_unlink = $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_btn_unlink');
    $vSubmit = new Valid_WhiteList('submit', array($btn_update, $btn_unlink));
    $vSubmit->required();
    if($request->isPost() && $request->valid($vSubmit)) {
        if($request->get('submit') == $btn_unlink) {
            if($ldapUserGroupManager->unbindFromBindLdap()) {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_manager_unlink'));
            }
        } else {
            $vBindWithGroup = new Valid_String('bind_with_group');
            $vBindWithGroup->required();
            if($request->valid($vBindWithGroup)) {
                if($request->existAndNonEmpty('confirm')) {
                    //
                    // Perform Ugroup <-> LDAP Group synchro
                    //
                    $ldapUserGroupManager->bindWithLdap($bindOption, $synchro);

                } elseif($request->exist('cancel')) {
                    // Display the screen below!
                    continue;

                } else {
                    //
                    // Display to user what will be done with Ugroup members.
                    //

                    $toRemove    = $ldapUserGroupManager->getUsersToBeRemoved($bindOption);
                    $toAdd       = $ldapUserGroupManager->getUsersToBeAdded($bindOption);
                    $notImpacted = $ldapUserGroupManager->getUsersNotImpacted($bindOption);

                    if(is_array($toAdd)) {
                        // Display
                        $um = UserManager::instance();

                        $content .= '<h1>'.$GLOBALS['Language']->getText('plugin_ldap','ugroup_members_synchro_title').'</h1>';
                        $content .= '<p>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_members_synchro_warning').'</p>';
                        $content .= '<p>'.$GLOBALS['Language']->getText('plugin_ldap', 'group_members_synchro_sumup', array(count($toRemove), count($toAdd), count($notImpacted))).'</p>';

                        $content .= '<table width="100%">';
                        $content .= '<tr><td width="50%" valign="top">';

                        $GLOBALS['HTML']->box1_top($GLOBALS['Language']->getText('plugin_ldap', 'group_members_synchro_to_remove', array(count($toRemove))));
                        $content .= '<ul>';
                        foreach ($toRemove as $userId) {
                            if (($user = $um->getUserById($userId))) {
                                $content .= '<li>'.$user->getRealName().' ('.$user->getUserName().')</li>';
                            }
                        }
                        $content .= '</ul>';
                        $GLOBALS['HTML']->box1_bottom();

                        $content .= '</td><td width="50%"  valign="top">';

                        $GLOBALS['HTML']->box1_top($GLOBALS['Language']->getText('plugin_ldap', 'group_members_synchro_to_add', array(count($toAdd))));
                        $content .= '<ul>';
                        foreach ($toAdd as $userId) {
                            if (($user = $um->getUserById($userId))) {
                                $content .= '<li>'.$user->getRealName().' ('.$user->getUserName().')</li>';
                            }
                        }
                        $content .= '</ul>';
                        $GLOBALS['HTML']->box1_bottom();

                        $content .= '</tr></td>';
                        $content .= '<tr><td colspan="2" align="center">';
                        $content .= '<form method="post" action="?ugroup_id='.$ugroupId.'&func=bind_with_group">';
                        $content .= '<input type="hidden" name="bind_with_group" value="'.$hp->purify($request->get('bind_with_group')).'" />';
                        $content .= '<input type="hidden" name="confirm" value="yes" />';
                        if($bindOption == 'preserve_members') {
                            $content .= '<input type="hidden" name="preserve_members" value="on" />';
                        }
                        if($synchro == LDAP_GroupManager::AUTO_SYNCHRONIZATION) {
                            $content .= '<input type="hidden" name="synchronize" value="on" />';
                        }
                        $content .= '<input type="submit" name="cancel" value="'.$GLOBALS['Language']->getText('global', 'btn_cancel').'" />';
                        $content .= '<input type="submit" name="submit" value="'.$btn_update.'" />';
                        $content .= '</form>';
                        $content .= '</td></tr>';
                        $content .= '</table>';

                        return $content;
                    } else {
                      /*  $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_ldap', 'invalid_ldap_group_name'));
                        $GLOBALS['Response']->redirect('/project/admin/index.php?group_id='.$group_id);*/
                    }
                }
            }
        }
    }

    //
    // Display
    //

    $ugroupRow  = ugroup_db_get_ugroup($ugroupId) ;
    $ugroupName = util_translate_name_ugroup($row['name']);
    $ldapGroup = $ldapUserGroupManager->getLdapGroupByGroupId($ugroupId);

    $clean_ugroupName = $hp->purify($ugroupName);
    if($ldapGroup !== null) {
        $clean_ldapGroupName = $hp->purify($ldapGroup->getCommonName());
    } else {
        $clean_ldapGroupName = '';
    }

    $content .= '
    <script type="text/javascript" src="/scripts/prototype/prototype.js"></script>
    <script type="text/javascript" src="/scripts/scriptaculous/scriptaculous.js"></script>
    <script type="text/javascript" src="/scripts/autocomplete.js"></script>
    <script type="text/javascript" src="'.$pluginPath.'/scripts/autocomplete.js"></script>
    ';

    $content .= '<h2>'.$GLOBALS['Language']->getText('project_admin_editugroup','ug_admin', $clean_ugroupName).'</h2>';

    if($ldapGroup !== null) {
            $content .= '<p>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_ugroup_linked', array($clean_ugroupName, $clean_ldapGroupName)).'</p>';
    }

    $content .= '<p>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_you_can').'</p>';
    $content .= '<ul>';

    if($ldapGroup !== null) {
    $content .= '<li>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_resync').'</li>';
    $content .= '<li>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_remove').'</li>';
    }
    $content .= '<li>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_link').'</li>';
    $content .= '</ul>';
    $content .= '<p>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_synchro').'</p>';
    $content .= '<ul>';
    $content .= '<li>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_synchro_del', $GLOBALS['sys_name']).'</li>';
    $content .= '<li>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_synchro_add', $GLOBALS['sys_name']).'</li>';
    $content .= '</ul>';

    $content .= '<form name="plugin_ldap_edit_ugroup" method="post" action="">';
    $content .= '<input type="hidden" name="ugroup_id" value="'.$ugroupId.'" />';
    $func = 'bind_with_group';
    $content .= '<input type="hidden" name="action" value="edit_directory" />';


    $content .= '<p>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_bind_with_group').' <input type="text" name="bind_with_group" id="group_add" value="'.$clean_ldapGroupName.'"  size="60" /></p>';

    $preservingChecked = '';
    if ($ldapUserGroupManager->isMembersPreserving($ugroupId)) {
        $preservingChecked = 'checked';
    }
    $content .= '<p><input type="checkbox" id="preserve_members" name="preserve_members" '.$preservingChecked.'/>';

    $content .= '<label for="preserve_members">'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_preserve_members_option').' ('.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_preserve_members_info').')</label></p>';

    $synchroChecked = '';
    if ($ldapUserGroupManager->isSynchronizedUgroup($ugroupId)) {
        $synchroChecked = 'checked';
    }
    $content .= '<p><input type="checkbox" id="synchronize" name="synchronize" '.$synchroChecked.'/>';
    $content .= '<label for="synchronize">'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_synchronize_option').' ('.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_synchronize_info').')</label></p>';

    $content .= '<input type="submit" name="submit" value="'.$btn_update.'" />';
    if($ldapGroup !== null) {
        $content .= '&nbsp;&nbsp;';
        $content .= '<input type="submit" name="submit" value="'.$btn_unlink.'" />';
    }
    $GLOBALS['Response']->includeFooterJavascriptFile($pluginPath.'/scripts/autocomplete.js');
    $js = "new LdapGroupAutoCompleter('group_add',
        '".$pluginPath."',
        '".util_get_dir_image_theme()."',
        'group_add',
        false);";
    $GLOBALS['Response']->includeFooterJavascriptSnippet($js);

    $content .= '</form>';

    return $content;
    
    }
}

?>
