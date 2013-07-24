Acknowledgement and thank you!
==============================

First of all we want to thank Timetrex and Mike Benoit for their work and for licensing their work as open source.


A look into the future:
-----------------------
Timetrex is fantastic software and we are exited forking it! We are looking forward to making it a truely free platform without gotchas but truely open source. We are looking forward to including many more countries tax codes and calcultion tables and to add a JQuery and mobile friendly user interface. Our aim is to keep this fork in sync with Timetrex as much as that is feasable and we are obviously making all our changes and what we believe to be improvements available to the Timetrex developers to hopefully be able to improve this great product.


Little word on licensing:
-------------------------
What we are doing now is taking a good look at the way the license for Timetrex is implemented and what it actually means. This is in no way meant as a slight against Timetrex and especially Mike, in any way. We believe that part of the licensing choices that were made with Timetrex are either a clear oversight, misunderstanding or have their roots in the history of the Timetrex licensing model.

Timetrex licensed their software as Open Source using the GNU AFFERO GENERAL PUBLIC LICENSE Version 3.

However reading through the source code there is all sorts of additional restrictions imposed on
the developer / user quite in contrast to the GNU license.

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
  
**It appears that Timetrex is trying to cover themselves in case of there is any infringements against the copyright of third parties (i.e. libaries they are using). We speculate that this is possibly because they are using a commercial javascript menu which is licensed to them with a serial code.**

**Our aim is to replace this library with a free JQuery menu. But until then if you want to be sure get your own license if you use this software.** 

###Point 2

In he last two paragraphs we muse that what Timetrex is tying to do is making sure that any future fork or derivitive work will diplay the "Powered by TimeTrex" Logo or say "Powered by TimeTrex." on every page of the user interface.
				
However section 7 of the license, and we urge you to read it for yourself, states that there are 2 types of "Additional Terms".
		
######Type 1: 
There are "Additional permissions" which you can remove. Quote: "***When you convey a copy of a covered work, you may at your option remove any additional permissions from that copy, or from any part of it.***"
		
######Type 2: 
Then there are "further restrictions" which you can also just remove. Quote: "***All other non-permissive additional terms are considered "further restrictions" within the meaning of section 10. If the Program as you received it, or any part of it, contains a notice stating that it is governed by this License along with a term that is a further restriction, you may remove that term.***"

There is another aspect from which one can look at this (There more than one way to skin a cat): Obviously we are forking this software and its now called "Fairness". Obviously putting "Powered by Timetrex" onto every page is nonsense for an open source product. But there is the point of attribution.

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
	
Article 0 is very clear as to what is meant by "Appropriate Legal Notices". The "About" menu is quite sufficient. So we could just put a "Powered by TimeTrex" into the "About" menu and that would be quite sufficient. But we will not be doing this because it violates the "further restrictions" clause and is not in the spirit of open source. We will however give full attribution to Timetrex in the about menu because we are thankful for what they have created.

###Point 3

There is an encrypted file called TTlicense.php. It states:

	<?php /* Reverse engineering of this file is strictly prohibited. File protected by copyright law and provided under license. */

This is then followed by a bunch of eval statements. Basically the code is well obfuscated. Obviously Timetrex does not want you to look at it.

Now here is the problem. This **directly** violates the license:

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

What are we doing about it?
We can't be bothered to unobuscate it. We are planning to just delete the file and any reference to it in other files and make sure things just keep on working.

###Point 4
A last point. The Timetrex software phones home without telling the user. It does that a lot. It uses several ways to do so. A soap interface, plain "fopen" calls during every step of the install process. (I understand why, but just let people know please or ask if its OK.) and Google Analytics for every page load including host names and "licensing information", number of users etc.

We are planning of ripping all that out but we are not there yet by a long shot.

List of removals:
----------------
For the record we are keeping a list of files that we found that have other "further restrictions" imposed which we removed:
**About.tpl**

	{* REMOVING OR CHANGING THIS LOGO IS IN STRICT VIOLATION OF THE LICENSE AGREEMENT *}
	<a href="http://{$ORGANIZATION_URL}"><img src="{$BASE_URL}/send_file.php?object_type=copyright" 	alt="Time and Attendance"></a>


