<?php

include_once 'phplib/base.php';

if (!db::connect()) {
    echo "Database connection failed, cannot continue. ";
} else {
    $timestamp = time();
    $username = getUsername();
    $range_start = db::escape($_POST['range_start']);
    $range_end = db::escape($_POST['range_end']);
    $report_id = generateMeetingNotesID($range_start, $range_end);
    $notes = db::escape($_POST['weeklynotes']);
    $query = "INSERT INTO meeting_notes (report_id, range_start, range_end, timestamp, user, notes) VALUES ('$report_id', '$range_start', '$range_end', '$timestamp', '$username', '$notes')";
    if (!db::query($query)) {
        echo "Database update failed, error: " . db::error();
    } else {
        Header("Location: {$ROOT_URL}/index.php?meeting_done=hellyeah");
    }
}

