<?php
error_reporting(E_ALL ^ E_NOTICE);

if (!file_exists('phplib/config.php')) {
    die('Cannot find config.php! It must be in phplib and named config.php');
}

require_once 'config.php';
require_once 'oncall.php';
require_once 'irccat.php';
require_once 'pagination.class.php';
// report.php contains report rendering functions
include_once("phplib/report.php");

$pages = array("/index.php" => "Overview", "/add.php" => "Add", "/report.php" => "Reports", "/meeting.php" => "Meeting");
$pages_icon = array("/index.php" => "icon-home", "/add.php" => "icon-plus-sign", "/report.php" => "icon-list-alt", "/meeting.php" => "icon-bullhorn");
$nagios_state_to_badge = array("WARNING" => "warning", "CRITICAL" => "important", "UNKNOWN" => "info", "DOWN" => "inverse", "OK" => "success");
$nagios_state_to_bar = array("WARNING" => "warning", "CRITICAL" => "danger", "UNKNOWN" => "info", "OK" => "success");
$tag_to_badge = array("action" => "success", "noaction" => "important", "" => "default");
$nagios_alert_tags = array("" => "Untagged", "issue" => "Action Taken: Service Issue (View clean)", "issuetimeperiod" => "Action Taken: Service Issue, timeperiod inappropriate (View clean)",
    "viewissue" => "Action Taken: View issue (network/site outage, service health questionable)", "incorrecttimeperiod" => "No Action Taken: Timeperiod not appropriate", 
    "downtimeexpired" => "No Action Taken: Work ongoing, downtime expired", "downtimenotset" => "No Action Taken: Work ongoing, downtime not set", 
    "thresholdincorrect" => "No Action Taken: Threshold adjustment required", "checkfaulty" => "No Action Taken: Check is faulty/requires modification", "na" => "N/A");
$nagios_tag_categories = array("" => "Untagged", "action" => "Action Taken", "noaction" => "No Action Taken");
$nagios_tag_category_map = array("issue" => "action", "issuetimeperiod" => "action", "viewissue" => "action", "incorrecttimeperiod" => "noaction", 
    "downtimeexpired" => "noaction", "downtimenotset" => "noaction", "thresholdincorrect" => "noaction", "checkfaulty" => "noaction");
$locales = array("UK" => "Europe/London", "ET" => "America/New_York", "PT" => "America/Los_Angeles");
$sleep_states = array(-1 => "Unknown", 0 => "Awake", 1 => "Asleep");
$sleep_state_icons = array(0 => "icon-eye-open", 1 => "icon-eye-close");
$sleep_state_levels = array(-1 => "Unknown", 1 => "NREM Stage 1", 2 => "NREM Stage 2", 3 => "NREM Stage 3", 4 => "REM");


// Test to make sure we can handle this team
// .. and handle dev
$fqdn = (isset($fqdn)) ? $fqdn : $_SERVER['HTTP_HOST'];
$fqdn = preg_replace($dev_fqdn, $prod_fqdn, $fqdn);

if (!$team_data = getTeam($fqdn)) {
    die("I don't know what to do with this FQDN, please add it to config.php");
}

$ROOT_URL = getTeamUrl();

if (!function_exists('getUsername')) {
    die("You haven't taught opsweekly how to authenticate users! Please check user_auth.php or information in the README");
}

function getOrSetRequestedDate() {
    session_start();
    if (isset($_POST['date'])) { 
        $_SESSION['opsweekly_requested_date'] = $_POST['date'];
    }
    $date = (isset($_SESSION['opsweekly_requested_date'])) ? $_SESSION['opsweekly_requested_date'] : "now";

    return $date;
}

function getTimezoneSetting() {
    global $locales;

    if (!isset($_COOKIE['opsweekly_locale'])) {
        $tz = "UTC";
    } else {
        $tz = $locales[$_COOKIE['opsweekly_locale']];
    }

    return $tz;
}

