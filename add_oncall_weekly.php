<?php

include_once 'phplib/base.php';

if (!connectToDB()) {
    echo "Database connection failed, cannot continue. ";
} else {
    $username = getUsername();
    $range_start = mysql_real_escape_string($_POST['oncall']['range_start']);
    $range_end = mysql_real_escape_string($_POST['oncall']['range_end']);

    logline("Started adding a new oncall update for {$username} with range_start: {$range_start} and range_end: {$range_end}...");

    if (count($_POST['oncall']['notifications']) > 0) {
        // See if this user is enrolled in sleep tracking
        $profile_data = checkForUserProfile($username);
        if ($profile_data && $profile_data['sleeptracking_provider'] != "none" && $profile_data['sleeptracking_provider'] != "") {
            $sleep_provider = $profile_data['sleeptracking_provider'];
            logline("Sleeptracking enabled: {$sleep_provider}");
            $sleep = true;

            // Get the user settings into an array by decoding the JSON we put in the database for them
            $sleeptracking_settings = json_decode($profile_data['sleeptracking_settings'], 1);
            // Get the provider settings from the config array
            $sleepprovider_settings = $sleep_providers[$sleep_provider];
            // Load the sleeptracking provider's PHP code
            include_once($sleepprovider_settings['lib']);

        } else {
            logline("No need to do sleep tracking because {$username} has no provider chosen");
        }

        foreach($_POST['oncall']['notifications'] as $id => $n) {
            $sleep_state = -1;
            $mtts = -1;
            $sleep_level = -1;
            $confidence = -1;
            $timestamp = mysql_real_escape_string($n['time']);
            $hostname = mysql_real_escape_string($n['hostname']);
            $output = mysql_real_escape_string($n['output']);
            $service = mysql_real_escape_string($n['service']);
            $state = mysql_real_escape_string($n['state']);
            $tag = mysql_real_escape_string($n['tag']);
            $notes = mysql_real_escape_string(htmlentities($n['notes'], ENT_QUOTES));
            $alert_id = generateOnCallAlertID($timestamp, $hostname, $service);

            if ($sleep) {
                // Run the sleep tracking provider for this alert
                $sleep_info = getSleepDetailAtTimestamp($timestamp, $sleeptracking_settings[$sleep_provider], $sleepprovider_settings);
                if ($sleep_info) {
                    $sleep_state = $sleep_info['sleep_state'];
                    $mtts = $sleep_info['mtts'];
                    $sleep_level = $sleep_info['sleep_level'];
                    $confidence = $sleep_info['confidence'];
                }

            }

            $query = "INSERT INTO oncall_weekly (alert_id, range_start, range_end, timestamp, hostname, service, state, contact, output, tag, sleep_state, mtts, sleep_level, sleep_confidence, notes) VALUES
                ('$alert_id', '$range_start', '$range_end', '$timestamp', '$hostname', '$service', '$state', '$username', '$output', '$tag', '$sleep_state', '$mtts', '$sleep_level', '$confidence','$notes')";

            logline("Processing on call line with data: $query");
            if (!mysql_query($query)) {
                echo "Database update failed, error: " . mysql_error();
                logline("Database update failed, error: " . mysql_error());
            }
        }
        logline("Everything worked great, redirecting the user with success");
        Header('Location: add.php?oncall_succ=hellyeah');
    } else {
        logline("We didn't find any notifications to process, redirect user back to add page");
        Header('Location: add.php');
    }


}

