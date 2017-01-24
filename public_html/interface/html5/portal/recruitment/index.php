<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright of T i m e T r e x Software Inc.
 * Fairness is a fork of "T i m e T r e x Workforce Management" Software.
 *
 * Fairness is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * Fairness is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 ********************************************************************************/

require_once('../../../../includes/global.inc.php');
forceNoCacheHeaders(); //Send headers to disable caching.

//Break out of any domain masking that may exist for security reasons.
Misc::checkValidDomain();

Misc::redirectMobileBrowser(); //Redirect mobile browsers automatically.
Misc::redirectUnSupportedBrowser(); //Redirect unsupported web browsers automatically.

//Handle HTTPAuthentication after all redirects may have finished.
$authentication = new Authentication();
if ($authentication->getHTTPAuthenticationUsername() == false) {
    $authentication->HTTPAuthenticationHeader();
} else {
    if ($authentication->loginHTTPAuthentication() == false) {
        $authentication->HTTPAuthenticationHeader();
    }
}
unset($authentication);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo APPLICATION_NAME . ' ' . TTi18n::getText('Workforce Management'); ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="Keywords"
          content="workforce management, time and attendance, payroll software, online timesheet software, open source payroll, online employee scheduling software, employee time clock software, online job costing software, workforce management, flexible scheduling solutions, easy scheduling solutions, track employee attendance, monitor employee attendance, employee time clock, employee scheduling, true real-time time sheets, accruals and time banks, payroll system, time management system"/>
    <meta name="Description"
          content="Workforce Management Software for tracking employee time and attendance, employee time clock software, employee scheduling software and payroll software all in a single package. Also calculate complex over time and premium time business policies and can identify labor costs attributed to branches and departments. Managers can now track and monitor their workforce easily."/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <script async src="../../framework/stacktrace.js"></script>
    <link rel="stylesheet" type="text/css"
          href="../../theme/default/css/application.css?v=<?php echo APPLICATION_BUILD ?>">
    <link rel="stylesheet" type="text/css"
          href="../../theme/default/css/jquery-ui/jquery-ui.custom.css?v=<?php echo APPLICATION_BUILD ?>">
    <link rel="stylesheet" type="text/css"
          href="../../theme/default/css/ui.jqgrid.css?v=<?php echo APPLICATION_BUILD ?>">
    <link rel="stylesheet" type="text/css"
          href="../../theme/default/css/views/login/LoginView.css?v=<?php echo APPLICATION_BUILD ?>">
    <link rel="stylesheet" type="text/css"
          href="../../theme/default/css/global/widgets/ribbon/RibbonView.css?v=<?php echo APPLICATION_BUILD ?>">
    <link rel="stylesheet" type="text/css"
          href="../../theme/default/css/global/widgets/search_panel/SearchPanel.css?v=<?php echo APPLICATION_BUILD ?>">
    <link rel="stylesheet" type="text/css"
          href="../../theme/default/css/views/attendance/timesheet/TimeSheetView.css?v=<?php echo APPLICATION_BUILD ?>">
    <script src="../../framework/jquery.min.js?v=<?php echo APPLICATION_BUILD ?>"></script>
    <script src="../../framework/jquery.form.min.js?v=<?php echo APPLICATION_BUILD ?>"></script>
    <script src="../../framework/jqueryui/js/jquery-ui.custom.min.js?v=<?php echo APPLICATION_BUILD ?>"></script>
    <script src="../../framework/jquery.i18n.js?v=<?php echo APPLICATION_BUILD ?>"></script>
    <script src="../../framework/backbone/underscore-min.js?v=<?php echo APPLICATION_BUILD ?>"></script>
    <script src="../../framework/backbone/backbone-min.js?v=<?php echo APPLICATION_BUILD ?>"></script>
    <script src="../../global/APIGlobal.js.php?v=<?php echo APPLICATION_BUILD ?>"></script>
    <script src="../../global/Global.js?v=<?php echo APPLICATION_BUILD ?>"></script>
    <script async src="../../framework/rightclickmenu/rightclickmenu.js?v=<?php echo APPLICATION_BUILD ?>"></script>
    <script async src="../../framework/rightclickmenu/jquery.ui.position.js?v=<?php echo APPLICATION_BUILD ?>"></script>
    <script async src="../../services/APIFactory.js?v=<?php echo APPLICATION_BUILD ?>"></script>
    <script src="../../global/LocalCacheData.js?v=<?php echo APPLICATION_BUILD ?>"></script>
    <script>
        Global.url_offset = '../../';

        Global.addCss("right_click_menu/rightclickmenu.css");
        Global.addCss("views/wizard/Wizard.css");
        Global.addCss("image_area_select/imgareaselect-default.css");
    </script>
