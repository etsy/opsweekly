<?php

include_once 'phplib/base.php';

if (!db::connect()) {
    echo "Database connection failed, cannot continue. ";
} else {
    $username = getUsername();
    $range_start = db::escape($_POST['oncall']['range_start']);
    $range_end = db::escape($_POST['oncall']['range_end']);

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

        $event_versioning = getTeamConfig('event_versioning');
        // We'll create an array of value strings for both INSERT and UPDATE
        // statements that will be submitted after iterating over all the
        // notifications we've found. For notifications found to already exist
        // in the database, we'll perform UPDATEs. Otherwise we'll INSERT a new
        // row.
        $insert_values = array();
        $update_values = array();
        foreach($_POST['oncall']['notifications'] as $id => $n) {
            $sleep_state = -1;
            $mtts = -1;
            $sleep_level = -1;
            $confidence = -1;
            $timestamp = db::escape($n['time']);
            $hostname = db::escape($n['hostname']);
            $output = db::escape($n['output']);
            $service = db::escape($n['service']);
            $state = db::escape($n['state']);
            $tag = db::escape($n['tag']);
            $notes = db::escape(htmlentities($n['notes'], ENT_QUOTES));
            $alert_id = generateOnCallAlertID($timestamp, $hostname, $service);
            $hide_event = isset($n['hide_event']) ? '1' : '0';
            $id = isset($n['id']) ? $n['id'] : false; // Will only be present for events already in the database. Used for UPDATEs.

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

            // By default, assume events will be INSERTs. This is important in case
            // we want event versioning and can simply push this string into
            // the $insert_values array to be used in a single INSERT statement.
            $values = "('$alert_id', '$range_start', '$range_end', '$timestamp', '$hostname', '$service', '$state', '$username', '$output', '$tag', '$sleep_state', '$mtts', '$sleep_level', '$confidence','$notes','$hide_event')";

            // If event versioning is not defined or is explicitly enabled...
            if (!$event_versioning || (isset($event_versioning) && (strcmp($event_versioning, "on")) == 0)) {
                array_push($insert_values, $values); # Push to the $insert_values array and move on.
            // If event versioning is explicitly disabled...
            } elseif (isset($event_versioning) && (strcmp($event_versioning, "off")) == 0) {
                // See if this event exists in the database already.
                $select_query = "SELECT 1 from oncall_weekly WHERE alert_id='{$alert_id}'";
                $result = db::query($select_query);
                if ($result) {
                    // If there's at least 1 row returned, the event has already
                    // been stored. We'll update it.
                    if ($result->num_rows > 0) {
                    // Create a hash representing the values. We'll use the keys to
                    // specifiy the column names we're updating.
                    $values = array('alert_id'          => $alert_id,
                                    'range_start'       => $range_start,
                                    'range_end'         => $range_end,
                                    'timestamp'         => $timestamp,
                                    'hostname'          => $hostname,
                                    'service'           => $service,
                                    'state'             => $state,
                                    'contact'           => $username,
                                    'output'            => $output,
                                    'tag'               => $tag,
                                    'sleep_state'       => $sleep_state,
                                    'mtts'              => $mtts,
                                    'sleep_level'       => $sleep_level,
                                    'sleep_confidence'  => $confidence,
                                    'notes'             => $notes,
                                    'hide_event'        => $hide_event,
                                    'id'                => $id);
                        array_push($update_values, $values);
                    // This is a fresh event.
                    } else {
                        array_push($insert_values, $values);
                    }
                // We failed query the database for the event.
                } else {
                    echo "Database select failed, error: " . db::error();
                    logline("Database select failed, error: " . db::error());
                }
    
                $result->free(); // Free it up, y'all.
            } else {
                logline("'event_versioning' appears to be defined but with an unknown value. Check phplib/config.php and be certain it's set to either 'on' or 'off'.");
            }
        }
        // Do we have INSERTs and/or UPDATEs to execute?
        if (count($insert_values) > 0) {
            $insert_values_string = implode(', ', $insert_values);
            $insert_query = "INSERT INTO oncall_weekly (alert_id, range_start, range_end, timestamp, hostname, service, state, contact, output, tag, sleep_state, mtts, sleep_level, sleep_confidence, notes, hide_event) VALUES {$insert_values_string}";
            if (!db::query($insert_query)) {
                echo "Database insert failed, error: " . db::error();
                logline("Database insert failed, error: " . db::error());
            }
        }
        // NOTE: This doesn't solve for entries that are already duplicated.
        // But this should prevent future entries from being duplicated.
        if (count($update_values) > 0) {
            foreach ($update_values as $update_hash) {
                $set_columns = array();
                foreach ($update_hash as $column => $value) {
                    array_push($set_columns, "{$column}='{$value}'");
                }
                $update_values_string = implode(', ', $set_columns);
                // We need to match id because sometimes our providers return duplicate entries where all values match.
                $update_query = "UPDATE oncall_weekly SET {$update_values_string} WHERE alert_id='{$update_hash['alert_id']}' AND id='{$update_hash['id']}'";
                if (!db::query($update_query)) {
                    echo "Database update failed, error: " . db::error();
                    logline("Database update failed, error: " . db::error());
                }
            }
        }
        logline("Everything worked great, redirecting the user with success");
        Header('Location: add.php?oncall_succ=hellyeah');
    } else {
        logline("We didn't find any notifications to process, redirect user back to add page");
        Header('Location: add.php');
    }


}

