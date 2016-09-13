<?php 

/**
 *  A 'weekly' provider, or 'hints' is designed to prompt the
 *  user to remember what they did in the last week, so they can
 *  fill out their weekly report more accurately.
 *
 *  The class name doesn't matter.. It's picked in the config.
 *
 *  Your constructor should accept the following variables:
 *  - $username: The username of the person the hints are for
 *  - $config: An array of the config options that came from config.php
 *  - $events_from: The beginning of the period to show hints for
 *  - $events_to: The end of the period to show hints for
 *
 *  Then, just create a public function 'printHints' that returns HTML to be
 *  inserted into the sidebar of the "add report" page.
 *
 **/

class JIRAHints {
    private $jira_api_url, $jira_url;
    private $events_from, $events_to;
    private $username;

    private $jira_context;

    public function __construct($username, $config, $events_from, $events_to) {
        $this->username = str_replace('@', '\u0040', getJiraUsernameFromDb());
        $this->events_from = $events_from;
        $this->events_to = $events_to;
        $this->jira_api_url = $config['jira_api_url'];
        $this->jira_url = $config['jira_url'];
        $this->jira_context = stream_context_create(array(
            'http' => array(
                'header'  => "Authorization: Basic " . base64_encode("{$config['username']}:{$config['password']}")
            )
        ));
    }

    public function printHints() {
        return $this->printJIRAForPeriod();
    }

    public function getJIRALastPeriod($days) {
        $user = strtolower($this->username);
        $search = rawurlencode("assignee = {$user} AND updated >= -{$days}days AND Status != New ORDER BY updated DESC, key DESC");
        $json = file_get_contents("{$this->jira_api_url}/search?jql={$search}", false, $this->jira_context);
        $decoded = json_decode($json);
        return $decoded;
    }

    public function getJIRAForPeriod($start, $end) {
        $user = strtolower($this->username);
        $search = "assignee = {$user} AND updated >= {$start} AND updated <= {$end} AND Status != New ORDER BY updated DESC, key DESC";
        $search = rawurlencode($search);
        $json = file_get_contents("{$this->jira_api_url}/search?jql={$search}", false, $this->jira_context);
        $decoded = json_decode($json);
        return $decoded;
    }

    public function printJIRALast7Days() {
        $tickets = $this->getJIRALastPeriod(7);
        if ($tickets->total > 0) {
            $html = "<ul>";
            foreach ($tickets->issues as $issue) {
                $html .= '<li><a href="' . $this->jira_url . '/browse/' . $issue->key. '" target="_blank">';
                $html .= "{$issue->key}</a> - {$issue->fields->summary} ({$issue->fields->status->name})</li>";
            }
            $html .= "</ul>";
            return $html;
        } else {
            # No tickets found
            return insertNotify("error", "No JIRA activity in the last 7 days found");
        }

    }

    public function printJIRAForPeriod() {
        // JIRA wants milliseconds instead of seconds since epoch
        $range_start = $this->events_from * 1000;
        $range_end = $this->events_to * 1000;
        $tickets = $this->getJIRAForPeriod($range_start, $range_end);
        if ($tickets->total > 0) {
            $html = "<ul>";
            foreach ($tickets->issues as $issue) {
                $html .= '<li><a href="' . $this->jira_url . '/browse/' . $issue->key. '" target="_blank">';
                $html .= "{$issue->key}</a> - {$issue->fields->summary} ({$issue->fields->status->name})</li>";
            }
            $html .= "</ul>";
            return $html;
        } else {
            # No tickets found
            return insertNotify("error", "No JIRA activity for this period was found");
        }
    }
}

?>
