<?php

require_once('includes/global.inc.php');

header('HTTP/1.1 301 Moved Permanently');
Redirect::Page( Environment::GetBaseURL().'html5/' );
?>