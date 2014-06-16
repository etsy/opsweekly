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
 *                                                or -1 for unknown/no sleep, or 0 if the person did not go back to sleep
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
    logline("SleepUP plugin is using URL {$url} for sleep data...");
    logline("SleepUP: Raw timestamp is {$timestamp}, searching from {$from} to {$to}");

    if (!$json = file_get_contents($url)) {
        return false;
    }

    if (!$data = json_decode($json, true)) {
        return false;
    }

    $mtts = -1;
    $confidence = -1;
    // We're not going to submit sleep level for the UP, because it's not accurate enough.
    // sure we will! use the below as default
    $sleep_level = -1;

    $jawboneSleepStates = array(
        1   =>  'awake',
        2   =>  'in light sleep',
        3   =>  'in deep sleep',
    );
    $firstWakeStateEpoch = "";
    $previousWakeStateEpoch = "";
    $firstWakeState = 1;    # default to 'stayed awake' for cases where we see null data
    $previousSleepState = 1; # default to 'awake'

    // TODO: there's duplicate code in this block; dedupe it
    foreach ( $data[0]['datapoints'] as $arrayKey => $sleepState ) {
        # if we see null state, typically the person is fully awake (i.e. no data)
        if ( $sleepState[1] >= $timestamp and empty($sleepState[0]) ) {
            $firstWakeStateEpoch = $sleepState[1];

            // get a slice of the previous sleep states so we can check for
            // previous sleep states to be certain of the engineer's sleep state
            // reverse the slice to speed up iterations looking back
            $allPreviousSleepStates = array_reverse( array_slice( $data[0]['datapoints'], 0, $arrayKey ), true );
            foreach ( $allPreviousSleepStates as $array => $previousSleepStatePair ) {
                if ( $previousSleepStatePair[0] == 2 or $previousSleepStatePair[0] == 3 ) {
                    $previousSleepState= $previousSleepStatePair[0];
                    $previousWakeStateEpoch = $previousSleepStatePair[1];
                    break;
                }
            }

            // make a clean break
            break;
        # otherwise, continue until we encounter the first wake state
        } elseif ( $sleepState[1] >= $timestamp and $sleepState[0] == "1.0" ) {
            $firstWakeState = $sleepState[0];
            $firstWakeStateEpoch = $sleepState[1];

            // get a slice of the previous sleep states so we can check for
            // previous sleep states to be certain of the engineer's sleep state
            // reverse the slice to speed up iterations looking back
            $allPreviousSleepStates = array_reverse( array_slice( $data[0]['datapoints'], 0, $arraykey ), true );
            foreach ( $allPreviousSleepStates as $previousArrayKey => $previousSleepStatePair ) {
                if ( $previousSleepStatePair[0] == 2 or $previousSleepStatePair[0] == 3 ) {
                    $previousSleepState= $previousSleepStatePair[0];
                    $previousWakeStateEpoch = $previousSleepStatePair[1];
                    break;
                }
            }

            // make a clean break
            break;
        }
    }

    logline("SleepUP: First wake state epoch: $firstWakeStateEpoch Alert epoch: $timestamp");
    logline("SleepUP: The engineer was {$jawboneSleepStates[ $previousSleepState ]} when the alert fired.");

    # find the next non-wake state
    $nextSleepStateEpoch = "";
    foreach ( $data[0]['datapoints'] as $array => $sleepState ) {
        # continue until we encounter the next non-wake state (2 or 3)
        # start from $firstWakeStateEpoch
        if ( $sleepState[1] >= $firstWakeStateEpoch and empty( $sleepState[0] ) ) {
            break;
        } elseif ( $sleepState[1] > $firstWakeStateEpoch and $sleepState[0] != "1.0" ) {
            $nextSleepStateEpoch = $sleepState[1];
            break;
        }
    }

    if ( !empty($nextSleepStateEpoch) ) {
        $mtts = ( $nextSleepStateEpoch - $firstWakeStateEpoch );
    } else {
        // this msg should make more sense if the engineer is already awake
        logline("SleepUP: The engineer never went back to sleep!");
        // Set the time to sleep to 0 to track this horrible occasion
        $mtts = 0;
    }
// frantz

    # rationale for assigning $sleep_state based on Jawbone sleep state comes
    # from http://en.wikipedia.org/wiki/Non-rapid_eye_movement_sleep:
    # Stage 2 – ... The sleeper is quite easily awakened... == Jawbone light sleep (2)
    # Stage 3 - ... associated with "deep" sleep... == Jawbone deep sleep (3)
    switch ($previousSleepState) {
    case 1:
        // If we have a data point saying the user was awake, they were definitely awake.
        $sleep_state = 0;
        $confidence = 100;
        break;
    case 2:
        $sleep_state = 1;
        // light sleep
        $sleep_level = 2;
        $confidence = 100;
        break;
    case 3:
        $sleep_state = 1;
        // deep sleep
        $sleep_level = 3;
        $confidence = 100;
        break;
    default:
        // Because of the way the UP graphite plugin works, if there is no data the user probably was awake.
        // this manifests as null sleep state values
        $sleep_state = 0;
        $confidence = 75;
        break;
    }

    logline("SleepUP: Finished, returning sleep_state: $sleep_state mtts: $mtts sleep_level: $sleep_level confidence: $confidence");
    return array("sleep_state" => $sleep_state, "mtts" => $mtts, "sleep_level" => $sleep_level, "confidence" => $confidence);
}

