<?php 

require_once('phplib/base.php');

if (!isset($_SERVER['HTTP_REFERER'])) {
        die("Woah, what did you just try and do?");
} else {
        $return_path = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
}

if (!isset($_GET['l'])) {
    die("Locale wasn't specified");
} else {
    $locale = $_GET['l'];
}

if (!array_key_exists($locale, $locales)) {
    die("That locale isn't in the configuration, can't set");
}

setcookie('opsweekly_locale', $locale, time()+60*60*24*365);
Header("Location: {$return_path}");

?>
