<?php
/*
 * This file has all the functions that are used for rendering or calculating
 * items in the reports
 */

/*
 * renderStatusProgressBar()
 * Renders a bootstrap progress bar showing the various status of alerts recieved
 */
function renderStatusProgressBar($items, $total) {
    global $nagios_state_to_badge, $nagios_state_to_bar;

    foreach ($items as $type => $number) {
        $pct = round( ($number / $total) * 100, 2);
        $html_status_summary .= "<span class='well well-small'><span class='label label-{$nagios_state_to_badge[$type]}'>{$type}</span>  {$number} ({$pct}%) </span>&nbsp;";
        $html_status_bar .= "<div class='bar bar-{$nagios_state_to_bar[$type]}' style='width: {$pct}%;'></div>";
    }
    $html = '<div class="progress input-xxlarge">';
    $html .= $html_status_bar;
    $html .= '</div>';
    $html .= "<p>{$html_status_summary}</p>";

    return $html;
}

/*
 * renderTagTables()
 * Renders a table of tag summaries for a week or a year
 */
function renderTagTable($tags, $total, $tag_lookup) {
    global $tag_to_badge, $nagios_tag_category_map;
    arsort($tags);
    foreach ($tags as $type => $number) {
        $pct = round( ($number / $total) * 100, 2);
        $tag = $tag_lookup[$type];
        if (isset($nagios_tag_category_map[$type])) {
            $label = $nagios_tag_category_map[$type];
        } else {
            $label = $type;
        }
        $html_status_summary .= "<tr><td><span class='label label-{$tag_to_badge[$label]}'>{$tag}</span></td> <td> {$number} ({$pct}%) </td></tr>";
    }
    return '<table class="table">' . $html_status_summary . '</table>';
}

function renderSleepStatus($sleep_statuses, $status_total, $mtts_total, $rtts_count, $ntts_count) {
    global $sleep_state_icons, $sleep_states;

    foreach ($sleep_statuses as $type => $number) {
        $pct = round( ($number / $status_total) * 100, 2);
        $html_status_summary .= "<tr><td><i class='{$sleep_state_icons[$type]}'></i> <span class='label'>";
        $html_status_summary .= "{$sleep_states[$type]}</span></td> <td> {$number} ({$pct}%) </td></tr>";
    }
    $html = "<table class='table'>{$html_status_summary}</table>";

    if ($mtts_total != 0) {
        $html .= "<p class='lead'>Mean Time to Sleep: <i class='icon-time'> </i> ";
        $html .= round( ($mtts_total / $rtts_count) / 60, 2);
        $html .= " minutes</p>";
        $html .= "<p class='lead'>Time spent awake due to notifications: <i class='icon-time'> </i> ";
        $html .= round( ($mtts_total / 60 / 60), 2);
        $html .= " hours</p>";
    }
    $html .= "<p class='lead'>Number of times sleep was abandoned: {$ntts_count} times</p>";

    return $html;

}

function renderTopNTableBody($input_array, $limit = 10, $type = 'host') {
    if(!is_array($input_array)) {
        return false;
    }

    // Get the top N results
    // First, sort the array by value
    arsort($input_array);
    // Then slice the top N off the top.
    $a = array_slice($input_array, 0, $limit);

    $html = '';
    foreach($a as $k => $v) {
        if ($type == 'host') {
            $link="<a href=\"{$ROOT_URL}/search.php?query=host: {$k}\">{$k}</a>";
        } else {
            $link="<a href=\"{$ROOT_URL}/search.php?query=service: {$k}\">{$k}</a>";
        }
        $html .= "<tr><td>{$link}</td><td>{$v}</td></tr>";
    }

    return $html;
}

function renderTopNPrettyLine($input_array, $limit = 4) {
    if(!is_array($input_array)) {
        return false;
    }

    arsort($input_array);
    $a = array_splice($input_array, 0, $limit);

    foreach ($a as $k => $v) {
        $strings[] = "<strong>{$k}</strong> ({$v} times)";
    }

    return implode($strings, ", ");
}












