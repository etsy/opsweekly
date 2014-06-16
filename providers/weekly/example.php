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

class ExampleHints {
    private $events_from, $events_to;
    private $username;

    public function __construct($username, $config, $events_from, $events_to) {
        $this->username = $username;
        $this->events_from = $events_from;
        $this->events_to = $events_to;
    }


    public function printHints() {
        return "Hello {$this->username}! Between {$this->events_from} and {$this->events_to} you did things!";
    }

}
?>

