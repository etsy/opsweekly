<?php

include_once 'phplib/base.php';

if (!db::connect()) {
    echo "Database connection failed, cannot continue. ";
} else {
    $username = getUsername();
    $full_name = db::escape($_POST['full_name']);
    $jira_username = db::escape($_POST['jira_username']);
    $github_username = db::escape($_POST['github_username']);
    $bitbucket_username = db::escape($_POST['bitbucket_username']);
    $tz = db::escape($_POST['timezone']);
    $sleep_provider = db::escape($_POST['sleeptracking_provider']);
    $sleep_settings = db::escape(json_encode($_POST['sleeptracking']));

    $query = "REPLACE INTO user_profile (ldap_username, full_name, jira_username, github_username, bitbucket_username, timezone, sleeptracking_provider, sleeptracking_settings) 
                                    VALUES ('$username', '$full_name', '$jira_username', '$github_username', '$bitbucket_username', '$tz', '$sleep_provider', '$sleep_settings')";
    if (!db::query($query)) {
        echo "Database update failed, error: " . db::error();
    } else {
        Header("Location: {$ROOT_URL}/edit_profile.php?succ=hellyeah");
    }
}