function getWeekRange($date) {
    $date_bits = explode('(', $date);
    $date = array_shift($date_bits);
    $ts = strtotime($date);
    $target_start = (date('l', $ts) == "Monday") ? "monday" : "last monday";
    $target_end = (date('l', $ts) == "Sunday") ? "sunday" : "next sunday";
    $start = strtotime($target_start, $ts);
    $return_start = date('U', $start);
    $return_end = date('U', strtotime($target_end, $start));

    return array($return_start, $return_end);
}

function getOnCallWeekRange($date) {
    // This function returns a UTC period regardless of the timezone and is used to
    // store a static range throughout the year for storing/retrieving data from the database. 
    $oncall_timezone = getTeamOncallConfig('timezone');
    $oncall_start_time = getTeamOncallConfig('start');
    $oncall_end_time = getTeamOncallConfig('end');

    $date_bits = explode('(', $date);
    $date = array_shift($date_bits);
    $ts = strtotime($date);
    // If we're still in the report week, we need to make sure we don't skip forward to the next oncall
    // week otherwise the two become mismatched. 
    $ts = ( date('l', $ts) == "Saturday" || date('l', $ts) == "Sunday" ) ? $ts = $ts - 172800: $ts;
    $start = strtotime("last {$oncall_start_time}", $ts);
    $return_start = date('U', $start);
    $return_end = date('U', strtotime("next {$oncall_end_time}", $start));

    return array($return_start, $return_end);
}

function getOnCallWeekRangeWithTZ($date) {
    // This function is like the one above, except takes the timezone into account for accurate
    // retrieval of the alerts sent to the user from Splunk. Can't afford to be an hour out here. 
    $oncall_timezone = getTeamOncallConfig('timezone');
    $oncall_start_time = getTeamOncallConfig('start');
    $oncall_end_time = getTeamOncallConfig('end');

    $date_bits = explode('(', $date);
    $date = array_shift($date_bits);
    $ts = strtotime($date);
    $ts = ( date('l', $ts) == "Saturday" || date('l', $ts) == "Sunday" ) ? $ts = $ts - 172800: $ts;
    date_default_timezone_set($oncall_timezone);
    $start = strtotime("last {$oncall_start_time}", $ts);
    $return_start = date('U', $start);
    $return_end = date('U', strtotime("next {$oncall_end_time}", $start));
    date_default_timezone_set("UTC");

    return array($return_start, $return_end);
}

class db {
    static $dbh = false;

    static function connect() {
        global $mysql_host, $mysql_user, $mysql_pass;
        $mysql_db = getTeamConfig('database');

        if(function_exists("mysqli_connect")) {
            $mysqli = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
            if ($mysqli->connect_error) {
                echo insertNotify("critical", $mysqli->connect_error);
                return false;
            }
            self::$dbh = $mysqli;
        } else if(function_exists("mysql_connect")) {
            if(!$mysql = mysql_connect($mysql_host, $mysql_user, $mysql_pass)) {
                echo insertNotify("critical", mysql_error());
                return false;
            }
            if(!mysql_select_db($mysql_db, $mysql)) {
                echo insertNotify("critical", mysql_error());
                return false;
            }
        } else {
            echo "MySQL support is required";
            return false;
        }
        return true;
    }

    static function query($query) {
        if (!self::$dbh) self::connect();
        if (!self::$dbh) return false;
        if (self::$dbh instanceof mysqli) {
            return self::$dbh->query($query);
        } else {
            return mysql_query($query, self::$dbh);
        }
    }

    static function error() {
        if (self::$dbh instanceof mysqli) {
            return self::$dbh->error;
        } else {
            return mysql_error();
        }
    }

    static function escape($string) {
        if (!self::$dbh) self::connect();
        if (!self::$dbh) return false;
        if (self::$dbh instanceof mysqli) {
            return self::$dbh->real_escape_string($string);
        } else {
            return mysql_real_escape_string($string, self::$dbh);
        }
    }

