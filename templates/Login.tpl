<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>{$APPLICATION_NAME} {t}Time and Attendance - Secure Login{/t}</title>
	<link rel="stylesheet" type="text/css" href="{$BASE_URL}global.css.php">

<style type="text/css">
{literal}
#popUpDiv {
    background-color: #e5e5e5;
    color: #FFF;
    height: 30px;
	padding-top:5px;
    position: absolute;
	left: 35%;
	top: -50px;
    width: 30%;
	text-align: center;
	border-style: solid;
	border-width: 2px;
	border-color: #779bbe;
    transition: top 1.5s;
    -moz-transition: top 1.5s;
    -webkit-transition: top 1.5s;
    -o-transition: top 1.5s;
}
#popUpDiv.visible {
	top: 5px;
}
{/literal}
</style>

<script language=JavaScript>
{literal}

function bookmarkSite( title, url ) {
	if ( window.sidebar ) { // FireFox
		window.sidebar.addPanel(title, url, "");
	} else if ( window.opera && window.print ) { // Opera
		var elem = document.createElement('a');
		elem.setAttribute('href',url);
		elem.setAttribute('title',title);
		elem.setAttribute('rel','sidebar');
		elem.click();
	}  else if ( document.all ) { // IE
		window.external.AddFavorite(url, title);
	}
}
{/literal}
</script>
</head>

<body onload="document.login.user_name.focus(); {if !isset($config_vars.branding)}document.getElementById('popUpDiv').className += ' visible';{/if}">

<div id="container">

<div id="rowHeaderLogin"><a href="http://{$ORGANIZATION_URL}"><img src="{$BASE_URL}/send_file.php?object_type=primary_company_logo" style="width:auto; height:42px;" alt="Time And Attendance"></a></div>

<div id="rowContentLogin">
  <form method="post" name="login" action="{$smarty.server.SCRIPT_NAME}">
  <div id="contentBox">

    <div class="textTitle2">
    	<img src="{$IMAGES_URL}lock.gif" width="28" height="26" alt="Secure Login" class="imgLock">
    	<span style="float: right">
				{if isset($config_vars.urls.facebook)}
					<a href="$config_vars.urls.facebook" target="_blank">
  	  			<img src="{$IMAGES_URL}/facebook_button.jpg" border="0">
	    		</a>
				{/if}
    		&nbsp;
				{if isset($config_vars.urls.twitter)}
	    		<a href="$config_vars.urls.twitter"  target="_blank">
  	  			<img src="{$IMAGES_URL}/twitter_button.jpg" border="0">
    			</a>
				{/if}
    	</span>
    	<br>
    </div>

    <div id="contentBoxOne"></div>

    <div id="contentBoxTwo">

		{if !$validator->isValid()}
			{include file="form_errors.tpl" object="validator"}
		{/if}

		{if $password_reset == 1}
			<div id="rowWarning" valign="center">
					<br>
					<b>{t}Your password has been changed successfully, you may now login.{/t}</b>
					<br>&nbsp;
			</div>
		{/if}

		<div class="row">
			<div class="cellLeft">{t}User Name{/t}</div><div class="cellRight"><input type="text" name="user_name" value="{$user_name}" size="40"></div>
		</div>
		<div class="row">
			<div class="cellLeft">{t}Password{/t}</div><div class="cellRight"><input type="password" name="password" value="{$password}" size="40" autocomplete="off"></div>
		</div>
		<div class="row">
			<div class="cellLeft">{t}Language{/t}</div>
			<div class="cellRight">
				<select name="language">
					{html_options options=$language_options selected=$language}
				</select>
			</div>
		</div>
    </div>

    <div id="contentBoxThree"></div>

	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td width="33%" align="left"><a href="ForgotPassword.php">{t}Forgot Your Password?{/t}</a></td>
			<td width="33%" align="center"><a href="quick_punch/">{t}Quick Punch{/t}</a></td>
			<td width="33%" align="right"><a href="javascript:bookmarkSite( '{$APPLICATION_NAME} - {t}Secure Login{/t}', location.href )">{t}Bookmark This Page!{/t}</a></td>
		</tr>
	</table>
	<br>
    <div id="contentBoxFour">
		<input type="submit" class="btnSubmit" name="action:submit" value="{t}Submit{/t}">
	</div>
  </div>
  </form>
</div>

{include file="footer.tpl"}
