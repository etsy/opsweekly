<?php

include_once 'phplib/base.php';

if (!connectToDB()) {
    echo "Database connection failed, cannot continue. ";
} else {
    $username = getUsername();
    $full_name = mysql_real_escape_string($_POST['full_name']);
    $tz = mysql_real_escape_string($_POST['timezone']);
    $sleep_provider = mysql_real_escape_string($_POST['sleeptracking_provider']);
    $sleep_settings = mysql_real_escape_string(json_encode($_POST['sleeptracking']));

    $query = "REPLACE INTO user_profile (ldap_username, full_name, timezone, sleeptracking_provider, sleeptracking_settings) 
                                    VALUES ('$username', '$full_name', '$tz', '$sleep_provider', '$sleep_settings')";
    if (!mysql_query($query)) {
        echo "Database update failed, error: " . mysql_error();
    } else {
        Header('Location: {$ROOT_URL}/edit_profile.php?succ=hellyeah');
    }
}

