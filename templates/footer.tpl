<!-- Begin Footer -->
<a name="bottom">
<br>
<div id="rowFooter">
	<div class="textFooter">
		<table border="0" width="100%">
			<tr>
				<td width="10%" align="left">{if ( stristr( $smarty.server.SCRIPT_NAME, 'login') OR stristr( $smarty.server.SCRIPT_NAME, 'forgotpassword') ) AND $config_vars.other.footer_left_html != ''}{$config_vars.other.footer_left_html}{else}&nbsp;{/if}</td>
				<td align="center">
					{t}Server response time:{/t} {php}echo sprintf('%01.3f',microtime(true)-$_SERVER['REQUEST_TIME_FLOAT']);{/php} {t}seconds.{/t} {t}All Rights Reserved.{/t}
						{if stristr( $smarty.server.SCRIPT_NAME, 'login') == FALSE}
						<br>
							{if isset($config_vars.urls.facebook)}
								<a href="$config_vars.urls.facebook" target="_blank">
									<img src="{$IMAGES_URL}/facebook_button.jpg" border="0">
								</a>
							{/if}
							{if isset($config_vars.urls.twitter)}
								<a href="$config_vars.urls.twitter" target="_blank">
									<img src="{$IMAGES_URL}/twitter_button.jpg" border="0">
								</a>
							{/if}
						{/if}
						<br>
				</td>
				<td width="10%" align="right">{if ( stristr( $smarty.server.SCRIPT_NAME, 'login') OR stristr( $smarty.server.SCRIPT_NAME, 'forgotpassword') ) AND $config_vars.other.footer_right_html != ''}{$config_vars.other.footer_right_html}{else}&nbsp;{/if}</td>
			</tr>
		</table>
	</div>
</div>

<div>
	{php}
		Debug::writeToLog();
		Debug::Display();
		if (Debug::getEnableDisplay() == TRUE AND Debug::getVerbosity() >= 10) {
			{/php}
			{$profiler->stopTimer('Main')}
			{$profiler->printTimers(TRUE)}
			{php}
		}
	{/php}
</div>
</div>
{if $config_vars.debug.production == 1 AND $config_vars.other.google_analytics_acct != ""}
<script src="http{if $smarty.server.HTTPS == TRUE}s://ssl{else}://www{/if}.google-analytics.com/urchin.js" type="text/javascript"></script>
<script type="text/javascript">
_uacct="$config_vars.other.google_analytics_acct";
__utmSetVar('Company: {if is_object($primary_company)}{$primary_company->getName()|escape}{else}N/A{/if}');
__utmSetVar('Host: {$smarty.server.HTTP_HOST}');
__utmSetVar('Version: {$APPLICATION_VERSION}');
urchinTracker();
</script>
<img src="{$IMAGES_URL}spacer.gif">{/if}
</body>
</html>
