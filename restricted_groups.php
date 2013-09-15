<?php

require_once dirname(__FILE__) . "/restricted_groups.conf.php";

/**
 * Creating a restricted group with only read priv
 * It's a internal function, but with can use it for other purposes
 *
 * @param $name name of the group to create
 */
function vlk_restrictgroup_creating($name) {
    // See: http://www.mediawiki.org/wiki/Manual:$wgRevokePermissions
    global $wgRevokePermissions;

    // See: http://www.mediawiki.org/wiki/Manual:User_rights#List_of_permissions
    $all_privs_except_read = array( 'edit', 'createpage', 'createtalk', 'move', 'movefile', 'move', 
            'move', 'createaccount', 'upload', 'reupload', 'reupload', 'reupload', 
            'upload_by_url', 'editprotected', 'delete', 'bigdelete', 'deletedhistory', 
            'deletedtext', 'undelete', 'browsearchive', 'mergehistory', 'protect', 'block', 
            'blockemail', 'hideuser', 'unblockself', 'userrights', 'userrights', 'rollback', 
            'markbotedits', 'patrol', 'editinterface', 'editusercssjs', 'editusercss', 'edituserjs', 
            'suppressrevision', 'deleterevision', 'siteadmin', 'import', 'importupload', 'trackback', 
            'unwatchedpages', 'bot', 'purge', 'minoredit', 'nominornewtalk', 'noratelimit', 
            'ipblock', 'proxyunbannable', 'autopatrol', 'apihighlimits', 'writeapi', 
            'suppressredirect', 'autoconfirmed', 'emailconfirmed' );

    // Setting all permissions to false, except 'read'
    foreach ($all_privs_except_read as $priv) {
        $wgRevokePermissions[$name][$priv] = true;
    }
    $wgGroupPermissions['read'] = true;
}

// Hook that determines if a user can or cannot read a page
// See: http://www.mediawiki.org/wiki/Manual:Hooks/userCan
//      http://www.mediawiki.org/wiki/Manual:Title.php
//      http://www.mediawiki.org/wiki/Manual:User.php
function vlk_restricgroup_hook($title, $user, $action, $result) {
    // Get user groups
    // See: https://doc.wikimedia.org/mediawiki-core/master/php/html/classUser.html#a1cc72fb824cc7541f696f951d6a382ce
    $groups = $user->getAllGroups();
    // Get page categories
    // See: https://doc.wikimedia.org/mediawiki-core/master/php/html/classTitle.html#ad44b1db65e5e1ccafa71512f0014d43d
    $categories = $title->getParentCategories();
    echo var_dump($groups);
    echo "<br>";
    echo var_dump($categories);
    exit();
    // See: http://www.mediawiki.org/wiki/Manual:Hooks/userCan#Table_of_combinations
    
}

// Create plugin environment
function vlk_restrictgroup_run() {
    // See: http://www.mediawiki.org/wiki/Manual:$wgHooks
    global $wgHooks;
    // Groups in config file
    global $wgVlkRestrictGroup;

    // Creating groups with correct permissions
    foreach ($wgVlkRestrictGroup as $name) {
        vlk_restrictgroup_creating($name);
    }
    // Adding Hook
    // See: http://www.mediawiki.org/wiki/Manual:Hooks/userCan
    $wgHooks['userCan'][] = 'vlk_restricgroup_hook';
}

// Run the plugin
vlk_restrictgroup_run();

?>