    static function num_rows($stmt) {
        if (!self::$dbh) self::connect();
        if (!self::$dbh) return false;
        if (self::$dbh instanceof mysqli) {
            return $stmt->num_rows;
        } else {
            return mysql_num_rows($stmt);
        }
    }

    static function fetch_assoc($stmt) {
        if (!self::$dbh) self::connect();
        if (!self::$dbh) return false;
        if (self::$dbh instanceof mysqli) {
            if ($stmt->num_rows > 0) {
                return $stmt->fetch_assoc();
            } else {
                return false;
            }
        } else {
            if (mysql_num_rows($stmt) > 0) {
                return mysql_fetch_assoc($stmt);
            } else {
                return false;
            }
        }
    }

    static function fetch_all($stmt) {
        if (!self::$dbh) self::connect();
        if (!self::$dbh) return false;
        if (self::$dbh instanceof mysqli) {
            while($result = $stmt->fetch_assoc()) {
                $return[] = $result;
            }
        } else {
            while($result = mysql_fetch_assoc($stmt)) {
                $return[] = $result;
            }
        }
        return $return;
    }
}

function insertNotify($level, $message) {
    return '<div class="alert alert-' . $level .'">' . $message . '<a class="close" data-dismiss="alert" href="#">&times;</a></div>';
}

function generateWeeklyReportID($username, $range_start, $range_end) {
    return "$username-$range_start-$range_end";
}

function generateOnCallAlertID($timestamp, $hostname, $service) {
    $hostname = str_replace(' ', '-', $hostname);
    $service = str_replace(' ', '-', $service);
    return "$timestamp-$hostname-$service";
}

function generateMeetingNotesID($range_start, $range_end) {
    return "$range_start-$range_end";
}

function checkForPreviousWeeklyUpdate($report_id) {
    $report_id = db::escape($report_id);
    $results = db::query("SELECT * FROM generic_weekly where report_id='{$report_id}' order by id DESC LIMIT 1");
    return db::fetch_assoc($results);
}

function checkForPreviousMeetingNotes($report_id) {
    $report_id = db::escape($report_id);
    $results = db::query("SELECT * FROM meeting_notes where report_id='{$report_id}' order by id DESC LIMIT 1");
    return db::fetch_assoc($results);
}

function checkForPreviousOnCallItem($alert_id) {
    $alert_id = db::escape($alert_id);
    $results = db::query("SELECT * FROM oncall_weekly where alert_id='{$alert_id}' order by id DESC LIMIT 1");
    return db::fetch_assoc($results);
}

function checkForUserProfile($username) {
    $username = db::escape($username);
    $results = db::query("SELECT * FROM user_profile where ldap_username='{$username}' LIMIT 1");
    return db::fetch_assoc($results);
}

function guessPersonOnCall($range_start, $range_end) {
    $results = db::query("SELECT DISTINCT(contact) FROM oncall_weekly where range_start='{$range_start}' AND range_end='{$range_end}'  order by id DESC LIMIT 1");
    if (db::num_rows($results) == 1) {
        $result = db::fetch_assoc($results);
        return $result['contact'];
    } else {
        return false;
    }
}

function printHeaderNav() {
    global $pages, $pages_icon, $ROOT_URL;

    foreach ($pages as $url_path => $url_name) {
        $active = ($_SERVER['SCRIPT_NAME'] == $url_path) ? ' class="active"' : "";
        $url_path = "{$ROOT_URL}{$url_path}";
        echo "<li{$active}><a href='{$url_path}'><i class='{$pages_icon[$url_path]} icon-white'></i> {$url_name}</a></li>";
    }
}

function getGenericWeeklyReportsForWeek($range_start, $range_end) {
    $query = "SELECT * FROM generic_weekly WHERE id IN (SELECT max(id) FROM generic_weekly where range_start='{$range_start}' AND range_end='{$range_end}' GROUP BY(user)) ORDER BY user ASC;";
    $results = db::query($query);
    return db::fetch_all($results);
}

