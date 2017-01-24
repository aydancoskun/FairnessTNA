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
    </body>
    </html>