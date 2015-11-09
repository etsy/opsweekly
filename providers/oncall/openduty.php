<?php
/**
 * Created by PhpStorm.
 * User: deathowl
 * Date: 11/8/15
 * Time: 2:45 PM
 */


/**
 * Openduty on call provider
 */

/** Plugin specific variables required
 * Global Config:
 *  - base_url: The path to your Openduty API, e.g. https://duty.company.com/api
 *
 * Team Config:
 *  - service_key: The service key that this team uses for alerts to be collected
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

function getOnCallNotifications($name, $global_config, $team_config, $start, $end) {
    $base_url = $global_config['base_url'];
    $service_key = $team_config['service_key'];
    $separator = $team_config['separator'];

    if ($base_url !== '' && $service_key != '') {
        // convert single Openduty service, to array construct in order to hold multiple services.
        if (!is_array($service_key)) {
            $service_key = array($service_key);
        }

        // loop through all Openduty services
        foreach ($service_key as $skey) {
            // check if the service key is formated correctly
            if (!sanitizeOpenDutyServiceKey($skey)) {
                logline('Incorect format for Openduty Service ID: ' . $skey);
                // skip to the next Service Key in the array
                continue;
            }

            // loop through Openduty's maximum incidents count per API request.
            $running_total = 0;
            $page=1;
            do {
                // Connect to the Openduty API and collect all incidents in the time period.
                $parameters = array(
                    'since' => date('c', $start),
                    'service_key' => $skey,
                    'until' => date('c', $end),
                    'page' => $page
                );

                $incident_json = doOpenDutyAPICall('/opsweekly', $parameters, $base_url);
                if (!$incidents = json_decode($incident_json)) {
                    return 'Could not retrieve incidents from Openduty! Please check your settings';
                }

                // skip if no incidents are recorded
                if (count($incidents->results) == 0) {
                    continue;
                }
                logline("Incidents on Service Key: " . $skey);
                logline("Total incidents: " . $incidents->count);
                logline("Number of page:" . $page);
                $page+=1;
                $running_total += count($incidents->results);



                logline("Running total: " . $running_total);
                foreach ($incidents->results as $incident) {
                    $time = strtotime($incident->occurred_at);
                    $service_key = $incident->incindent_key;
                    $output = $incident->output;
                    $service_parts = explode(":",$service_key);
                    if (count($service_parts) > 1) {
                        $hostname = array_shift($service_parts);
                        $service = implode(":", $service_parts);
                    } else {
                        $hostname ="Openduty";
                        $service = $service_key;
                    }

                    $valid_states = array("WARNING", "UNKNOWN", "DOWN"); #Dont include CRITICAL, as it's the default
                    $state = "CRITICAL";
                    foreach($valid_states as $astate) {
                        if (strpos($output, $astate) !== false) {
                            $state = $astate;
                        }
                    }
                    $notifications[] = array("time" => $time, "hostname" => $hostname, "service" => $service, "output" => $output, "state" => $state);
                }
            } while ($running_total < $incidents->count);
        }
        // if no incidents are reported, don't generate the table
        if (count($notifications) == 0 ) {
            return array();
        } else {
            return $notifications;
        }
    } else {
        return false;
    }
}

function doOpenDutyAPICall($path, $parameters, $openduty_baseurl) {


    $params = null;
    foreach ($parameters as $key => $value) {
        if (isset($params)) {
            $params .= '&';
        } else {
            $params = '?';
        }
        $params .= sprintf('%s=%s', $key, $value);
    }
    return file_get_contents($openduty_baseurl . $path . $params);
}

function whoIsOnCall($schedule_id, $time = null) {

    $until = $since = date('c', isset($time) ? $time : time());
    $parameters = array(
        'since' => $since,
        'until' => $until,
    );

    $json = doOpenDutyAPICall("/oncall/{$schedule_id}", $parameters);

    if (false === ($scheddata = json_decode($json))) {
        return false;
    }

    if (count($scheddata) == 0) {
        return false;
    }

    if ($scheddata['0']->person == "") {
        return false;
    }

    $oncalldetails = array();
    $oncalldetails['person'] = $scheddata[0]->person;
    $oncalldetails['email'] = $scheddata[0]->email;
    $oncalldetails['start'] = strtotime($scheddata[0]->start);
    $oncalldetails['end'] = strtotime($scheddata[0]->end);

    return $oncalldetails;

}

function sanitizeOpenDutyServiceKey($service_key) {
    $pattern = '/^[a-z0-9]{40}$/';
    if (preg_match($pattern, $service_key)) {
        return true;
    } else {
        return false;
    }
}
?>