function getGenericWeeklyReportsForUser($username) {
    $username = db::escape($username);
    $query = "SELECT * FROM generic_weekly WHERE id IN (SELECT max(id) FROM generic_weekly where user='{$username}' GROUP BY(range_start)) ORDER BY range_end DESC;";
    $results = db::query($query);
    return db::fetch_all($results);
}

function getOnCallReportForWeek($range_start, $range_end) {
    // Pull all notifications except those that have been defined as hidden.
    $query = "SELECT * FROM oncall_weekly WHERE range_start='{$range_start}' AND range_end='{$range_end}' AND hide_event = '0' ORDER BY timestamp ASC;";
    $results = db::query($query);
    return db::fetch_all($results);
}

function getAvailableOnCallRangesForLastYear() {
    $year_ago = strtotime("-1 year");
    $query = "SELECT DISTINCT(range_start), range_end FROM oncall_weekly where range_start > '{$year_ago}' order by range_start ASC;";
    $results = db::query($query);
    return db::fetch_all($results);
}

function getAvailableOnCallRangesForUser($username) {
    $username = db::escape($username);
    $query = "SELECT DISTINCT(range_start), range_end FROM oncall_weekly where contact = '{$username}' order by range_start ASC;";
    $results = db::query($query);
    return db::fetch_all($results);
}

function getListOfPeopleWithReports() {
    $query = "SELECT DISTINCT(user) FROM generic_weekly ORDER BY user ASC;";
    $results = db::query($query);
    foreach(db::fetch_all($results) as $result) {
        $people[] = $result['user'];
    }
    return $people;
}

// Pull events stored in the database within the given period.
function getOnCallNotificationsFromDb($range_start, $range_end) {
    // We store 'timestamp' in the database, but providers refer to the value as 'time'.
    // Return the value keyed as 'time' so phplib/oncall.php can easily iterate over these
    // data along with notifications from the defined oncall provider.
    $query = "SELECT contact, hide_event, hostname, id, notes, output, service, state, tag, timestamp AS time FROM oncall_weekly WHERE timestamp >= '{$range_start}' AND timestamp <= '{$range_end}' ORDER BY time DESC;";
    $results = db::query($query);
    return db::fetch_all($results);
}

function getMostRecentEventTimestampFromDb($range_start, $range_end) {
    $query = "SELECT timestamp FROM oncall_weekly WHERE timestamp >= '{$range_start}' AND timestamp <= '{$range_end}' ORDER BY timestamp DESC LIMIT 1;";
    $results = db::query($query);
    if (db::num_rows($results) == 1) {
        $result = db::fetch_assoc($results);
        return $result['timestamp'];
    } else {
        return false;
    }
}

function handleSearch($search_type, $search_term) {
    switch ($search_type) {
    case 'service':
        $query = "SELECT * FROM oncall_weekly WHERE service LIKE '%{$search_term}%' AND hide_event = '0' ORDER BY timestamp DESC;";
        break;
    case 'host':
        $query = "SELECT * FROM oncall_weekly WHERE hostname LIKE '%{$search_term}%' AND hide_event = '0' ORDER BY timestamp DESC;";
        break;
    case 'generic_reports':
        $query = "SELECT a.* FROM generic_weekly a, (SELECT max(id) as id, range_start FROM generic_weekly WHERE report like '%{$search_term}%' GROUP BY(range_start)) b WHERE a.id = b.id ORDER BY a.timestamp DESC;";
        break;
    case 'meeting_notes':
        $query = "SELECT a.* FROM meeting_notes a, (SELECT max(id) as id, range_start FROM meeting_notes WHERE notes like '%{$search_term}%' GROUP BY(range_start)) b WHERE a.id = b.id ORDER BY a.timestamp DESC;";
        break;
    default:
        return false;
    }
    $results = db::query($query);
    return db::fetch_all($results);
}

