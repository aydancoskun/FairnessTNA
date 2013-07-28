Fairness Time & Attendance
==========================

Fairness is a Fork of the "TimeTrex Workforce Management" software by Mike Benoit which is kindly licensed under the
GNU AFFERO GENERAL PUBLIC LICENSE VERSION 3. See LICENSE file for the full license text.

Acknowledgement and thank you!
==============================

First of all we want to thank Timetrex and Mike Benoit for their work and for licensing their work as open source.


A look into the future:
-----------------------
Timetrex is fantastic software and we are exited forking it! We are looking forward to making it a truely free platform without gotchas but truely open source. We are looking forward to including many more countries tax codes and calcultion tables and to add a JQuery and mobile friendly user interface. Our aim is to keep this fork in sync with Timetrex as much as that is feasable and feeding our changes back to the community.

We are not planning to supporting Windows as a platform. Not for any other reason that we don't use windows. If someone wants to step up and be the windows part maintainer, speak up.


Little word on licensing:
-------------------------
We had to take a good look at the way the license for Timetrex is implemented and what it actually means because it has a few gotchas. This is probably the reason no one else has ever forked TimeTrex. We will list out what we found. Just to be clear upfront: This is in no way a slight against Timetrex and especially Mike, in any way. We believe that part of the licensing choices that were made are either a clear oversight, misunderstanding or have their roots in the history of the Timetrex licensing model.

Timetrex licensed their software as Open Source using the GNU AFFERO GENERAL PUBLIC LICENSE Version 3.

However reading through the source code there is all sorts of additional restrictions imposed.

Each source file states the following:


	TimeTrex is a Payroll and Time Management program developed by TimeTrex Software Inc.
	Copyright (C) 2003 - 2013 TimeTrex Software Inc.

	This program is free software; you can redistribute it and/or modify it under
	the terms of the GNU Affero General Public License version 3 as published by
	the Free Software Foundation with the addition of the following permission
	added to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED
	WORK IN WHICH THE COPYRIGHT IS OWNED BY TIMETREX, TIMETREX DISCLAIMS THE
	WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.

	This program is distributed in the hope that it will be useful, but WITHOUT
	ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
	FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
	details.

	You should have received a copy of the GNU Affero General Public License along
	with this program; if not, see http://www.gnu.org/licenses or write to the Free
	Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
	02110-1301 USA.

	You can contact TimeTrex headquarters at Unit 22 - 2475 Dobbin Rd. Suite
	292 Westbank, BC V4T 2E9, Canada or at email address info@timetrex.com.

	The interactive user interfaces in modified source and object code versions
	of this program must display Appropriate Legal Notices, as required under
	Section 5 of the GNU Affero General Public License version 3.

	In accordance with Section 7(b) of the GNU Affero General Public License
	version 3, these Appropriate Legal Notices must retain the display of the
	"Powered by TimeTrex" logo. If the display of the logo is not reasonably
	feasible for technical reasons, the Appropriate Legal Notices must display
	the words "Powered by TimeTrex".

We will try to comment on **two points**.

###Point 1
In paragraph 2 Timetrex is saying is that they modified their "Disclaimer of Warranty"

Section 7A states:

	"a) Disclaiming warranty or limiting liability differently from the terms of sections 15 and 16 of this License;"

Section 15 states:

	15. Disclaimer of Warranty.
	THERE IS NO WARRANTY FOR THE PROGRAM, TO THE EXTENT PERMITTED BY
	APPLICABLE LAW. EXCEPT WHEN OTHERWISE STATED IN WRITING THE COPYRIGHT
	HOLDERS AND/OR OTHER PARTIES PROVIDE THE PROGRAM "AS IS" WITHOUT WARRANTY
	OF ANY KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO,
	THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
	PURPOSE. THE ENTIRE RISK AS TO THE QUALITY AND PERFORMANCE OF THE PROGRAM
	IS WITH YOU. SHOULD THE PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF
	ALL NECESSARY SERVICING, REPAIR OR CORRECTION.

