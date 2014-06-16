<?php

/**
 * This file is for your own use to control how you authenticate to Opsweekly.
 */

function getUsername() {
    // Make sure this is relevant to your environment.
    // For example, if you use SSO then maybe you have a HTTP header set, or a cookie.
    return $_SERVER['HTTP_X_USERNAME'];
}

/** Uncomment this section if you wish to use HTTP Basic Authentication with Apache
 *
function getUsername() {
    // User the PHP_AUTH_USER header which contains the username when Basic auth is used. 
    return $_SERVER['PHP_AUTH_USER'];
}

*/

