# Writing an on call provider
You may write an on call provider to cater for any service or data you want to appear as notifications in Opsweekly.
Writing a provider is fairly simple; you must:

1. Place your file either in `providers/oncall/` or in another path in the PHP include path
2. Define it in the `$oncall_providers` providers array in `config.php`
3. Allow users to pass in any variables (e.g. server name, authentication details required for service) from `config.php`
4. Define function(s) that Opsweekly will call when it needs to retrieve data. 

There are two types of configuration passed to your on call provider; global config (which is defined in the `options` part of the array in `$oncall_providers`) and team specific configuration (which is defined in the `provider_options` part of the on call section of the `$teams` array).

You can refer to example.php for a working example of an on-call provider. This provider just returns one example notification, regardless of the timeframe requested, user requested, or provider/team options passed to it. 

## Functions required

### getOnCallNotifications()
Returns the notifications for a given time period and parameters

#### Parameters:
*   `$on_call_name` - The username of the user compiling this report
*   `$provider_global_config` - All options from `config.php` in `$oncall_providers` - That is, global options.
*   `$provider_team_config` - All options from `config.php` in `$teams` - That is, specific team configuration options
*   `$start` - The unix timestamp of when to start looking for notifications
*   `$end` - The unix timestamp of when to stop looking for notifications
 
 
#### Returns:
0 or more notifications as an array

* Each notification should have the following keys:
	* `time`: Unix timestamp of when the alert was sent to the user
	*  `hostname`: Ideally contains the hostname of the problem. Must be populated but feel free to make bogus if not applicable.
	*  `service`: Contains the service name or a description of the problem. Must be populated. Perhaps use "Host Check" for host alerts.
	*  `output`: The plugin output, e.g. from Nagios, describing the issue so the user can reference easily/remember issue
	*  `state`: The level of the problem. One of: CRITICAL, WARNING, UNKNOWN, DOWN