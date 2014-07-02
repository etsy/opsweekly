Using logstash for oncall reporting.
------------------------------------

- Ship your nagios logs into logstash, either via syslog or logstash forwarder
- Apply the 'nagios filters'

    filter {
      if [file] =~ /\/var\/log\/nagios\/nagios.log$/ {
        grok {
          # use distributed nagios patterns
          patterns_dir => "/opt/logstash/patterns"
          match => { "message" => "%{NAGIOSLOGLINE}" }
          remove_field => [ "message" ]
        }

Nagios log entries should get the nagios_* tags applied in logstash.

Enable th elogstash provider, and add the required config.  

Setup a notification-user-map if you use different contactnames in nagios than in opsweekly.

Goto add, click 'Im was on call'

