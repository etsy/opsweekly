<?php

// This is a sleep tracking provider. 
// Sleep tracking providers must provide a number of functions that can be called, and return data in a certain way. 
//


/*
 * getSleepDetailAtTimestamp
 * @param   int         $timestamp      The unix timestamp when we should get sleep data for
 * @param   array       $user_options   The array of options the user provided that let's us do our job
 * @param   array       $plugin_options The array of options from the global config that let's us do our job
 *
 * @return  array|false $sleep_data     An array that contains:
 *                                          sleep_state: -1 for no data, 0 for awake, 1 for asleep
 *                                          mtts: The time in seconds, up to 7200, that the next asleep period occured after $timestamp
 *                                                or -1 for unknown/no sleep.
 *                                          sleep_level: As per the standard measure of 4 levels of sleep, if available:
 *                                              - 1: NREM Stage 1
 *                                              - 2: NREM Stage 2
 *                                              - 3: NREM Stage 3
 *                                              - 4: REM
 *                                          confidence: The confidence the plugin has that the data it has is accurate
 *                                              - 0: No confidence
 *                                              - 100: Very confident
 */
function getSleepDetailAtTimestamp($timestamp, $user_options, $plugin_options) {
    $graphite_host = $plugin_options['graphite_host'];
    $graphite_prefix = $user_options['graphite_prefix'];

    // We allow some tolerances here to account for differences in time sync and graphite timestamp rounding
    $from = floor(($timestamp - 180) / 100)*100;
    $to = $timestamp + (120 * 60);

    // Generated URL. Add together all the states, so 1 == awake, 2 == light sleep, 3 == deep sleep.
    $url = "{$graphite_host}/render/?target=sumSeries({$graphite_prefix}.details.deep,{$graphite_prefix}.details.light,{$graphite_prefix}.details.awake)";
    $url .= "&from={$from}&until={$to}&format=json";

    if (!$json = file_get_contents($url)) {
        return false;
    }

    if (!$data = json_decode($json, true)) {
        return false;
    }

    $mtts = -1;
    $confidence = -1;
    // We're not going to submit sleep level for the Fitbit, because it's not accurate enough.
    $sleep_level = -1;

    // Get the sleep state at the timestamp
    // First, we need to find the timestamp of the alert in the JSON, since we fudged the start
    // time. We go through the values and find the data point that is closest to our datapoint. 
    $nearest_datapoint = 0;
    foreach ($data[0]['datapoints'] as $k => $v) {
        if ($v[1] >= $timestamp) {
            // If we exceeded the timestamp, go back to the previous one and then bail out. 
            $nearest_datapoint = $k - 1;
            break;
        }
    }

    $first_data_point = $data[0]['datapoints'][$nearest_datapoint][0];
    switch ($first_data_point) {
    case '1':
        // If we have a data point saying the user was awake, they were definitely awake.
        $sleep_state = 0;
        $confidence = 100;
        break;
    case '2':
    case '3':
        // If we have a data point saying the user was asleep, it almost certainly was the case.
        $sleep_state = 1;
        $confidence = 100;
        break;
    default:
        // Because of the way the Fitbit graphite plugin works, if there is no data the user probably was awake.
        $sleep_state = 0;
        $confidence = 75;
        break;
    }

    // Time to figure out if and when they got back to sleep. 
    foreach (array_slice($data[0]['datapoints'], $nearest_datapoint + 2) as $d) {
        if ($d[1] > $timestamp && $d[0] >= 2) {
            $mtts = $d[1] - $timestamp;
            break;
        }
    }

    return array("sleep_state" => $sleep_state, "mtts" => $mtts, "sleep_level" => $sleep_level, "confidence" => $confidence);
}


