<?php

/**
 * This is a sleep tracking provider.
 * Sleep tracking providers must provide a number of functions that can be called, and return data in a certain way.
 **/


/*
 * getSleepDetailAtTimestamp
 *  Given a unix timestamp, return an array of the state of sleep the given user was in for use with
 *  on call reporting
 *
 * @param   int         $timestamp      The unix timestamp when we should get sleep data for
 * @param   array       $user_options   The array of options the user provided that let's us do our job
 * @param   array       $plugin_options The array of options from the global config that let's us do our job
 *
 * @return  array|false $sleep_data     An array that contains:
 *                                          sleep_state: -1 for no data, 0 for awake, 1 for asleep
 *                                          mtts: The time in seconds, up to 7200 seconds, that the next asleep period occured after $timestamp
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

    // Do the relevant calculations
    // Here we'll just pretend that people were always awake!
    $sleep_state = 0;
    $mtts = null;
    $sleep_level = null;
    $confidence = 100;

    return array("sleep_state" => $sleep_state, "mtts" => $mtts, "sleep_level" => $sleep_level, "confidence" => $confidence);
}

