<?php

/**
 * Logstash on call provider
//

/** Plugin specific variables required
 * Global Config:
 *  - base_url: The path to your Logstash API, e.g. http://logstash.company.com:9200
 *
 * Team Config:
 *  - notification-user-map: Maps opsweekly usernames to nagios contact names
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
    // Perform a Logstash search to retrieve the Nagios notifications received in the specified timeperiod, with the specifed search filter.
    if (isset($provider_team_config['notification-user-map'][$on_call_name])) {
        $pagername = $provider_team_config['notification-user-map'][$on_call_name];
    } else {
        $pagername = $on_call_name;
    }
    $results = doLogstashSearch($pagername, $start, $end, $provider_global_config);
    if ($results['success'] === false) {
        return 'Failed to retrieve on call data from Logstash, error: ' . $results['error'];
    } else {
        $notifications = [];
        $seen = [];
        foreach($results["data"] as $notification) {
            $service_name = (!isset($notification['_source']['nagios_service'])) ? "Host Check" : $notification['_source']['nagios_service'];
            $output = $notification['_source']['nagios_message'];
            $time = $notification['_source']['nagios_epoch'];
            $notifyname = $notification['_source']['nagios_notifyname'];
            $state = $notification['_source']['nagios_state'];
            $hostname = $notification['_source']['nagios_hostname'];
            if (!$seen[$time][$hostname][$service_name][$notifyname]) {
                $notifications[] = array("output" => $output, "time" => $time, "contact" => $notifyname,
                    "state" => $state, "hostname" => $hostname, "service" => $service_name);
                $seen[$time][$hostname][$service_name][$notifyname] = 'true';
            }
        }
    }
    return $notifications;
}

function doLogstashSearch($pagername, $start, $end, $config, $max_results = 10000) {
    $logstash_baseurl = $config['base_url'];
    $qry = '{
            "query": {
                "filtered": {
                    "query": {
                        "query_string": {
                            "query": "nagios_notifyname:\"'.$pagername.'\" AND NOT (nagios_state:\"ACKNOWLEDGEMENT*\" or nagios_state:\"UP\" OR nagios_state:\"OK\") AND NOT nagios_service:\"SMS Check\""
                        }
                    },
                    "filter": {
                        "bool": {
                            "must": [
                                {
                                    "range": {
                                        "nagios_epoch": {
                                            "from": "'.$start.'",
                                            "to": "'.$end.'"
                                        }
                                    }
                                }
                            ]
                        }
                    }
                }
            },
            "size": "'.$max_results.'",
            "sort": {
                "nagios_epoch": {
                    "order": "asc"
                }
            }
        }';
    $ch = curl_init();
    $method = "GET";
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_URL, "{$logstash_baseurl}/_search/");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $qry);

    if (!$response = curl_exec($ch)) {
        return array("success" => false, "error" => "curl failed, error: " . curl_error($ch) );;
    }

    if (!$json = json_decode($response, true)) {
        return array("success" => false, "error" => "JSON decode failed!");
    } else {
        return array("success" => true, "data" => $json["hits"]["hits"]);
    }

}
