# Homepage

Where else to start? The homepage provides an overview of the week chosen, with Meeting Notes first (if taken yet for that week), then the on call report rows, and finally the user reports

The date picker and 'add report' button are prominent here, as well as a dropdown that allows viewing just a single person's reports. 

Note in the header: 

* Another datepicker
* The ability to change the Timezone of the UI 
* Search bar
* Links to other pages

![Homepage](homepage.png?raw=true "Homepage")

# Data entry

So, how do you enter data into Opsweekly? The good news is, with a simple UI providing context hints for both Weekly and On-call reports, it should be as lightweight as possible to get data into the system. Both reports can be modified as often as user's wish during the week. 

## Add screen/Weekly reports

In this view, the on call report view has not been loaded. If the user is on call, they can click the "I was on call button". 

Otherwise, Opsweekly is prompting for just a "Weekly Update". You can see the weekly "hints" providers doing their job providing some context for the week on the right hand side. 

There are two buttons (off the bottom of the screenshot) that allow both saving and saving with email notification that sends a finalised report to the team. 

![Add Report screen](add_screen.png?raw=true "Add Report screen")


## On call classification/categorisation 

And now what happens if the user hits that big red "I was on call" button. 

The "on call" provider is run, and the screen is populated with all the alerts that the person received during this week. 

Every alert has it's own row here which clearly describes the date/time (in the user's timezone of choice), the host/service, the check output for context, and the state. The user then picks from 8 simple options that most relates to how they feel about the alert. 

There are check boxes for bulk classification, and a notes field for every entry which is captured and then searchable later. 

The report can be filled out during the on call rotation and updated as it progresses, rather than filling it out in one go. 


![On call classification](categorising_alerts.png?raw=true "On call alert classification")

# Reports

Now with data entered, it's time for the juicy bit: The weekly, yearly and personal reports. 


## Weekly Reports

Weekly reports provide a glimpse into the week gone by. The weekly report is also pulled into the Meeting view (as seen later) to provide context for discussion during weekly team meetings. 

![Weekly report 1](weekly_report_1.png?raw=true "Weekly Report 1")

Firstly, the distribution of alerts is displayed. This is useful if you're using Nagios as a source of data as it displays the ratio of alert types. 

Next, the tag or alert classification summary for the week. What kinds of alerts were received? No action taken vs action taken, etc. 

![Weekly report 2](weekly_report_2.png?raw=true "Weekly Report 2")

If the user has sleep tracking enabled, we're then given a breakdown of that person's sleep stats. For example, how many times were they woken up? What is their Mean Time to Sleep (MTTS)? Did they have to abandon sleep altogether? 

As the report continues, it then display:

* Top notifiying hosts - Which hosts were the nosiest? 
* Top notifying services - Which services were the nosiest? 
* Alert volume per day - Which day was the nosiest? 
* Notification time map - A heat map displaying the alerts received during the week over time. 


## Yearly Reports

This is where the data *really* starts to pay off. How has your on call experience improved over time? What does the last year look like for your on-call rotation?

![Yearly report 1](yearly_report_1.png?raw=true "Yearly Report 1")

The report starts off in a similar way to the Weekly Report, with the difference of being able to show data like average notifications per week. 

The tag status summary shows the years totals, but more detail are available further down. 

The yearly report also features more aggregated sleep data, not shown here. 

![Yearly report 2](yearly_report_2.png?raw=true "Yearly Report 2")

The notification time map shows a heat map of the alerts received over the year, with busy periods showing darker. Hovering over these shows the number of alerts received that day. 

Average alert volume per day gives insight into whether there are days that are, on average, busier for alerts than others. This data can then be used to consider why those days are busiest; e.g. it's likely on weekends if no one is working, those days are quieter, so how can we tackle noise during the week? In this illustration, Monday is the worst. Is that due to people making changes after the weekend? 

Alert types over time illustrates how the type of alerts (e.g Nagios states CRITICAL vs WARNING) have changed over time. Are you getting better at utilising the WARNING notification type? 

![Yearly report 3](yearly_report_3.png?raw=true "Yearly Report 3")

The Tag Summary over Time graphs are key to understanding if your on call experience is improving. The first graph simply illustrates the number of alerts per type per week. 

The second graph however, shows the ratio of Action Taken versus No Action Taken per week over time, so in theory the amount of red (No Action Taken) alerts should decrease as time goes on and you continue to asses noisy alerts using the data captured in Opsweekly. 

Average tag summary per Person demonstrates if particular people have worse or nosier on calls compared to others. 


## Personal Report/View Profile

Once a person has participated in many on calls, their own personal profile page will start to show more data about how their on call experience is compared to others. 

![Personal report/view profile](personal_profile_1.png?raw=true "Personal report/view profile")

The personal profile analyzes the on call data for the last year, comparing averages to the person's own experiences. 

For example, in this screenshot Sleep data has been captured, so the number of times woken per week and total sleep lost to alerts is displayed. This can be a great motivator to reduce on call noise. 

The user's personal Mean Time to Sleep (MTTS) is calculated, and compared to others, allowing a deeper understanding of how on call affects sleep. 

Regular notification stats are also available, especially useful for those without sleep data. In the example above, this person has both less notifications per week (displayed in green) and less "No Action Taken" alerts that others received. 

The personal report also features some visualisations of their personal on call data, such as a time map showing what times are busiest for them to receive alerts, and a graph of on call alert volume per week for their past year of on call rotations. 


# Meeting View



Opsweekly can also help faciliate weekly meetings in a way that helps the whole team. The "meeting view" is especially designed to help a meeting organiser run a meeting and easily leave notes that are then recorded and displayed on that week's homepage. 

![Meeting View](meeting_view_1.png?raw=true "Meeting View")

The meeting view comprises of a notes field, for the note taker, a permalink (which can be distributed manually or automatically via cron), the on call report and who was on call (including the "weekly" report for that week) and then everyone's Weekly updates. 


# Search

With all this valuable data locked away inside the Opsweekly database, it makes sense to have a way to get it back out again. 

The powerful search function has both a "fuzzy match" option which will show all results for that search in Weekly Reports, On call notifications and Meeting Notes. But you can also be more specific:

![Search Hints](search_hints.png?raw=true "Search Hints")


## On call searching

How often on your team does someone say "I think I got that alert before but I don't remember the context"? Well now you don't have to remember, and the important detail about the alert is captured. 

Here's an example:

![On call search](oncall_search.png?raw=true "On call search")

This service has had a troubled history. It went months without any context at all, but the lack of categorisation or notes probably tells a bigger story; no one knows what it does. Eventually someone notes that the check was *probably* noisy, so the threshold was adjusted up. Then, after it persisted, the check was made email only so it's usefulness could be investigated. 

The bottom of on the on call search page displays the tag summary and the graphed frequency of those search results:

![On call search graphs](oncall_search_graphs.png?raw=true "On call search graphs")




