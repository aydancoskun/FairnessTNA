<!-- Begin Footer -->
<a name="bottom">
    <br>
    <div id="rowFooter">
        <div class="textFooter">
            <table border="0" width="100%">
                <tr>
                    <td width="10%"
                        align="left">{if ( stristr( $smarty.server.SCRIPT_NAME, 'login') OR stristr( $smarty.server.SCRIPT_NAME, 'forgotpassword') ) AND $config_vars.other.footer_left_html != ''}{$config_vars.other.footer_left_html}{else}&nbsp;{/if}</td>
                    <td align="center">
                        {t}Server response time:{/t} {php}echo sprintf('%01.3f',microtime(true)-$_SERVER['REQUEST_TIME_FLOAT']);{/php} {t}seconds.{/t}
                        <br>
                        Copyright &copy; {$smarty.now|date_format:"%Y"} <a href="http://{$ORGANIZATION_URL}"
                                                                           class="footerLink">{$ORGANIZATION_NAME}</a>.
                        <br>
                        The Program is provided AS IS, without warranty. Licensed under <a
                                href="http://www.fsf.org/licensing/licenses/agpl-3.0.html">AGPLv3.</a> This program is
                        free software; you can redistribute it and/or modify it under the terms of the <a
                                href="http://www.fsf.org/licensing/licenses/agpl-3.0.html">GNU Affero General Public
                            License version 3</a> as published by the Free Software Foundation.
                        <br><br><a href="http://{$ORGANIZATION_URL}"><img
                                    src="{$BASE_URL}/send_file.php?object_type=copyright" alt="Time and Attendance"></a>
                    </td>
                    <td width="10%"
                        align="right">{if ( stristr( $smarty.server.SCRIPT_NAME, 'login') OR stristr( $smarty.server.SCRIPT_NAME, 'forgotpassword') ) AND $config_vars.other.footer_right_html != ''}{$config_vars.other.footer_right_html}{else}&nbsp;{/if}</td>
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
    </body>
    </html>