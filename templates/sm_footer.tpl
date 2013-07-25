<!-- Begin Footer -->
<a name="bottom">
<br>
<div>
	{php}
		Debug::writeToLog();
		Debug::Display();
		if (Debug::getEnableDisplay() == TRUE AND Debug::getVerbosity() >= 10) {
			{/php}
			{$profiler->printTimers(TRUE)}
			{php}
		}
	{/php}
</div>

</div>
{if $config_vars.debug.production == 1 AND $config_vars.other.google_analytics_acct <> ""}
<script src="https://ssl.google-analytics.com/urchin.js" type="text/javascript"></script>
<script type="text/javascript">
_uacct="$config_vars.other.google_analytics_acct";
__utmSetVar('Company: {if is_object($current_company)}{$current_company->getName()|escape}{else}N/A{/if}');
__utmSetVar('Host: {$smarty.server.HTTP_HOST}');
__utmSetVar('Version: {$system_settings.system_version}');
urchinTracker();
</script><img src="{$IMAGES_URL}spacer.gif">{/if}
</body>
</html>