The changed it to:

	FOR ANY PART OF THE COVERED WORK IN WHICH THE COPYRIGHT IS OWNED BY TIMETREX, TIMETREX DISCLAIMS THE WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.

It appears that Timetrex is trying to cover themselves in case of there is any infringements against the copyright of third parties (i.e. libaries they are using). We speculate that this is possibly because they are using a commercial javascript menu which is licensed to them with a serial code.

Our aim is to replace this library with a free JQuery menu. But until then if you want to use this software, get your own license for that menu. Or better even, just help us fix it.

###Point 2

In he last two paragraphs it sounds like that what Timetrex is tying to do is making sure that any future fork or derivitive work will diplay the "Powered by TimeTrex" Logo or say "Powered by TimeTrex." on every page of the user interface.

However section 7 of the license, and we urge you to read it for yourself, states that there are 2 types of "Additional Terms".

######Type 1:
There are "Additional permissions" which you can remove. Quote: "***When you convey a copy of a covered work, you may at your option remove any additional permissions from that copy, or from any part of it.***" (A permission is something extra you are allowed to do)

######Type 2:
Then there are "further restrictions". Quote: "***All other non-permissive additional terms are considered "further restrictions" within the meaning of section 10. If the Program as you received it, or any part of it, contains a notice stating that it is governed by this License along with a term that is a further restriction, you may remove that term.***"

From our standpoint we are forking this software and its now called "Fairness". Obviously putting "Powered by Timetrex" onto every page is nonsense for an open source product. But there is the point of attribution.

There are two quotes relevant to this:

**Article 0**

	An interactive user interface displays "Appropriate Legal Notices"
	to the extent that it includes a convenient and prominently visible
	feature that (1) displays an appropriate copyright notice, and (2)
	tells the user that there is no warranty for the work (except to the
	extent that warranties are provided), that licensees may convey the
	work under this License, and how to view a copy of this License. If
	the interface presents a list of user commands or options, such as a
	menu, a prominent item in the list meets this criterion.

**Article 7b):**

	b) Requiring preservation of specified reasonable legal notices or
	author attributions in that material or in the Appropriate Legal
	Notices displayed by works containing it;

Article 0 is very clear as to what is meant by "Appropriate Legal Notices". The "About" menu is quite sufficient. Therefore we are giving full attribution to Timetrex in the about menu because we are thankful for what they have created.

###Another Point

There is an encrypted file called TTlicense.php. It states:

	<?php /* Reverse engineering of this file is strictly prohibited. File protected by copyright law and provided under license. */

This is then followed by a bunch of eval() statements. Basically the code is well obfuscated. Obviously Timetrex does not want you to look at it. We don't understand why that would exist in an open source product. The whole point of open source is that the software is free of restrictions. We think that this exists because of historical reasons. Since we can't be bothered to unobuscate it. We are planning to just delete the file and any reference to it in other files and make sure things just keep on working. The reference in the license is here:

Section 3 of the GNU AFFERO GENERAL PUBLIC LICENSE Version 3 states:

	3. Protecting Users' Legal Rights From Anti-Circumvention Law.

	No covered work shall be deemed part of an effective technological
	measure under any applicable law fulfilling obligations under article
	11 of the WIPO copyright treaty adopted on 20 December 1996, or
	similar laws prohibiting or restricting circumvention of such
	measures.

	When you convey a covered work, you waive any legal power to forbid
	circumvention of technological measures to the extent such circumvention
	is effected by exercising rights under this License with respect to
	the covered work, and you disclaim any intention to limit operation or
	modification of the work as a means of enforcing, against the work's
	users, your or third parties' legal rights to forbid circumvention of
	technological measures.


###A last point
The Timetrex software phones home without telling the user. It does that a lot. It uses several ways to do so. A soap interface, plain "fopen" calls during every step of the install process. (I understand why, but just let people know please or ask if its OK.) and Google Analytics for every page load including host names and "licensing information", number of users etc.

We are planning of ripping all that out but we are not there yet by a long shot.

List of removals:
----------------
For the record we are keeping a list of files that we found that have other "further restrictions" imposed which we removed:

