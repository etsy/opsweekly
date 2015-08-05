<?php

// This handles dispatching the oncall related functions to the correct plugin depending on the
// configuration.

function printOnCallNotifications($on_call_name, $start, $end, $oncall_start, $oncall_end) {
    global $nagios_state_to_badge, $nagios_alert_tags;

    # Non timezone version to store the data
    $range_start = $start;
    $range_end = $end;

    // Call the correct provider
    $oncall_config = getTeamConfig('oncall');
    if (!$oncall_config) {
        return '<div class="alert alert-error">Something terrible has happened. This team doesn\'t have on call enabled!</div>';
    }

    $provider_name = $oncall_config['provider'];
    logline("printOnCallNotifications has started, looking for provider {$provider_name}. ");

    // Get the global configuration for the provider
    $provider_config = getOnCallProvider($provider_name);
    $provider_global_options = $provider_config['options'];

    // Get the per team options for the provider
    $provider_team_options = getTeamOnCallConfig('provider_options');

    // Hopefully the user entered a valid oncall provider name...
    if (!$provider_config) {
        return '<div class="alert alert-error">Something terrible has happened. We cannot find an on-call provider named "'. $provider_name .'"</div>';
    }


    logline("printOnCallNotifications is loading {$provider_config['lib']}");
    // Load the provider specified.
    include_once "{$provider_config['lib']}";

    logline("Firing getOnCallNotificationsFromDb to get events stored in the database for the period...");
    $notifications_from_db = getOnCallNotificationsFromDb($oncall_start, $oncall_end);
    if (empty($notifications_from_db)) {
        logline("getOnCallNotificationsFromDb returned an empty array! The oncall provider may have events...");
    } else {
        logline("getOnCallNotificationsFromDb returned an array containing ". count($notifications_from_db) . " notifications");
        // Find the most recent event in the database and return its timestamp.
        // We'll use this as the start timestamp for querying the oncall provider.
        $most_recent_db_event_timestamp = getMostRecentEventTimestampFromDb($oncall_start, $oncall_end);
        logline("Most recent event timestamp from the database: {$most_recent_db_event_timestamp}");
        $oncall_start = $most_recent_db_event_timestamp;
    }

    logline("Firing getOnCallNotifications...");
    // And now ask for the notifications
    $notifications_from_provider = getOnCallNotifications($on_call_name, $provider_global_options, $provider_team_options, $oncall_start, $oncall_end);

    if (empty($notifications_from_provider)) {
        logline("getOnCallNotifications returned an empty array! Continuing with the events stored in the database.");
        $notifications = $notifications_from_db;
    } else {
        logline("getOnCallNotifications returned an array containing ". count($notifications) . " notifications");
        if (empty($notifications_from_db)) {
            // This is probably the first time the on-call engineer started writing the report so nothing is stored in the database.
            $notifications = $notifications_from_provider;
        } else {
            // Merge the results from the database into what we've received from our oncall provider.
            logline("Merging the database results with the provider results.");
            $notifications = array_merge($notifications_from_provider, $notifications_from_db);
        }
    }

    // Data collection complete. Time to render the form items for report submission. 

    // First, we populate the field with the on call ranges so the report is saved with the correct timestamp
    $html = "<input type='hidden' name='oncall[range_start]' value='{$range_start}'>";
    $html .= "<input type='hidden' name='oncall[range_end]' value='{$range_end}'>";
    // Add some javascript so we can toggle hidden events.
    $html .= "<script type='text/javascript'>";
    $html .= "  function toggleHiddenEvents() {";
    $html .= "    var events = document.getElementsByClassName('hiddenEvent');";
    $html .= "    for (var i=0; i < events.length; i++) {";
    $html .= "      events[i].style.display = events[i].style.display == 'none' ? '' : 'none';";
    $html .= "    }";
    $html .= "  }";
    $html .= "</script>";
    // Allow the user to display/hide hidden events.
    $html .= "<button class='btn btn-primary pull-right' type='button' onclick='toggleHiddenEvents()'>Toggle Hidden Events</button><br><br>";

    $timezone = getTimezoneSetting();
    date_default_timezone_set($timezone);
    $n_num = 0;
    $n_total = count($notifications);
    $timesaver = false;

    foreach ($notifications as $n) {
        # Add a row that lets the user potentially stop halfway and come back later
        if ($n_num >= ($n_total / 2) && !$timesaver) {
            $timesaver = true;
            $html .= "<tr><td colspan='7'><div class='well'><b>Hey!</b> You made it halfway. If you want you can save up to here and continue later.";
            $html .= "<button class='btn btn-primary pull-right' type='submit'>Save draft</button></div></td></tr>";
        }

        // Determine if we need to display a checked box for a hidden event.
        $hide_checked = $n['hide_event'] ? "checked" : "";

        $pretty_date = date("D d M H:i:s T", $n['time']);
        if ($n['hide_event']) {
            $html .= "<tr class='hiddenEvent' style='display:none'>";
        } else {
            $html .= "<tr>";
        }
        $html .= "<td>{$pretty_date}</td><td>{$n['hostname']}</td><td>{$n['service']}</td><td><pre><small>{$n['output']}</small></pre></td>";
        $html .= "<td><span class='label label-{$nagios_state_to_badge[$n['state']]}'>{$n['state']}</span></td>";

        # Need to populate all the information into hidden fields so we get all the data back nicely when the form is submitted
        $html .= "<input type='hidden' name='oncall[notifications][not_{$n_num}][hostname]' value='{$n['hostname']}'>";
        $html .= "<input type='hidden' name='oncall[notifications][not_{$n_num}][output]' value='{$n['output']}'>";
        $html .= "<input type='hidden' name='oncall[notifications][not_{$n_num}][time]' value='{$n['time']}'>";
        $html .= "<input type='hidden' name='oncall[notifications][not_{$n_num}][state]' value='{$n['state']}'>";
        $html .= "<input type='hidden' name='oncall[notifications][not_{$n_num}][service]' value='{$n['service']}'>";
        if ($n['id']) {
            $html .= "<input type='hidden' name='oncall[notifications][not_{$n_num}][id]' value='{$n['id']}'>";
        }
        $html .= "<td><input class='bulk-check' data-num='{$n_num}' type='checkbox'></td>";
        $html .= "<td><input class='hide-check' data-num='{$n_num}' name='oncall[notifications][not_{$n_num}][hide_event]' type='checkbox' {$hide_checked}></td>";
        $html .= "</tr>";
        if ($n['hide_event']) {
            $html .= "<tr class='hiddenEvent' style='display:none'>";
        } else {
            $html .= "<tr>";
        }
        $html .= "<td colspan='2'>";
        # Dropdown that lets the user choose a tag for the alert
        $html .= "<select name='oncall[notifications][not_{$n_num}][tag]' class='input-xlarge'>";
        foreach ($nagios_alert_tags as $tag => $tag_name) {
            //$selected = ($tag == $previous_tag) ? " selected" : "";
            $selected = ($tag == $n['tag']) ? " selected" : "";
            $html .= "<option value='{$tag}'{$selected}>{$tag_name}</option>";
        }
        $html .= "</select></td>";
        $html .= "<td colspan='5'><div class='control-group'><label class='control-label'><b>Notes:</b> </label>
            <div class='controls'><input type='text' name='oncall[notifications][not_{$n_num}][notes]' class='input-xxlarge' placeholder='Notes' value='{$n['notes']}'></div></div></td>";
        $html .= "</tr>";
        $n_num++;
    }
    date_default_timezone_set("UTC");

    return $html; 
}
