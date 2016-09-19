<?php

/**
** Bitbucket weekly provider
** Author: Antonino Abbate
**/

/** Plugin specific variables required
 * Global Config:
 *
 *  - bitbucket_api_url: The Bitbucket API url: https://api.bitbucket.org/2.0
 *  - bitbucket_team: the name of your team/organization in bitbucket (first part of repository->full_name)
 *  - bitbucket_user: the username used to query the API
 *  - bitbucket_password: the user password (not needed if you set an App password)
 *  - bitbucket_app_password: The App password (preferred)
 *
 */

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

class BitbucketHints {
    private $bibucket_api_url;
    private $events_from, $events_to;
    private $username;
    private $bitbucket_context;

    public function __construct($username, $config, $events_from, $events_to) {
        $bbusername_fromdb = getBitbucketUsernameFromDb();
        if (!($bbusername_fromdb == NULL)) {
            $this->username = str_replace('@', '\u0040', getBitbucketUsernameFromDb());
        } else {
            $this->username = $username;
        }
        $this->events_from = $events_from;
        $this->events_to = $events_to;
        $this->bitbucket_api_url = $config['bitbucket_api_url'];
        $this->bitbucket_team = $config['bitbucket_team'];
        if(isset($config['bitbucket_app_password'])) {
            $this->bitbucket_context = stream_context_create(array(
            'http' => array(
                'header'  => "Authorization: Basic " . base64_encode("{$config['bitbucket_user']}:{$config['bitbucket_app_password']}"))));  
        } else {
            $this->bitbucket_context = stream_context_create(array(
            'http' => array(
                'header'  => "Authorization: Basic " . base64_encode("{$config['bitbucket_user']}:{$config['bitbucket_password']}"))));
        }
    }

    public function printHints() {
        if (!$activities = $this->getBitbucketActivity()) {
            return insertNotify("error", "No Bitbucket activity could be loaded");
        }
        if (count($activities) > 0) {
            $html = "<ul>";
            foreach ($activities as $activity) {
                $repo_base = $activity['cr'];
                $message_base = $activity['cm'];
                $url_base = $activity['cu'];
                $date_base = $activity['cd'];
                if (((strtotime($date_base)) >= $this->events_from) && ((strtotime($date_base)) <= $this->events_to)) {
                    $html .= "<li><a href=\"{$url_base}\">";
                    $html .= "{$repo_base}</a> - {$message_base}</li>";
                }
            }
            $html .= "</ul>";
            return $html;
        } else {
            return insertNotify("error", "No Bitbucket activity could be found and/or loaded");
        }
    }

    private function getBitbucketRepos() {
        $base_repos_url = "{$this->bitbucket_api_url}/repositories/{$this->bitbucket_team}";
        $base_repos_content = file_get_contents($base_repos_url, false, $this->bitbucket_context);
        $bb_repos = json_decode($base_repos_content);
        $repos = array();
        $counter = 0;
        foreach ($bb_repos->values as $values) {
            $repos [$counter] = $values->name;
            $counter = $counter+1;
        }
        return $repos;
    }
    
    private function getBitbucketActivity() {
        $repositories = $this->getBitbucketRepos();
        $commits = array();
        $counter = 0;
        foreach ($repositories as $var => $val) {
            $base_commits_url = "{$this->bitbucket_api_url}/repositories/{$this->bitbucket_team}/{$val}/commits";
            $base_commits_content = file_get_contents($base_commits_url, false, $this->bitbucket_context);
            $bb_commits = json_decode($base_commits_content);
            foreach ($bb_commits->values as $commitvalues) {
                $commit_repo = $commitvalues->repository->full_name;
                if (isset($commitvalues->author->user)) {
                    $commit_author = $commitvalues->author->user->username;
                } else {
                    $commit_author = $commitvalues->author->raw;
                }
                $commit_url = $commitvalues->links->html->href;
                $commit_date = $commitvalues->date;
                $commit_message = $commitvalues->message;
                # This block takes only the user commits
                if ($commit_author == $this->username) {
                    $commits[$counter] = array("cr" => "$commit_repo","ca" => "$commit_author","cm" => "$commit_message","cd" => "$commit_date","cu" => "$commit_url");
                    $counter = $counter+1;
                }
            }
        }
        return $commits;    
    }
}

?>