**About.tpl**

	{* REMOVING OR CHANGING THIS LOGO IS IN STRICT VIOLATION OF THE LICENSE AGREEMENT *}
	<a href="http://{$ORGANIZATION_URL}"><img src="{$BASE_URL}/send_file.php?object_type=copyright" 	alt="Time and Attendance"></a>

**CompanyFactory.class.php**

**PunchFactory.class.php**

**ScheduleFactory.class.php**

**UserContactFactory.class.php**

**UserFactory.class.php**

**UserList.class.php**

	$obj_class = "\124\124\114\x69\x63\x65\x6e\x73\x65"; $obj_function =
	"\166\x61\154\x69\144\x61\164\145\114\x69\x63\145\x6e\x73\x65"; $obj_error_msg_function =
	"\x67\x65\x74\x46\x75\154\154\105\162\x72\x6f\x72\115\x65\x73\163\141\x67\x65"; @$obj = new
	$obj_class; $retval = $obj->{$obj_function}(); if ( $retval !== TRUE ) { $this->Validator-
	>isTrue( 'lic_obj', FALSE, $obj->{$obj_error_msg_function}($retval) ); }

We found this tabbed over by more than 80 characters (so that you won't see this code when you scroll though the file unless you have linewrap turned on) all on one line obfuscated code in the six files above.
Again, I think we just rip it out, making sure we don't loose functionality.

**global.inc.php**

	// **REMOVING OR CHANGING THIS APPLICATION NAME AND ORGANIZATION URL IS IN STRICT VIOLATION OF THE LICENSE AND COPYRIGHT AGREEMENT**
	( isset($config_vars['branding']['application_name']) AND $config_vars['branding']['application_name'] != '' ) ? define('APPLICATION_NAME', $config_vars['branding']['application_name']) : define('APPLICATION_NAME', (PRODUCTION == FALSE) ? 'TimeTrex-Debug' : 'TimeTrex');
	( isset($config_vars['branding']['organization_name']) AND $config_vars['branding']['organization_name'] != '' ) ? define('ORGANIZATION_NAME', $config_vars['branding']['organization_name']) : define('ORGANIZATION_NAME', 'TimeTrex');
	( isset($config_vars['branding']['organization_url']) AND $config_vars['branding']['organization_url'] != '' ) ? define('ORGANIZATION_URL', $config_vars['branding']['organization_url']) : define('ORGANIZATION_URL', 'www.TimeTrex.com');

**send_file.php**

		//
		//REMOVING OR CHANGING THIS LOGO IS IN STRICT VIOLATION OF THE LICENSE AGREEMENT AND COPYRIGHT LAWS.
		//
		if ( getTTProductEdition() > 10 ) { $file_name = Environment::getImagesPath().'/powered_by.jpg';Debug::Text('File Name: '. $file_name, __FILE__, __LINE__, __METHOD__,10);if ( $file_name != '' AND file_exists($file_name) ) { $params['file'] = $file_name;$params['contentdisposition'] = 'attachment; filename=pro_copyright.jpg';$params['data'] = file_get_contents($file_name);$params['cache'] = TRUE;}} else {$params['contentdisposition'] = 'attachment; filename=copyright.jpg';$params['data'] = base64_decode('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');$params['cache'] = TRUE;}

**milonic_menu_code.js**

	/*

		Milonic DHTML Menu - JavaScript Website Navigation System.
		Version 5.813 - Built: Saturday December 13 2008 - 11:42
		Copyright 2008 (c) Milonic Solutions Limited. All Rights Reserved.
		This is a commercial software product, please visit http://www.milonic.com/ for more information.
		See http://www.milonic.com/license.php for Commercial License Agreement
		All Copyright statements must always remain in place in all files at all times

		*******  PLEASE NOTE: THIS IS NOT FREE SOFTWARE, IT MUST BE LICENSED FOR ALL USE  ******* 

		License Details:
		Number: 197517
		URL: www.timetrex.com
		Type: Professional
		Dated: Sunday January 11 2009

	*/

I contacted Milonic to see if we can get a free license for the Open Source Project or what they suggest we do with this. This is really not compatible with the AGPL and will need to be addressed fast.

