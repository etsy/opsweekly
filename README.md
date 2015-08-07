# Opsweekly [![Build Status](https://travis-ci.org/etsy/opsweekly.svg?branch=master)](https://travis-ci.org/etsy/opsweekly)
[![Deploy](https://www.herokucdn.com/deploy/button.png)](https://heroku.com/deploy?template=https://github.com/etsy/opsweekly.git)

## What is Opsweekly?

Opsweekly is a **weekly report tracker**, an **on call categorisation** and **reporting** tool, a **sleep tracker**, a **meeting organiser** and a coffee maker all in one. 

The goal of Opsweekly is to both organise your team into one central place, but also helps you understand and improve your on call rotations through the use of a simple on call "survey", and reporting as a result of that tracking. 

Alert classification is a complicated task, but with Opsweekly a few simple questions about each alert received can pay dividends in improving the on call experience for your engineers. 

## Features


* **Weekly Updates**: Every member of your team can write a weekly status update using hints (e.g. Github commits, JIRA tickets) to inform the team what they've been working on, and then optionally email it out.
* **On-Call Alert Classification**: Track, measure and improve your on call rotations by allowing your engineers to easily classify and document the alerts they received. 
	* Make a simple assesment for each alert that relates to whether action was taken, no action was taken, or whether the alert needs modification for follow up later
	* Free notes field to allow documentation of actions taken to refer back to later
	* Bulk classification for time saving
* **Sleep Tracking**: If your engineers have popular life tracking devices such as Fitbit or Jawbone UP, they integrate with Opsweekly to provide even more insight into the effect on call is having on their lives. 
	* Mean time to sleep (MTTS) and sleep time lost to notifications are calculated.
	* Easy to configure but gives valuable data that could lead to questions like "can this alert wait until morning as it keeps waking up our engineers?"
* **In depth reporting**: As you start to build up data, Opsweekly starts to generate reports and graphs illustrating your on call rotations. 
	* Examples include: action taken vs no action taken on alerts, what alerts wake people up the most, mean time to sleep, top notifying hosts/services, average alert volume per day, and how on call has improved (or not) over the last year
* **Personal reporting**: As well as a summary for all rotations, users are able to gain insight into their own behaviours. 
	* How have their on calls affected them? 
	* How much sleep do they lose on average? 
	* How does this compare to others?
* **Meeting Mode**: Make running a weekly meeting simple with all the data you need in one page, and a facility for people to take notes. 
	* Meeting mode hides all UI displaying only information required for the meeting.
	* The on call report for the previous week is included, along with key stats and elements from report
	* All weekly updates are displayed in case items need to be discussed
	* Set up a cron to remind people about the weekly meeting and provide the permalink to the meeting 
* **Powerful Search**: All data is searchable using a powerful search function. The default search mode is fuzzy, which will return results from all data stored in Opsweekly. However, you can get more specific: 
	* Search previous on call alerts for a history of that alert, previous engineer's notes, how the alerts were classified (is this alert constantly "no action taken?") and a time map showing it's frequency over the past year. 
	* Search Weekly Updates for full context on changes made previously
	* Search Meeting Notes for agenda items discussed in previous meetings
* **Fully timezone aware**: Obviously it's important for users to be editing the alerts they receive in the timezone that they received them in. Each user can set their own timezone for the whole Opsweekly UI.
* **Fill in as you go/drafts**: Both the Weekly report and the On-call reports can be updated to multiple times during the week, so the user does not have to edit a hefty report at the end. 

## Screenshots
### Please visit the [screenshot README](screenshots/README.md) for a guided tour of how Opsweekly works and the reports it can generate!


## Prerequisites
* A webserver
* PHP 5.4 (or higher), including the curl extensions for PHP, MySQL extension, and short_open_tags enabled
* MySQL for data storage


## Installation/configuration
1. Download/clone the repo into an appropriate folder either in your
   webservers directory or symlinked to it. or:
1. Create a configuration in your webserver for Opsweekly, if using it as a seperate domain (e.g. VirtualHost)
1. You *must* increase the PHP variable `max_input_vars` for submitting on-call reports. See [Increasing max input vars](#increasing-max-input-vars)
1. Create a MySQL database for opsweekly, and optionally grant a new user access to it. E.g.:
   * `mysql> create database opsweekly;`
   * `mysql> grant all on opsweekly.* to opsweekly_user@localhost IDENTIFIED BY 'my_password';`
1. Load the database schema into MySQL, e.g. `mysql -u opsweekly_user opsweekly < opsweekly.sql`
1. [Teach Opsweekly how to authenticate your users](#authenticating-with-opsweekly).
1. Move phplib/config.php.example to phplib/config.php, edit with your favourite editor (more detail below)
1. Load Opsweekly in your browser
1. Reward yourself with a refreshing beverage.

## Providers/Plugins
Opsweekly uses the concept of "providers" for the various pieces of data it needs. 
These are like plugins and can vary from team to team. 

The following providers are used: 

* `providers/weekly/`: These are known as weekly "hints" which are used to helpfully hint or remind people what they did in the last week when writing their reports. 
	* Weekly hint provider peoples include Github (showing recent commit activity) or JIRA (showing tickets closed in that time period)
* `providers/oncall/`: These are used to pull in notifications from somewhere for the on call engineer to document. 
	* For example, if you're using Logstash or Splunk to parse your Nagios logs, or pull in alerts sent to Pagerduty. 
* `providers/sleep`: These are used to query an external datasource to establish whether the on call engineer was asleep during the notifications he or she received. 
	* Opsweekly has been tested with Jawbone UP and Fitbit sleep trackers with success


The theory behind the providers mean if Opsweekly is not pulling data from a service you're currently using, it should be trivial to write your own and plug them in. Generally providers have two sets of configuration: One global for your entire instance, and then one config per team (or user, in the case of sleep)

For more information about how to configure the providers or to write your own, please see the documentation in each of the provider directories mentioned above. 



## Configuration

The config.php.example contains an example configuration to get you on your way. It's fairly well commented to explain the common options, but we'll go into more depth here: 

### Authenticating with Opsweekly
It's very important that Opsweekly knows who everyone who uses Opsweekly is, so the first step of using Opsweekly is to teach it how to understand who people are. 

In `config.php`, there is the important function, `getUsername`. This function must return the username, for example, "ldenness".
You can write whatever PHP you like here; perhaps your SSO passes a HTTP header, or sets a cookie you can read to get the username.

The `config.php.example` has a couple of examples, one that will use the username from HTTP Basic Auth that can be configured with Apache.

### Increasing max input vars
PHP has a default limit of the number of variables that can be input via form submission. Because compiling and submitting the on-call report is essentially just submitting a giant form, you must increase this value or your reports will be truncated! 

Look for the configuration option `max_input_vars` in your PHP configuration (e.g. php.ini) or if you have your own Virtualhost (e.g. in Apache) you can do something like: `php_value max_input_vars 10000` to increase the limit. 

We highly suggest increasing to 10000 for future proofing your on-call reports. There's no real downside to this if you're limiting it to Opsweekly. The limit is to try and protect against exploits by hash collisions (basically, someone DoS-ing forms on your site). But you should not run Opsweekly exposed on the internet anyway. 

### Teams configuration
Opsweekly has the ability to support many different teams using the same codebase, if required. Each team gets it's own "copy" of the UI at a unique URL, and their data is stored in a seperate database. 

Even if you only intend to use one team, the `$teams` array contains most of the important configuration for Opsweekly. 

The key of the array(s) in the `$teams` array is the FQDN that you will access Opsweekly via, e.g. `opsweekly.mycompany.com`. 

Inside this array are many configuration options:

* `display_name`: The "friendly" or display name for your team is used throughout the UI to describe your team. For example, "Ops"
* `root_url`: If your installation is on a path other than "/", enter the path here. For example, if your desired URL is "http://intranet.mycompany.com/opsweekly" would enter "/opsweekly".
* `email_report_to`: The email address of the mailing list your team uses to communicate, used for sending weekly reports (if the person requests it) or any other email communication. 
* `database`: The name of the MySQL database Opsweekly will try and use for this team
* `oncall`: Either `false` or another array containing configuration regarding your on call rotations. 
	* If you wish for this team's on call data to be tracked, this should be an array containing the following information:
	* `provider`: Which on call provider you wish to use for this team to fetch information, for example "splunk", "logstash" or "pagerduty"
	* `provider_options`: An array of team unique configuration options that this plugin requires. The list of these is available in the documentation for the provider itself. For example, Pageduty will require the service ID. 
	* `timezone`: The PHP style timezone that this team operates in, or rather the timezone that your on call rotation starts in. A great example here is to take this (and the following two variables) directly from Pagerduty if you use that for scheduling your on call rotations
	* `start`: The time when your on call rotation starts. This is input into strtotime so it can be friendly text like "friday 18:00" for 6pm on Friday
	* `end`: As above, except when your on call rotation ends. 
* `weekly_hints`: The weekly hint providers you wish to use for these team to prompt people to fill in their weekly reports. There are examples in the `providers/weekly` folder, for example Github (pulling in recent commits) and JIRA (pulling in closed tickets)
* `irc_channel`: The IRC channel your team uses. Used for various IRC integrations (currently just warning about weekly meeting time, if cron is set up)

You can have as many teams as you want in the `$teams` array, they just need to have unique FQDNs. 

### Weekly "hint" provider configuration
In this section you define and confgure the available weekly hint providers. These are displayed on the right hand side of the "Add" page so people have some information infront of them about what they did for a prompt to write their updates. 

Of course, you are free to write your own that suits your needs. If you wish to do so, please see the documentation inside of the `providers/weekly` folder. 

The `$weekly_providers` array handles the definition and configuring of the plugins in the `providers/weekly` folder. The array key should be a simple name of your provider, e.g. "github". This name is referred to in the teams configuration under `weekly_hints`. Then as values inside the array, the following are required:

* `display_name`: Displayed above the output from your plugin, this is the friendly header name for your provider, e.g. "Last week's tickets"
* `lib`: The path to the PHP file that contains your provider, e.g. `providers/weekly/github.php`
* `class`: The class name you're using for your weekly provider, which will be created if requested by the team configuration
* `options`: An array of arbritrary key/value pairs that are passed into the provider when it's loaded, used for configuration that is to be shared between all teams. For example, a path to an API, or a username and password to login to an API. 



### On call provider configuration
In this section you define and configure the available on call notification providers. On call providers are plugins that given a time period and a username (and the configuration we will enter both here and in the team configuration) will fetch all the notifications the person received in that time period, so they can classify the alerts. 

Of course, you are free to write your own that suits your needs. If you wish to do so, please see the documentation inside of the `providers/oncall` folder. 

The `$oncall_providers` array handles the definition and configuring of the plugins in the `providers/oncall` folder. The array key should be a simple name of your provider, e.g. "pagerduty". This name is referred to inside the teams configuration in the on call section as `provider`. Then as values inside the array, the following are required:

* `display_name`: A friendly, display name for your provider (e.g. Pagerduty)
* `lib`: The path to the PHP file that contains your provider code, e.g. `providers/oncall/pagerduty.php`
* `options`: An array of arbritrary key/value pairs that are passed into the provider when it's loaded, used for configuration that is to be shared between all teams. For example, a path to an API, or a username and password to login to an API. 


### Sleep provider configuration
In this section you can define and configure the sleep providers that users can choose in their "Edit Profile" screen. Sleep providers are plugins that given a unix timestamp, will return data on the sleep state of the user (for example, were they asleep and how deep asleep were they, and did they/how long did it take for them to get back to sleep)

We use this data to generate interesting reports about how on call rotations are affecting engineers sleep patterns, and help the team try and improve this required practice. For example, by listing alerts that most woke engineers, you could make a concious decision to wait to send that alert until morning, if it's not urgent enough. 

The data is only stored alongside the notifications in the MySQL database, never shared. 

Of course, you are free to write your own that suits your needs. If you wish to do so, please see the documentation inside of the `providers/sleep` folder. 

The `$sleep_providers` array handles the definition and configuring of the plugins in the `providers/sleep` folder. The array key should be a simple name of your provider. The values must include the following:

* `display_name`: A friendly name to display on the UI of Opsweekly for this provider. E.g. "Jawbone UP"
* `description`: A description of the sleep tracker, to differentiate it from others
* `logo`: Please place a logo in an addressable location, e.g. in the `/assets/sleep/` directory (30x30px) and place the URL path to it here. 
* `options`: An array of key/value pairs that will be used to display configuration options in the UI to users. Unlike other providers, sleep tracking is a per user subject, so configuration is entered via the "Edit Profile" screen, and stored in the database. Each option is parsed to create a HTML form input field. The key should be the option name. The following values are required:
	* `type`: The type of input field. Currently only `text` is supported/tested.
	* `name`: The friendly "field name" for the input box
	* `description`: The description of what the user shoud enter, displayed next to the input box
	* `placeholder`: Placeholder text displayed inside of the text box
* `lib`: The path to the PHP file that contains your provider code, e.g. `providers/sleep/up.php`
* You are also allowed to pass any other arbritray key/value pairs in. As the entire config array is passed to the plugin, you can retrieve any values that are applicable to Opsweekly as a whole, rather than per user (which are specified above)


### Generic configuration
There are a few other configuration options, which are documented in the example config file. Some highlights include:

* `$mysql_host`, `$mysql_user`, `$mysql_pass`: Global configuration for your MySQL database. Per team database configuration (e.g. the database name to use) goes inside the team config. 
* `$email_from_domain`: The domain name you use to send email, used for a "From" address when sending weekly reports. 
* `$search_results_per_page`: Allows control of the number of search results returned at once
* `$error_log_file`: Opsweekly prints some events, especially relating to on call fetching and Sleep tracking to a debug log file. This log file can be extremely useful at debugging provider issues. 
* `$dev_fqdn`, `$prod_fqdn`: To allow ease of development, Opsweekly will preg_replace the hostname given to it to another hostname (which then matches your team names in the `$teams` array). 
* `$irccat_hostname`, `$irccat_port`: If you use irccat and wish to use meeting reminders, and have them appear in IRC, you will need to configure the hostname and port your irccat instance runs at here. 

## A note on on-call classification and categorisation
One of Opsweekly's core goals is to try and assist with thinking deeply about on call rotations and the notifications received during them. 

A big part of this is requiring the on call engineer to categorise every alert they receive. If they receive 50+, this can be a daunting task. 

We spent a long time trying to come up with a good balance of concise options to choose from, that provided a sufficient amount of detail but at the same time didn't overwhelm the user. 

This is the list that we came up with:

---

### Two types: Action/No Action
The main thing we wanted to record was whether an alert was actionable, or not actionable (e.g. was there a genuine problem that affected service of the system that the user had to intervene to fix)

Therefore, the alert categorisations are broken down into those two categories. 

#### Action Taken Tags
The following are "Action Taken" tags, and their brief description:

* Service Issue (View Clean): The service was affected, and this alert correctly saw the issue and alerted on it
* Service Issue (timeperiod inappropriate, view clean): There was an issue but it could've alerted during another timeperiod, e.g. it could've waited until morning. 
* View Issue (network/site outage, service health quesitonable): The service could've been okay or broken, but the monitoring system did not have a clear view of the system to say either way, but an alert was fired. 
	* Most common use for this is a cascading failure/alert storm due to network outage with no parents setup
	

#### No Action Taken Tags
The following are "No Action Taken" tags, and their brief description:

* Time period inappropriate: This alert should go off during a different time period, e.g. during the waking hours
* Work ongoing, downtime expired: Known work is occuring on this system, but the downtime/alert supression expired
* Work ongoing, downtime not set: Known work is occuring on this system, but downtime/alert supression was not set at all (e.g. by accident)
* Threshold adjustment required: An alert fired due to a misconfiguration of thresholds. The threshold should be adjusted. 
* Check is faulty/requires modification: An alert fired because the check malfuctioned, needs to be redesigned or otherwise requires modification

---

More than once engineers have been somewhat baffled by these choices, and asked for another option; but actaully, in all those cases it ended up being covered by another choice. This is actually a good thing, as it forces everyone to think about the intitial cause of the alert, rather than things wrapped up around it. 

Hopefully by using Opsweekly you can become very aware of the kind of alerts your engineers are receiving, and then work to reduce noise and only wake/context switch your intelligent humans to do jobs when they're really required to do so. 


## Setting up meeting reminders
You can have opsweekly automatically email and IRC message you to remind you about meeting time, and provide the permalink to this week's meeting for convenience. 

To do so, simply set up a cron (or other method of triggering script e.g. manually) with the following: 
`php /path/to/opsweekly/send_meeting_reminder.php <your-configured-cname>`

e.g., using cron, weekly at 2pm:
`0 14 * * 3 php /var/www/opsweekly/send_meeting_reminder.php myweekly.yourdomain.com`


## Known issues/caveats/future goals
* As the name implies, Opsweekly is rather tied to the concept of a week. In theory the database stores time ranges, but the UI is all based on a week's worth of data
   * At some point I invisage dropping the concept of a fixed time period and instead having "providers" that pull the periods people were on call, prompting them to fill in the data. E.g. Pagerduty: You were on call from X to Y, please categorise your alerts for that period. 
* Whilst users can fill in their weekly report and on call report as the week continues, two people cannot edit the same on call report otherwise duplicate events may appear in the reports. This is due to the timestamp being part of the unique key for alerts. 
* ~~Garbage in, garbage out: There is no way to exclude items returned by the on call providers in the reports right now, outside of deleting anything from the database you're not happy with after compiling the on-call report. I want to add a "soft delete" so deliberately allow editing of reports for unforseen reasons (e.g. monitoring system goes wrong and didn't actually send any alerts but they were logged)~~

