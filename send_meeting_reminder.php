<?php
// Cron this script to send out a reminder about your weekly meeting. 
// It gets a permalink for the last week's meeting page, and then sends it
// out via irccat and/or email, depending on what is configured in your team settings. 
//
// Usage:
// php /path/to/opsweekly/send_meeting_reminder.php <your-configured-cname>
//
// cron using something like:
// 0 14 * * 3 php /var/www/opsweekly//send_meeting_reminder.php myweekly.yourdomain.com
// to send the reminder on Wednesday at 2pm. 

$fqdn = $argv[1];

include_once('phplib/base.php');

sendMeetingReminder($fqdn);


?>
