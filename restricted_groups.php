<?php

require_once dirname(__FILE__) . "/restricted_groups.conf.php";

/**
 * Create a restricted group with only read priv
 * It's a internal function, but you can use it for other purposes
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

/**
 * Add some common pages to Whitelist
 * It's a internal function, but you can use it for other purposes
 *
 * @param $mainpage True if the 'Main Page' should be allowed
 */
function vlk_restrictgroup_defaultwhitelist($mainpage = false) {
    // See: http://www.mediawiki.org/wiki/Manual:$wgWhitelistRead
    global $wgWhitelistRead;
    global $wgContLang;

    // Adding "Special:UserLogin", "Special:UserLogout", "Special:PasswordReset"
    // See: http://www.mediawiki.org/wiki/Manual:$wgWhitelistRead#Details
    // Using $wgContLang to get correct page names
    // See: http://www.mediawiki.org/wiki/Manual:$wgContLang
    if (empty($wgWhitelistRead)) {
        $wgWhitelistRead = array();
    }
    $wgWhitelistRead = array_merge($wgWhitelistRead, array($wgContLang->specialPage('Userlogin'),
            $wgContLang->specialPage('Userlogout'), $wgContLang->specialPage('PasswordReset')) );
    // Use $wfMessage to get correct mainpage name
    // See: http://www.mediawiki.org/wiki/Manual:Messages_API
    if ($mainpage) {
        $wgWhitelistRead[] = wfMessage('mainpage')->plain();
    }
}

/**
 * Get an array with all category names of a page
 *
 * @param $title A page (includes/Title.php object)
 * @return Array with all category names
 */
function vlk_restrictgroup_categories($title) {
    $categories = array();

    // Calc categories
    // $title->getParentCategories() return a array like
    // array(2) { ["Category:Category1"]=> string(5) "Title" ["Categoria:Category2"]=> string(5) "Title" }
    // See: https://doc.wikimedia.org/mediawiki-core/master/php/html/classTitle.html#ad44b1db65e5e1ccafa71512f0014d43d
    foreach (array_keys($title->getParentCategories()) as $uggly_key) {
        $_tmp = split(':', $uggly_key);
        $categories[] = $_tmp[1];
    }

    return $categories;
}

// Whitelist all category pages of a user
// $restricted_groups is an array with all restricted groups that the user belongs
function vlk_restrictgroup_whitelistcategories($restricted_groups) {
    global $wgVlkRestrictGroupRules;
    // See: http://www.mediawiki.org/wiki/Manual:$wgWhitelistRead
    global $wgWhitelistRead;
    global $wgContLang;

    // Creating $category_pages array
    foreach ($restricted_groups as $group) {
        // Adding as a key to add only unique values
        if (array_key_exists($group, $wgVlkRestrictGroupRules)) {
            foreach ($wgVlkRestrictGroupRules[$group] as $category) {
                $category_pages[$category] = $wgContLang->getNsText( NS_CATEGORY ) . ":" . $category;
            }
        }
    }
    // Whitelisting all category pages
    // See: http://www.php.net/manual/pt_BR/function.array-values.php
    $wgWhitelistRead = array_merge($wgWhitelistRead, array_values($category_pages));
}

// Hook that determines if a user can or cannot read a page
// See: http://www.mediawiki.org/wiki/Manual:Hooks/userCan
//      http://www.mediawiki.org/wiki/Manual:Title.php
//      http://www.mediawiki.org/wiki/Manual:User.php
function vlk_restricgroup_hook($title, $user, $action, $result) {
    // Global vars
    global $wgVlkRestrictGroup, $wgVlkRestrictGroupRules;
    global $wgWhitelistRead;

    // Get page categories
    // If you don't know what is a category, see:
    //     http://meta.wikimedia.org/wiki/Help:Category
    $categories = vlk_restrictgroup_categories($title);

    // Get user restricted groups
    // See: https://doc.wikimedia.org/mediawiki-core/master/php/html/classUser.html#a83878b7b9f03a5a835ffa8f3a185f53f
    $restricted_groups = array_intersect($user->getGroups(), $wgVlkRestrictGroup);

    // To understand the returned values and the $result var, see:
    //      http://www.mediawiki.org/wiki/Manual:Hooks/userCan#Table_of_combinations

    // If user is not in a restricted group, maintain the default behavior
    if (empty($restricted_groups)) {
        return true;
    }

    // Permit Whitelist pages
    vlk_restrictgroup_defaultwhitelist();
    vlk_restrictgroup_whitelistcategories($user);
    if (!empty($wgWhitelistRead)) {
        if(in_array($title, $wgWhitelistRead)) {
            $result = true;
            return true;
        }
    }

    // If there is no categories, deny
    if (empty($categories)) {
        $result = false;
        return false;
    }

    // Compute if user can read pages in category
    foreach ($restricted_groups as $group) {
        // If there is some rule, check if category is in group rules
        if (array_key_exists($group, $wgVlkRestrictGroupRules)) {
            foreach ($categories as $category) {
                // Case insensitive comparing
                // See: http://stackoverflow.com/questions/2166512/php-case-insensitive-in-array-function/2166522#2166522
                if (in_array(strtolower($category), array_map('strtolower', $wgVlkRestrictGroupRules[$group]))) {
                    $result = true;
                    // Returning 'true' because the privileges must be overwritten by the system
                    // For example, system will overwrote the 'edit' priv to 'false'
                    return true;
                }
            }
        }
    }
    // If there is no rule, deny
    $result = false;
    return false;
}

// Create plugin environment
function vlk_restrictgroup_run() {
    // See: http://www.mediawiki.org/wiki/Manual:$wgHooks
    global $wgHooks;
    // Groups in config file
    global $wgVlkRestrictGroup;
    global $wgVlkRestrictGroupRules;

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

