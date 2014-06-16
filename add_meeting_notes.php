<?php

include_once 'phplib/base.php';

if (!connectToDB()) {
    echo "Database connection failed, cannot continue. ";
} else {
    $timestamp = time();
    $username = getUsername();
    $range_start = mysql_real_escape_string($_POST['range_start']);
    $range_end = mysql_real_escape_string($_POST['range_end']);
    $report_id = generateMeetingNotesID($range_start, $range_end);
    $notes = mysql_real_escape_string($_POST['weeklynotes']);
    $query = "INSERT INTO meeting_notes (report_id, range_start, range_end, timestamp, user, notes) VALUES ('$report_id', '$range_start', '$range_end', '$timestamp', '$username', '$notes')";
    if (!mysql_query($query)) {
        echo "Database update failed, error: " . mysql_error();
    } else {
        Header('Location: index.php?meeting_done=hellyeah');
    }
}

