<?php

/**
 * Example on call provider
 */

/** Plugin specific variables required
 *
 *  None
 *
 */

/**
 * getOnCallNotifications - Returns the notifications for a given time period and parameters
 *
 * Parameters:
 *   $on_call_name - The username of the user compiling this report
 *   $provider_global_config - All options from config.php in $oncall_providers - That is, global options.
 *   $provider_team_config - All options from config.php in $teams - That is, specific team configuration options
 *   $start - The unix timestamp of when to start looking for notifications
 *   $end - The unix timestamp of when to stop looking for notifications
 *
 * Returns 0 or more notifications as array()
 * - Each notification should have the following keys:
 *    - time: Unix timestamp of when the alert was sent to the user
 *    - hostname: Ideally contains the hostname of the problem. Must be populated but feel free to make bogus if not applicable.
 *    - service: Contains the service name or a description of the problem. Must be populated. Perhaps use "Host Check" for host alerts.
 *    - output: The plugin output, e.g. from Nagios, describing the issue so the user can reference easily/remember issue
 *    - state: The level of the problem. One of: CRITICAL, WARNING, UNKNOWN, DOWN
 */
function getOnCallNotifications($on_call_name, $provider_global_config, $provider_team_config, $start, $end) {
    $notifications = array(
        array("time" => "1402024945", "hostname" => "myhost.com", "service" => "CPU", "output" => "WARNING CPU iowait is 50%: user=15.25% system=1.05% iowait=63.29% idle=20.40%", "state" => "WARNING"),
    );

    return $notifications;
}

// You may put your own functions for data retrival and processing here!