</head>

<!--z-index

Alert: 100
DatePicker:100
Awesomebox: 100
Progressbar: 100
ribbon sub menu: 100
right click menu: 100
validation: 6000 set by plugin


Wizard: 50
camera shooter in wizard 51


EditView : 40
Bottom minimize tab: 39

Login view:10

 -->
<body class="login-bg" oncontextmenu="return true;">

<div class="need-hidden-element"><a href="https://github.com/aydancoskun/fairness">Workforce Management</a><a
            href="https://github.com/aydancoskun/fairness">Time and Attendance</a></div>

<div id="topContainer" class="top-container"></div>

<div id="contentContainer" class="content-container">
    <div class="loading-view">
        <!--[if (gt IE 8)|!(IE)]><!-->
        <div class="progress-bar-div">
            <progress class="progress-bar" max="100" value="10">
                <strong>Progress: 100% Complete.</strong>
            </progress>
            <span class="progress-label">Initializing...</span>
        </div>
        <!--<![endif]-->
    </div>
</div>

<div class="need-hidden-element"><a href="https://github.com/aydancoskun/fairness">Download Time and Attendance
        Software</a></div>

<div id="bottomContainer" class="bottom-container" ondragstart="return false;">
    <a id="copy_right_logo_link" class="copy-right-logo-link" target="_blank"><img id="copy_right_logo"
                                                                                   class="copy-right-logo"></a>
    <a id="copy_right_info" class="copy-right-info" target="_blank" style="display: none"></a>
    <span id="copy_right_info_1" class="copy-right-info" style="display: none">
			&nbsp;&nbsp;
        <?php echo COPYRIGHT_NOTICE; ?>
		</span>
</div>

<div id="overlay" class=""></div>
</body>

<iframe style="display: none" id="hideReportIFrame" name="hideReportIFrame"></iframe>

<script>
    //Hide elements that show hidden link for search friendly
    hideElements();

    //Don't not show loading bar if refresh
    if (Global.isSet(LocalCacheData.getLoginUser())) {
        $(".loading-view").hide();
    } else {
        setProgress()
    }

    function setProgress() {
        loading_bar_time = setInterval(function () {
            var progress_bar = $(".progress-bar")
            var c_value = progress_bar.attr("value");

            if (c_value < 90) {
                progress_bar.attr("value", c_value + 10);
            }
        }, 1000);
    }

    function cleanProgress() {
        if ($(".loading-view").is(":visible")) {

            var progress_bar = $(".progress-bar")
            progress_bar.attr("value", 100);
            clearInterval(loading_bar_time);

            loading_bar_time = setInterval(function () {
                $(".progress-bar-div").hide();
                clearInterval(loading_bar_time);
            }, 50);
        }
    }

    function hideElements() {
        var elements = document.getElementsByClassName('need-hidden-element');

        for (var i = 0; i < elements.length; i++) {
            elements[i].style.display = 'none';
        }
    }
</script>

<script src="../../framework/require.js" data-main="main.js?v=<?php echo APPLICATION_BUILD ?>"></script>
</html>
<?php
Debug::writeToLog();
?>
