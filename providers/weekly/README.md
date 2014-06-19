# Writing a weekly hint provider
A 'weekly' provider, or 'hints' is designed to prompt the user to remember what they did in the last week, so they can fill out their weekly report more accurately.
If you have a service that has data like this, e.g. commits or closed tickets, you can write a provider to pull this information into the Opsweekly UI. The data is displayed next to the text area on the Add Weekly Update page. 

Writing a provider is fairly simple; you must:

1. Place your file either in `providers/weekly/` or in another path in the PHP include path
2. Define it in the `$weekly_providers` providers array in `config.php`
3. Allow users to pass in any variables (e.g. server name, authentication details required for service) from `config.php`
4. Define a class and function(s) that Opsweekly will call when it needs to retrieve data. 

Weekly providers only have a global configuration which is passed into your class on instantiation. 
The username, and the requested time period is also passed in. 

You can refer to example.php for a working example of a weekly hints provider. This provider just outputs some text to display the data that was passed in to it. 

## Class definition
The class should have a unique name, e.g. `ExampleHints`. The name is not important; you (or you users) provide that as part of the definition of the provider in `config.php`

The `__construct` function is passed the following variables:

* `$username`: The simple text username of the person who is using Opsweekly and making this request
* `$config`: The array of configuration items that you have defined in your 'options' array in `$weekly_providers`
* `$events_from`: The unix timestamp of the beginning of the time period to show events for
* `$events_to`: The unix timestamp of the end of the time period to show events for

(Remember, users can go back to past weeks to fill in their Weekly reports, so respecting these from/to variables is important, if possible.)


## Functions required

### getOnCallNotifications()
Returns HTML to be displayed on the "add weekly update" page. 
 
#### Returns:
Returns HTML to be displayed on the "add weekly update" page. 
