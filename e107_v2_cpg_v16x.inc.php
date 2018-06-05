<?php
/********************************************************************
    Coppermine Photo Gallery
    e107 bridge
    ************************
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 3
    as published by the Free Software Foundation.
    ************************
    
    Autor: v3 <v3@sonic-world.ru>
    Date: 2006-09-28 04:48:42 +0400
    Origin brige: http://forum.coppermine-gallery.net/index.php?topic=36151.0
    
    ************************
    
    Adapted for e107 (v 1.x and 2.x) and cpg (v 1.5.x only) by Chip Viled
    
    ************************
    
    Adapted for e107 (v 2.x) and cpg (v 1.6.x only) by Chip Viled
    
    ************************
    
    ---  ATTENTION !!!  ---
    
    In file cpg1.6.x/include/init.inc.php value $strict MUST BE FALSE !!!
    
    
    If the encoding from the database is incorrectly then 
    need inject correct charset type in file dbase.inc.php.
    Example for PDO:
    $dsn = "{$db_sub}:host=" . $cfg['dbserver'] . ';dbname='.$cfg['dbname'].';charset=UTF8';
                                                                           ^--------------^

********************************************************************/

if (!defined('IN_COPPERMINE')) die('Not in Coppermine...');

if (isset($bridge_lookup)) {
    $default_bridge_data[$bridge_lookup] = array(
        'full_name' => 'e107',
        'short_name' => 'e107',
        'support_url' => 'e107',
        'full_forum_url_default' => 'http://localhost/marvin_test/',
        'full_forum_url_used' => 'mandatory,not_empty',
        'relative_path_to_config_file_default' => '../',
        'relative_path_to_config_file_used' => 'lookfor,e107_config.php',
        'use_post_based_groups_default' => '0',
        'use_post_based_groups_used' => 'radio,1,0',
    );
} else {

    define('USE_BRIDGEMGR', 1);
    $E107_COPPERMINE_ADMIN_GROUP = "COPPERMINE_ADMIN";

    require_once 'bridge/udb_base.inc.php';

    class cpg_udb extends core_udb {
        function cpg_udb() {
            global $BRIDGE;
            
            if (!USE_BRIDGEMGR) {
                
                $this->boardurl = 'http://localhost/';
                require_once('../e107_config.php');

            } else {
                $this->boardurl = $BRIDGE['full_forum_url'];
                require_once($BRIDGE['relative_path_to_config_file'] . 'e107_config.php');
                $this->use_post_based_groups = $BRIDGE['use_post_based_groups'];
            }


        // class2.php MUST be included from CPG's config.inc.php (otherwise, globals will not work)
        // it can't be realised with plugins for now. So, it's the only way.
        if (!defined('CPG_E107_CLASS_INCLUDED')) {

            for ($tmp = 0; $tmp < 4; $tmp++) {
                if (!is_readable (@$BRIDGE['relative_path_to_config_file'] . 'class2.php')) {
                    @$BRIDGE['relative_path_to_config_file'] = "../".@$BRIDGE['relative_path_to_config_file'];
                }
            }

            if (@$_POST['e107path'] && $_POST['e107path'][ strlen($_POST['e107path']) - 1 ] != '/') {
                $_POST['e107path'] .= "/";
            }

            if (@$_POST['e107path'] && !is_readable (@$BRIDGE['relative_path_to_config_file'] . 'class2.php'))
                $BRIDGE['relative_path_to_config_file'] = @$_POST['e107path'];

            if (is_readable ($BRIDGE['relative_path_to_config_file'] . 'class2.php') && !@$_POST['e107path']) {
                $text = 
'<h1>Please, enter relative path to e107</h1>
Please, check out if this is relative path to e107 (from Coppermine folder).<br />
Installing Coppermine not into e107 subfolder is <b>not recommend</b>, so don\'t complain if it will not work :)
<form action="" method="post">
<input type="text" name="e107path" value="'.$BRIDGE['relative_path_to_config_file'].'" />
<input type="submit" name="submit" value="Here\'s it" />
</form>';
                //echo $text;
                //die;
            }

            if (!is_readable ($BRIDGE['relative_path_to_config_file'] . 'class2.php')) {
                $tmppath = @$_POST['e107path'] ? $_POST['e107path'] : "../e107/";
                $text = 
'<h1>Can\'t find e107_config.php :/</h1>
Please, enter <b>relative</b> path to e107 (from Coppermine folder).<br />
Installing Coppermine not into e107 subfolder is <b>not recommend</b>, so don\'t complain if it will not work :)
<form action="" method="post">
<input type="text" name="e107path" value="'.addslashes($tmppath).'" />
<input type="submit" name="submit" value="Here\'s it" />
</form>';
                echo $text;
                die();
            }    


            $class2 = $BRIDGE['relative_path_to_config_file'] . 'class2.php';
            $data ='

// e107
define("CPG_E107_CLASS_INCLUDED", 1);
$register_globals = true;
@include_once "'.$class2.'";

';
            
            $config = "include/config.inc.php";
            
            if (!is_writable($config)) {
                die ("Can't write into '$config' file. Please, turn on write permissions on it.");
            }

            $f = fopen($config, "a");
            fwrite($f, $data);
            fclose($f);

            $text = '
<h1>e107 Coppermine Bridge was installed!</h1>
Note that "include/config.inc.php" was modified! It\'s required operation if you want this bridge to work propertly. :)<br />
Following block was added:<div style="margin: 5px; border: 1px solid #666; background-color: #eee">
<small><pre>'.htmlspecialchars($data).'</pre></small></div>
If you want to disable this bridge, you should remove added block by yourself!<br /><br />
';
        if(@ini_get("register_globals")) $text = '
<h1>Warning: you have register_globals turned on!</h1>
There can be problems on configurations with register_globals turned on. So, if everything\'s messed up, please consider of
<a target="blank" href="http://www.google.ru/search?q=turn+off+register_globals">turning it off</a> (actually, it\'s a good idea anyway).'
.$text;
        echo $text;
        global $CONFIG;
        $qr = "UPDATE `{$CONFIG['TABLE_BRIDGE']}` 
                SET `value` = '".mysql_escape_string($BRIDGE['relative_path_to_config_file'] )."'
                WHERE name = 'relative_path_to_config_file'";
        $this->query($qr);
        die;
        }
        
        if (!is_readable ($BRIDGE['relative_path_to_config_file'] . 'e107_config.php'))
            die ("E107 BRIDGE ERROR: can't find 'e107_config.php'");
        include($BRIDGE['relative_path_to_config_file'] . 'e107_config.php');
        
        global $e107;
        if (!is_object(@$e107)) {
            die ('E107 BRIDGE ERROR: class2.php is not included! Try to check out "coppermine/include/config.inc.php"');
        }
        
        if (!$BRIDGE['full_forum_url']) {
            global $CONFIG;
            $BRIDGE['full_forum_url'] = $e107->base_path;
            $qr = "UPDATE `{$CONFIG['TABLE_BRIDGE']}` 
                    SET `value` = '".mysql_escape_string($BRIDGE['full_forum_url'] )."'
                    WHERE name = 'full_forum_url'";
            $this->query($qr);
        }
        
        $this->boardurl = $BRIDGE['full_forum_url'];
        $this->use_post_based_groups = 1;

        $this->e107_version = "2.0";
        $this->multigroups = 1;
        $this->group_overrride = 1;

        // Database connection settings
        $this->db = array(
            'name' => $mySQLdefaultdb,
            'host' => $mySQLserver,
            'user' => $mySQLuser,
            'password' => $mySQLpassword,
            'prefix' => $mySQLprefix
        );
        
        // Board table names
        $this->table = array(
            'users' => 'user',
            'groups' => 'userclass_classes',
            'sessions' => 'session',
            'config' => 'core'
        );

        // Derived full table names
        $this->usertable = '`' . $this->db['name'] . '`.' . $this->db['prefix'] . $this->table['users'];
        $this->groupstable =  '`' . $this->db['name'] . '`.' . $this->db['prefix'] . $this->table['groups'];
        $this->sessionstable =  '`' . $this->db['name'] . '`.' . $this->db['prefix'] . $this->table['sessions'];
        $this->configtable = '`' . $this->db['name'] . '`.' . $this->db['prefix'] . $this->table['config'];
        $this->usergroupstable = $this->usertable;

        // Table field names
        $this->field = array(
            'username' => 'user_name', // name of 'username' field in users table
            'user_id' => 'user_id', // name of 'id' field in users table
            'is_admin' => 'user_admin', // whether user is admin
            'admin_perms' => 'user_perms', // admin permissions
            'password' => 'user_password', // name of 'password' field in users table
            'email' => 'user_email', // name of 'email' field in users table
            'regdate' => 'user_join', // name of 'registered' field in users table
            'lastvisit' => 'user_lastvisit', // last time user logged in
            'active' => 'user_ban', // is user account active?
            'location' => "''", // name of 'location' field in users table
            'website' => "''", // name of 'website' field in users table
            'usertbl_group_id' => 'user_class', // name of 'group id' field in users table
            'grouptbl_group_id' => 'userclass_id', // name of 'group id' field in groups table
            'grouptbl_group_name' => 'userclass_name', // name of 'group name' field in groups table

            'e107_configcol' => 'e107_name' // main column of e107_core
        );

        // Pages to redirect to
        $this->page = array(
            'register' => $BRIDGE['full_forum_url']."/signup.php",
            'editusers' => $BRIDGE['full_forum_url'].'/administrator/index.php',
            'edituserprofile' => $BRIDGE['full_forum_url']."/$ADMIN_DIRECTORY/user.php"
        );

        // Group ids - admin and guest only.

        // get COPPERMINE_ADMIN Group


        $this->connect();

        global $E107_COPPERMINE_ADMIN_GROUP;
        $query = "SELECT * FROM {$this->groupstable} WHERE `userclass_name` = '$E107_COPPERMINE_ADMIN_GROUP'";
        //$result = cpg_db_query($query, $this->link_id);
        $result = $this->query($query);
        //$row = mysql_fetch_assoc($result);
        $row =cpg_db_fetch_assoc($result);
        $result->free();

        
        $this->admingroups = array();
        if ($row['userclass_id']) {
            $this->admingroups = array($row['userclass_id']);
        }
        
        $this->guestgroup = -97;  // (100 - 97) = 3
        
    }

    function collect_groups() {
        $query ="SELECT * FROM {$this->groupstable}";

        $result = $this->query($query);

        $udb_groups = array(1 =>'Administrators', 2=> 'Registered', 3=>'Guests', 4=> 'Banned');

        while ($row = cpg_db_fetch_assoc($result)){
            // don't include COPPERMINE_ADMIN Group
            if (!in_array($row[$this->field['grouptbl_group_id']], $this->admingroups)) {
                $udb_groups[$row[$this->field['grouptbl_group_id']] + 100] = 
                    utf_ucfirst(utf_strtolower($row[$this->field['grouptbl_group_name']]));
            }
        }
        $result->free();
        
        $udb_groups = array_unique($udb_groups);

        return $udb_groups;
    }


    // definition of how to extract an id and password hash from a cookie
    function cookie_extraction() {
        // not used anymore
    }

    function get_groups($row) {
        global $USER_DATA;

        if (!$row['group_id']) return;
        $g = explode(",", $row['group_id']);

        foreach ($g as $i => $z) {
            $g[$i]+=100;
        }

        return $g;
    }

    /**
     *  Autentificate
     *  кто все эти люди? 
     */
    function authenticate()
    {
        global $USER_DATA, $currentUser;

        if (!(USER && USERID)) {
            $this->load_guest_data();
        } else {
            // class2.php included
            list ($id, $cookie_pass) = array (USERID, $currentUser['user_password']);
            $f = $this->field;

            $row = array (
                        'id' => $id,
                        'username' => $currentUser['user_name'],
                        'password' => $cookie_pass,
                        'is_admin' => $currentUser['user_admin'],
                        'admin_perms' => $currentUser['user_perms']
                        );
            
            if ($currentUser['user_class']) {
                $row['group_id'] = $currentUser['user_class'];
            }
            $this->load_user_data($row);
            $USER_DATA['groups'][count($USER_DATA['groups'])] = 2; // user is registered
        }

        $user_group_set = '(' . implode(',', $USER_DATA['groups']) . ')';

        if (in_array($this->admingroups[0]+100, $USER_DATA['groups']) ||
                ($row['is_admin'] && $row['admin_perms'] == 0)
            ) {
            $USER_DATA['has_admin_access'] = true;
            $USER_DATA['groups'][count($USER_DATA['groups'])] = 1;
        }
        $USER_DATA['can_see_all_albums'] = $USER_DATA['has_admin_access'];


        $USER_DATA = array_merge($USER_DATA, $this->get_user_data($USER_DATA['groups'][0], $USER_DATA['groups'], $this->guestgroup));

        // avoids a template error
        if (!$USER_DATA['user_id']) {
            $USER_DATA['can_create_albums'] = 0;
        }

        // For error checking
        $CONFIG['TABLE_USERS'] = '**ERROR**';

        define('USER_ID', $USER_DATA['user_id']);
        define('USER_NAME', addslashes($USER_DATA['user_name']));
        define('USER_GROUP', $USER_DATA['group_name']);
        define('USER_GROUP_SET', $user_group_set);
        define('USER_IS_ADMIN', $USER_DATA['has_admin_access']);
        define('USER_CAN_SEND_ECARDS', (int)$USER_DATA['can_send_ecards']);
        define('USER_CAN_RATE_PICTURES', (int)$USER_DATA['can_rate_pictures']);
        define('USER_CAN_POST_COMMENTS', (int)$USER_DATA['can_post_comments']);
        define('USER_CAN_UPLOAD_PICTURES', (int)$USER_DATA['can_upload_pictures']);
        define('USER_CAN_CREATE_ALBUMS', (int)$USER_DATA['can_create_albums']);

        define('USER_CAN_CREATE_PRIVATE_ALBUMS', (int)$USER_DATA['can_create_albums']);
        define('USER_CAN_CREATE_PUBLIC_ALBUMS', (int)$USER_DATA['can_create_public_albums']);
        define('USER_ACCESS_LEVEL', (int)$USER_DATA['access_level']);

        $this->session_update();
    }

    function udb_hash_db($password) {
        // Never use anymore.
    }

    function login_page() {
        $this->redirect($BRIDGE['full_forum_url']);
    }

    function logout_page() {
        global $CONFIG;
        $this->redirect($BRIDGE['full_forum_url']."?logout");
    }

    function edit_profile() {
        $this->redirect($BRIDGE['full_forum_url']."usersettings.php");
    }

    function view_users() {}
    function view_profile() {}
    function session_extraction($cookie_id) {}

}

    // and go !
    $cpg_udb = new cpg_udb;
}

// EOF