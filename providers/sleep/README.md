# Writing a sleep provider
Opsweekly has the ability to use providers to look up the sleep status of a person during their on call notifications. You can write a provider that interfaces with an external service or data to pull in this information for sleep devices that you use. 

Writing a provider is fairly simple; you must:

1. Place your file either in `providers/sleep/` or in another path in the PHP include path
2. Define it in the `$sleep_providers` providers array in `config.php`
3. Allow users to pass in any variables (e.g. server name, authentication details required for service) from `config.php`
4. Define function(s) that Opsweekly will call when it needs to retrieve data. 

Sleep providers have the abillity to be passed any number of amounts of data from the Opsweekly UI itself. The config in `$sleep_providers` has an entry "options" which is used to generate HTML fields on the "Edit Profile" page of Opsweekly, allowing each user to have their own friendly interface for configuring their sleep device. This data is saved from that form to the database, and then passed into your provider as an array when the functions are called. 

You can also pass global options in from `$sleep_providers`; the entire key => value array in the config for that provider is passed into it when called, so you can define other variables required (e.g. URLs, authentication information that is specific to your provider)

You can refer to example.php for a working example of a sleep provider. This provider always returns that the user was awake, regardless of the timeframe requested, user requested, or provider/team options passed to it. 

## Functions required

### getSleepDetailAtTimestamp()
Returns the sleep state for a given unix timestamp and user

#### Parameters:
*   `$timestamp` - The unix timestamp of the notification that Opsweekly wishes to know sleep data about
*   `$user_options` - The array of options specified in the Opsweekly UI by the user during sleep configuration (see above)
*   `$plugin_options` - All values from `config.php` in `$sleep_provider` for this providers key - That is, global options.
 
 
#### Returns:
Sleep detail for the timestamp as an array.

The array must contain the following keys: 

 * sleep_state
 	* -1 for no data
 	* 0 for awake
 	* 1 for asleep
 * mtts: The time in seconds, up to 7200 seconds, that the next asleep period occured after `$timestamp` or -1 for unknown/no sleep.
 
 * sleep_level: As per the standard measure of 4 levels of sleep, if available:
	 * 1: NREM Stage 1
	 * 2: NREM Stage 2
	 * 3: NREM Stage 3
	 * 4: REM
 * confidence: The confidence the plugin has that the data it has is accurate
 	* 0: No confidence
 	* 100: Very confident