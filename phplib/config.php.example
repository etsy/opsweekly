<?php

// Login details for the MySQL database, where all the data is stored.
// The empty database schema is stored in opsweekly.sql
$mysql_host = "localhost";
$mysql_user = "opsweekly_user";
$mysql_pass = "my_password";

// The domain name your company uses to send email from, used for a reply-to address
// for weekly reports
$email_from_domain = "mycompany.com";

/**
 * Authentication configuration
 * Nagdash must know who is requesting pages, as every update entry etc is unique
 * to a single person. 
 * Therefore, you must define a function somewhere called getUsername()
 * that will return a plain text username string to Nagdash, e.g. "ldenness" or "bsmith"
 * 
 * Below are two examples to get you started. 

function getUsername() {
    // Make sure this is relevant to your environment.
    // For example, if you use SSO then maybe you have a HTTP header set, or a cookie.
    return $_SERVER['HTTP_X_USERNAME'];
}

function getUsername() {
    // Use the PHP_AUTH_USER header which contains the username when Basic auth is used.
    return $_SERVER['PHP_AUTH_USER'];
}

 **/



/**
 * Team configuration
 * Arrays of teams, the key being the Virtual Host FQDN, e.g. opsweekly.mycompany.com
 *
 * Options:
 * display_name: Used for display purposes, your nice team name.
 * email_report_to: The email address the weekly reports users write should be emailed to
 * database: The name of the MySQL database the data for this team is stored in
 * event_versioning: Set to 'on' to store each event with a unique id each time the on-call report is saved.
 *                   Set to 'off' to only update existing events and insert new ones each time the on-call report is saved. Makes for a cleaner database.
 *                   If undefined, defaults to 'on', for backwards compatibility.
 * oncall: false or an array. If false, hides the oncall sections of the interface. If true, please complete the other information.
 *   - provider: The plugin you wish to use to retrieve on call information for the user to complete
 *   - provider_options: An array of options that you wish to pass to the provider for this team's on call searching
 *       - There are variables for the options that are subsituted within the provider. See their docs for more info
 *   - timezone: The PHP timezone string that your on-call rotation starts in
 *   - start: Inputted into strtotime, this is when your oncall rotation starts.
 *            e.g. Match this to Pagerduty if you use that for scheduling.
 *   - end: Inputted into strtotime, this is when your oncall rotation ends.
 *          e.g. Match this to Pagerduty if you use that for scheduling.
 **/
$teams = array(
    "opsweekly.mycompany.com" => array(
        "root_url" => "/opsweekly",
        "display_name" => "Ops",
        "email_report_to" => "ops@mycompany.com",
        "database" => "opsweekly",
        "event_versioning" => "off",
        "oncall" => array(
            "provider" => "splunk",
            "provider_options" => array(
                "splunk_index" => 'nagios',
                "splunk_search" => 'contact="#logged_in_username#_pager"',
            ),
            "timezone" => "America/New_York",
            "start" => "friday 18:00",
            "end" => "friday 18:00",
        ),
        "weekly_hints" => array("jira", "github"),
        "irc_channel" => "#ops"
    ),
    "anotherweekly.mycompany.com" => array(
        "display_name" => "Team2",
        "email_report_to" => "team2@mycompany.com",
        "database" => "team2weekly",
        "weekly_hints" => array("jira", "github"),
        "oncall" => false
    ),
    "thirdweekly.mycompany.com" => array(
        "display_name" => "Third",
        "email_report_to" => "third@mycompany.com",
        "database" => "thirdweekly",
        "weekly_hints" => array("jira", "github"),
        "oncall" => array(
            "provider" => "pagerduty",
            "provider_options" => array(
                // Single PagerDuty Service id
                "pagerduty_service_id" => 'AB12CDE',
                // Multiple PagerDuty Service ids are set in the format array('XXYYZZ1', 'AABBCC2') etc...
                // "pagerduty_service_id" => array('AB12CDE', 'CC23DDE'),
            ),
            "timezone" => "America/New_York",
            "start" => "monday 12:00",
            "end" => "monday 12:00",
        ),
    ),
    "forthweekly.mycompany.com" => array(
        "display_name" => "Forth",
        "email_report_to" => "forth@mycompany.com",
        "database" => "forthweekly",
        "weekly_hints" => array("jira", "github"),
        "oncall" => array(
            "provider" => "logstash",
            "provider_options" => array(
                "notification-user-map" => array(
                    "username" => "nagioscontact",
                ),
            ),
            "timezone" => "America/New_York",
            "start" => "monday 12:00",
            "end" => "monday 12:00",
        ),
    ),
);

/**
 * Weekly hint providers
 *  A 'weekly' provider, or 'hints' is designed to prompt the
 *  user to remember what they did in the last week, so they can
 *  fill out their weekly report more accurately.
 *
 *  It appears on the right hand side of the "add" screen.
 *  Select which providers you want for your team using the 'weekly_hints'
 *  key in the teams array.
 *
 **/