function formatSearchResults(array $results, $search_type, $highlight_term, $limit = 0, $start = 0) {

    // If only a limited number of results is required, reduce the array down to that number. 
    if ($limit != 0) $results = array_slice($results, $start, $limit);

    switch ($search_type) {
    case 'service':
    case 'hostname':
        $results = highlightSearchQuery($results, $highlight_term, $search_type);
        $html = printOnCallTableHeader();
        foreach ($results as $n) {
            $html .= formatOnCallRowForPrint($n);
        }
        $html .= printOnCallTableFooter();
        break;

    case 'report':
        $results = highlightSearchQuery($results, $highlight_term, 'report');
        foreach ($results as $result) {
            $html .= formatWeeklyReportForPrint($result);
        }
        break;

    case 'notes':
        $results = highlightSearchQuery($results, $highlight_term, 'notes');
        foreach ($results as $result) {
            $html .= formatMeetingNotesForPrint($result, true);
        }
        break;

    default:
        return false;

    }

    return $html;

}

function highlightSearchQuery(array $results, $search_q, $search_field) {
    foreach ($results as $result) {
        $result[$search_field] = preg_replace("/{$search_q}/i", "<span class='text-success'>{$search_q}</span>", $result[$search_field] );
        $outgoing_results[] = $result;
    }
    return $outgoing_results;
}

function printMoreSearchTypeButton($type, $term) {
    global $ROOT_URL;

    return "<ul class='pager'><li class='next'>
                <a href='{$ROOT_URL}/search.php?query=" . urlencode("{$type}: {$term}") . "'>More {$type} results &rarr;</a>
              </li></ul>";
}

function printOnCallTableHeader() {
    return '<table class="table table-striped table-bordered table-hover" id="oncall-table" style="font-size: 90%">
            <thead>
            <tr><th>Date/Time</th><th>Host</th><th>Service</th><th>Output</th><th>State</th></tr>
            </thead>
            <tbody>';
}

function printOnCallTableFooter() {
    return '</tbody></table>';
}

function formatOnCallRowForPrint(array $n) {
    global $nagios_state_to_badge, $tag_to_badge, $nagios_alert_tags, $nagios_tag_category_map, $sleep_state_icons, $sleep_state_levels;

    $timezone = getTimezoneSetting();
    date_default_timezone_set($timezone);
    $pretty_date = date("D d/m H:i:s T", $n['timestamp']);
    if ($n['sleep_state'] >= 0) {
        $mtts = round($n['mtts'] / 60, 2);
        // Process sleep data to generate HTML
        $sleep_tooltip = "Sleep level: {$sleep_state_levels[$n['sleep_level']]}, confidence: {$n['sleep_confidence']}%";
        $pretty_sleeptime = ($n['sleep_state'] == 1) ? "<i class='icon-time'></i> <small><a class='sleep-tooltip' data-toggle='tooltip' title='{$sleep_tooltip}'>"
            . "{$mtts} min</a></small>" : "";
        $sleep_html = "<i class='{$sleep_state_icons[$n['sleep_state']]}'></i> {$pretty_sleeptime}";
    } else {
        $sleep_html = "";
    }

    $html = "<tr>";
    $html .= "<td>{$pretty_date} {$sleep_html}</td><td>{$n['hostname']}</td><td>{$n['service']}</td><td><pre><small>{$n['output']}</small></pre></td>";
    $html .= "<td><span class='label label-{$nagios_state_to_badge[$n['state']]}'>{$n['state']}</span></td></tr>";
    $tag = ($n['tag'] != "") ? "<span class='label label-{$tag_to_badge[$nagios_tag_category_map[$n['tag']]]}'><i class='icon-tag'></i><b>{$nagios_alert_tags[$n['tag']]}</b></span>" : "";
    $notes = ($n['notes'] != "") ? "<span><i class='icon-info-sign'></i> <b>{$n['contact']}:</b> {$n['notes']}</span>" : "";
    if ( ($n['tag'] != "") || ($n['notes'] != "") ) {
        $html .= "<tr><td colspan='3'>{$tag}</td><td colspan='2'>{$notes}</td></tr>";
    } else {
        $html .= "<tr><td colspan='5'></td></tr>";
    }
    date_default_timezone_set("UTC");

    return $html;
}

