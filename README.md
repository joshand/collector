# collector

# Description
The collector works alongside the Dashboard application. It checks in with the Dashboard and gets instructions, which it then executes against LAN local devices.

# Installation
## If you are doing a test on a local network, you can install the collector co-resident with the Dashboard.
sudo yum install net-tools<br>
<br>
sudo git clone https://github.com/joshand/collector /var/www/html/collector<br>
cd /var/www/html/collector<br>
<br>
sudo vi config.inc<br>
<br>
Change &lt;PUBLIC_FQDN_OR_IP_ADDRESS&gt; to be the public dns name or IP address of the server. If you are demoing on a private network, you can use an internal IP here as well.<br>
<br>
sudo ./genid.sh<br>
sudo cat /tmp/collector.id<br>
> This will return a &lt;COLLECTOR_ID&gt; that you will need below

Now, you must authorize the collector to speak with the dashboard. This process requires credentials from a valid Dashboard account.<br>
curl -H "Content-Type: application/json" -X POST -d '{"credentials":{"username":"&lt;DASHBOARD_EMAIL_ADDRESS&gt;","password":"&lt;DASHBOARD_PASSWORD&gt;"}}' http://127.0.0.1/dashboard/api/v0/session/<br>
> This should return something that looks like the following:

> {"dashboard":{"response":"success","detail":"Authentication Successful","token":"&lt;DASHBOARD_TOKEN&gt;","userpkid":"&lt;USER_GUID&gt;","authentication":{"response":"unknown","detail":"Login Attempt"}}}

curl -H "Content-Type: application/json" -X POST -d '{"authentication":{"token":"&lt;DASHBOARD_TOKEN&gt;"},"collector":{"description":"My Collector","authorized":"true"}}' [http://127.0.0.1/dashboard/api/v0/collector/&lt;COLLECTOR_ID&gt;](http://127.0.0.1/dashboard/api/v0/collector/)<br>
> This should return something that starts like the following:

> {"dashboard":{"response":"success"

php checkin.php<br>
> This should return something that looks like the following:

> {"dashboard":{"response":"success","action_count":0,"dbtime":"2016-03-27 19:18:49","authentication":{"response":"authorized","detail":"Collector Authentication Passed","collectorpkid":"&lt;COLLECTOR_GUID&gt;"}}}

Ctrl-C to kill the script, and re-run like this:<br>
nohup php checkin.php &<br>
