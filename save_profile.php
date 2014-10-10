<?php

include_once 'phplib/base.php';

if (!db::connect()) {
    echo "Database connection failed, cannot continue. ";
} else {
    $username = getUsername();
    $full_name = db::escape($_POST['full_name']);
    $tz = db::escape($_POST['timezone']);
    $sleep_provider = db::escape($_POST['sleeptracking_provider']);
    $sleep_settings = db::escape(json_encode($_POST['sleeptracking']));

    $query = "REPLACE INTO user_profile (ldap_username, full_name, timezone, sleeptracking_provider, sleeptracking_settings) 
                                    VALUES ('$username', '$full_name', '$tz', '$sleep_provider', '$sleep_settings')";
    if (!db::query($query)) {
        echo "Database update failed, error: " . db::error();
    } else {
        Header("Location: {$ROOT_URL}/edit_profile.php?succ=hellyeah");
    }
}