function formatWeeklyReportForPrint(array $data) {
    $pretty_time = getPrettyTime($data['timestamp']);
    $html = "<h3>{$data['user']}<small> written {$pretty_time}</small></h3>";
    $html .= "<div class='well well-small'><p>{$data['report']}</p></div>";

    return $html;
}

function formatMeetingNotesForPrint(array $data, $small_header = false) {
    $html = ($small_header) ? "<h4>Notes " : "<h2>Meeting Notes <small>";
    $html .= "taken by {$data['user']} at the " . getTeamName() ." Meeting held on " . date("l jS F Y", $data['timestamp']);
    $html .= ($small_header) ? "</h4>" : "</small></h2>";
    $html .= "<div class='well well-small'>{$data['notes']}</div>";

    return $html;
}

function printWeeklyHints($username, $from, $to) {
    $wanted_hints = getTeamConfig('weekly_hints');

    if (is_array($wanted_hints)) {
        foreach ($wanted_hints as $provider) {
            // Load each requested provider and run the printHints() to print the hints
            $provider_info = getWeeklyHintProvider($provider);
            if ($provider_info && require_once($provider_info['lib'])) {
                $provider_class = new $provider_info['class']($username, $provider_info['options'], $from, $to);
                echo "<h4>{$provider_info['display_name']}</h4>";
                echo $provider_class->printHints();
            } else {
                echo insertNotify("info", "Couldn't load weekly hint provider '{$provider}'! Please check your config.");
            }
        }
    }
}

function sendMeetingReminder($fqdn) {
    $team_name = getTeamName();

    $start_end = getWeekRange("1 week ago");
    $start_ts = $start_end[0];
    $end_ts = $start_end[1];

    $permalink = "http://{$fqdn}{$ROOT_URL}/meeting.php?week={$start_ts}";

    // If IRC is configured, send an IRC reminder
    if($irc_channel = getTeamConfig('irc_channel')) {
        $message = "{$team_name} Weekly meeting time! Link to this week's meeting: {$permalink}";
        sendIRCMessage($message, $irc_channel);
    }

    if($email_report_to = getTeamConfig('email_report_to')) {
        $subject = "{$team_name} Weekly Meeting time!";

        $message = "<html><body>";
        $message .= "<h3>Weekly Meeting for week ". date("l jS F Y", $start_ts) . " to " . date("l jS F Y", $end_ts ) . "</h3>";
        $message .= "<p>";
        $message .= "It's that time again! <br>";
        $message .= "Click here for this week's meeting report: <a href='{$permalink}'>{$permalink}</a>";
        $message .= "</p>";
        $message .= "</body></html>";

        sendEmail($email_report_to, $email_report_to, $subject, $message);
    }
}

function formatPagination($current_page, $result_count) {
    global $search_results_per_page;

    $pagination = new Pagination();
    $pagination->setCurrent($current_page);
    $pagination->setTotal($result_count);
    $pagination->setRPP($search_results_per_page);
    $pagination->setClasses(array("pagination", "pagination-right"));

    return $pagination->parse();
}

function getTeam($fqdn) {
    global $teams;
    return (array_key_exists($fqdn, $teams)) ? $teams[$fqdn] : false;
}

function getTeamUrl() {
    // Allow operation on a directory, instead of only on a fqdn
    $root_url = getTeamConfig('root_url');
    if ($root_url) {
        return $root_url;
    } else {
        return "";
    }
}

function getTeamName() {
    return getTeamConfig('display_name');
}

function getTeamConfig($option) {
    // Gets a value from the team's configuration options
    global $team_data;
    return (array_key_exists($option, $team_data)) ? $team_data[$option] : false;
}

function getTeamOncallConfig($option, $plugin_options = false) {
    // Gets a value from the team's on call configuration. Set "plugin" to true to get
    // an item from the plugin_options section instead. 
    global $team_data;
    if ($team_data['oncall']) {
        if ($plugin_options) {
            return (array_key_exists($option, $team_data['oncall']['plugin_options'])) ? $team_data['oncall']['plugin_options'][$option] : false;
        } else {
            return (array_key_exists($option, $team_data['oncall'])) ? $team_data['oncall'][$option] : false;
        }
    } else {
        // On call disabled
        return false;
    }
}