$weekly_providers = array(
    "github" => array(
        "display_name" => "Github Activity",
        "lib" => "providers/weekly/github.php",
        "class" => "GithubHints",
        "options" => array(
            "github_url" => "https://github.com",
        ),
    ),
    "jira" => array(
        "display_name" => "JIRA Tickets",
        "lib" => "providers/weekly/jira.php",
        "class"=> "JIRAHints",
        "options" => array(
            "jira_api_url" => "https://jira.mycompany.com/rest/api/2",
            "jira_url" => "https://jira.mycompany.com",
            "username" => "jira_api_login",
            "password" => "jira_api_password",
        ),
    ),
    "example" => array(
        "display_name" => "Example HTML",
        "lib" => "providers/weekly/example.php",
        "class" => "ExampleHints",
        "options" => array(
        ),
    ),
);


/**
 * Oncall providers
 * These are used to retrieve information given a time period about the alerts the requesting
 * user received.
 **/
$oncall_providers = array(
    "splunk" => array(
        "display_name" => "Splunk",
        "lib" => "providers/oncall/splunk.php",
        "options" => array(
            "base_url" => "https://splunk.mycompany.com:8089",
            "username" => "splunkapiusername",
            "password" => "splunkapipassword",
        ),
    ),
    "example" => array(
        "display_name" => "Example",
        "lib" => "providers/oncall/example.php",
    ),
    "logstash" => array(
        "display_name" => "Logstash",
        "lib" => "providers/oncall/logstash.php",
        "options" => array(
            "base_url" => "http://localhost:9200",
        ),
    ),
    "pagerduty" => array(
        "display_name" => "Pagerduty",
        "lib" => "providers/oncall/pagerduty.php",
        "options" => array(
            "base_url" => "https://mycompany.pagerduty.com/api/v1",
            // Supports two auth methods. Username/password or apikey.
            // If you define apikey, then the username/password will be ignored
            "username" => "mylogin@mycompany.com",
            "password" => "password",
            // uncomment and define if you use apikeys
            // "apikey" => "XXXXXX",
        ),
    ),
);

/**
 * Sleep providers
 * These are used to track awake/asleep during alert times, and MTTS (mean time to sleep)
 * If you want to create your own sleep provider, you need to enter it's configuration here.
 *
 * Options:
 * - display_name: The name displayed in the UI for the sleep provider
 * - description: A description so the user knows to pick your provider
 * - logo: The URL path to a small (30x30px) logo for your sleep provider.
 * - options: An array of all the options you wish to present to the user.
 *            this data is passed to your provider. The key name is the option name.
 *   For each option you required, the following keys are available:
 *     - type: An HTML form field type. E.g. 'text'
 *     - name: A friendly name displayed to the user
 *     - description: The description of your option
 *     - placeholder: Text displayed inside the field as a placeholder text for the user
 * - lib: The path on disk to your provider. The provider should provide a few set functions;
 *        for more information on those, see the example plugin provided.
 * - Any other key: Some other option you want to pass to your provider that is NOT unique to
 *                  one user, but to the whole of opsweekly E.g. A web URL or other data.
 **/
$sleep_providers = array(
    "up" => array(
        "display_name" => "Jawbone UP",
        "description" => "The band that tracks your sleep",
        "logo" => "/assets/sleep/up.png",
        "options" => array(
            "graphite_prefix" => array(
                "type" => "text",
                "name" => "Graphite Prefix",
                "description" => "The root path to your sleep stats",
                "placeholder" => "ops.ldenness.sleep"
            )
        ),
        "lib" => "providers/sleep/SleepUP.php",
        "graphite_host" => "http://graphite.mycompany.com"
    ),
    "fitbit" => array(
        "display_name" => "fitbit",
        "description" => "the other sleep tracker",
        "logo" => "/assets/sleep/fitbit.png",
        "options" => array(
            "graphite_prefix" => array(
                "type" => "text",
                "name" => "Graphite Prefix",
                "description" => "The root path to your sleep stats",
                "placeholder" => "ops.dschauenberg.sleep")
            ),
        "lib" => "providers/sleep/SleepFitbit.php",
        "graphite_host" => "http://graphite.mycompany.com")
);

// The number of search results per page
$search_results_per_page = 25;

// Path to disk where a debug error log file can be written
$error_log_file = "/var/log/httpd/opsweekly_debug.log";

// Dev FQDN
// An alternative FQDN that will be accepted by Opsweekly for running a development copy elsewhere
// Fed into preg_replace so regexes are allowed
$dev_fqdn = "/(\w+).vms.mycompany.com/";
// The prod FQDN is then subsituted in place of the above string.
$prod_fqdn = "mycompany.com";

// Global configuration for irccat, used to send messages to IRC about weekly meetings.
$irccat_hostname = '';
$irccat_port = 12345;


