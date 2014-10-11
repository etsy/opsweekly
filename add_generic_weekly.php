<?php

include_once 'phplib/base.php';

if (!db::connect()) {
    echo "Database connection failed, cannot continue. ";
} else {
    $timestamp = time();
    $username = getUsername();
    $range_start = db::escape($_POST['range_start']);
    $range_end = db::escape($_POST['range_end']);
    $report_id = generateWeeklyReportID($username, $range_start, $range_end);
    $state = "final";
    $report = db::escape($_POST['weeklyupdate']);
    $query = "INSERT INTO generic_weekly (report_id, range_start, range_end, timestamp, user, state, report) VALUES ('$report_id', '$range_start', '$range_end', '$timestamp', '$username', '$state', '$report')";
    if (!db::query($query)) {
        echo "Database update failed, error: " . db::error();
    } else {
        if (isset($_POST['do_email'])) {
            # The user clicked the email button so also email a copy of the report
            if (sendEmailReport($username, $report, $range_start, $range_end)) {
                Header("Location: {$ROOT_URL}/add.php?weekly_succ_email=hellyeah");
            }
        } else {
            Header("Location: {$ROOT_URL}/add.php?weekly_succ=hellyeah");
        }
    }
}