function getOnCallProvider($provider) {
    global $oncall_providers;
    return (array_key_exists($provider, $oncall_providers)) ? $oncall_providers[$provider] : false;
}

function getWeeklyHintProvider($provider) {
    global $weekly_providers;
    return (array_key_exists($provider, $weekly_providers)) ? $weekly_providers[$provider] : false;
}

function sendEmailReport($from_username, $report, $range_start, $range_end) {
    global $email_from_domain;

    if(!$email_report_to = getTeamConfig('email_report_to')) {
        return false;
    }

    // Remove any bare linefeeds, Evernote loveeees to insert them. 
    $report = str_replace('\r\n', '', $report);

    $subject = "Weekly Update";

    $message = "<html><body>";
    $message .= "<h3>Weekly report for week ending ". date("l jS F Y", $range_end ) . "</h3>";
    $message .= "<p>";
    $message .= stripslashes($report);
    $message .= "</p>";
    $message .= "</body></html>";

    if (sendEmail($email_report_to, strip_tags("{$from_username}@{$email_from_domain}"), $subject, $message)) {
        return true;
    } else {
        return false;
    }

}

function sendEmail($to, $from, $subject, $message) {
    $headers = "From: " . $from . "\r\n";
    $headers .= "Reply-To: " . $from . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

    if (mail($to, $subject, $message, $headers)) {
        return true;
    } else {
        return false;
    }
}

function logline($message) {
    global $error_log_file;
    $team_name = getTeamName();
    error_log(date("r") . " - {$team_name} - $message\n", 3, $error_log_file);
}

function getPrettyTime($referencedate=0, $timepointer='', $measureby='', $autotext=true){    ## Measureby can be: s, m, h, d, or y
    if ($timepointer == '') $timepointer = time();
    $Raw = $timepointer-$referencedate;    ## Raw time difference
    $Clean = abs($Raw);
    $calcNum = array(array('s', 60), array('m', 60*60), array('h', 60*60*60), array('d', 60*60*60*24), array('mo', 60*60*60*24*30));    ## Used for calculating
    $calc = array('s' => array(1, 'second'), 'm' => array(60, 'minute'), 'h' => array(60*60, 'hour'), 'd' => array(60*60*24, 'day'), 'mo' => array(60*60*24*30, 'month'));    ## Used for units and determining actual differences per unit (there probably is a more efficient way to do this)

    if ($measureby == '') {    ## Only use if nothing is referenced in the function parameters
        $usemeasure = 's';    ## Default unit

        for ($i=0; $i<count($calcNum); $i++) {      ## Loop through calcNum until we find a low enough unit
            if ($Clean <= $calcNum[$i][1]) {        ## Checks to see if the Raw is less than the unit, uses calcNum b/c system is based on seconds being 60
                $usemeasure = $calcNum[$i][0];      ## The if statement okayed the proposed unit, we will use this friendly key to output the time left
                $i = count($calcNum);               ## Skip all other units by maxing out the current loop position
            }
        }
    } else {
        $usemeasure = $measureby;                ## Used if a unit is provided
    }

    $datedifference = floor($Clean/$calc[$usemeasure][0]);    ## Rounded date difference

    if ($autotext==true && ($timepointer==time())) {
        if ($Raw < 0) {
            $prospect = 'from now';
        } else {
            $prospect = 'ago';
        }
    }

    if ($referencedate != 0) {        ## Check to make sure a date in the past was supplied
        if ($datedifference == 1) {    ## Checks for grammar (plural/singular)
            return $datedifference . ' ' . $calc[$usemeasure][1] . ' ' . $prospect;
        } else {
            return $datedifference . ' ' . $calc[$usemeasure][1] . 's ' . $prospect;
        }
    } else {
        return false;
    }
}
