<?php

/**
 * Splunk on call provider
//

/** Plugin specific variables required
 * Global Config:
 *  - base_url: The path to your Splunk API, e.g. http://splunk.company.com:8089
 *  - username: The API user for your Splunk instance
 *  - password: The password for the user above
 *
 * Team Config:
 *  - splunk_search: The search filter that narrows down the results to the team.
 *    - The following variables are available for subsitution inside this plugin:
 *      - #logged_in_username# = The username of the person currently using opsweekly
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
    $search_index = (isset($provider_team_config['splunk_index'])) ? $provider_team_config['splunk_index'] : 'nagios';
    $search_filter = $provider_team_config['splunk_search'];

    // Variable replacement in the search filter, see config.php for the full list.
    $search_filter = str_replace("#logged_in_username#", "$on_call_name", $search_filter);

    // Perform a Splunk search to retrieve the Nagios notifications received in the specified timeperiod, with the specifed search filter. 
    $search = 'index='. $search_index .' '. $search_filter .' (state="WARNING" OR state="CRITICAL" OR state="UNKNOWN" OR state="DOWN") | fields *';
    $results = doSplunkSearch($search, $start, $end, $provider_global_config);
    if ($results['success'] === false) {
        return 'Failed to retrieve on call data from Splunk, error: ' . $results['error'];
    } else {
        foreach($results['data'] as $notification) {
            $service_name = (!isset($notification->service_name)) ? "Host Check" : $notification->service_name;
            $notifications[] = array("output" => $notification->check_output, "time" => $notification->_indextime, "contact" => $notification->contact, 
                "state" => $notification->state, "hostname" => $notification->hostname, "service" => $service_name);
        }
    }
    return $notifications;
}

function doSplunkSearch($query, $start, $end, $config, $max_results = 10000) {
    $splunk_baseurl = $config['base_url'];
    $splunk_username = $config['username'];
    $splunk_password = $config['password'];

    $params = array(
        'exec_mode' => 'oneshot',
        'earliest_time' => $start,
        'latest_time' => $end,
        'search' => "search $query",
        'output_mode' => 'json',
        'count' => $max_results,
    );

    $q_params = http_build_query($params);

    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_URL, "{$splunk_baseurl}/services/search/jobs");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $q_params);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERPWD, "$splunk_username:$splunk_password");

    if (!$response = curl_exec($ch)) {
        return array("success" => false, "error" => "curl failed, error: " . curl_error($ch) );;
    }

    $curl_info = curl_getinfo($ch);
    logline("Splunk provider HTTP status: {$curl_info['http_code']}");

    $json = json_decode($response);
    if (is_null($json)) {
        logline("NULL response from Splunk. This likely means no events matched our search criteria.");
        //return array("success" => false, "error" => "JSON decode failed!");
        return array("success" => true, "data" => array());
    } else {
        return array("success" => true, "data" => $json);
    }

}
