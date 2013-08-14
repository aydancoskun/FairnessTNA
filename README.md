Fairness Time & Attendance
==========================

Fairness is Time and Attendance software derived from sources freely provided to the public by a Canadian software company. It is licensed under the AGPL as is its upstream counterpart. See LICENSE file for the full license text and COPYRIGHT file for attribution and notes.


To install fairness follow INSTALL.txt. It should run on any linux as well as other proprietary OSs as long as the system requirements are met.


So far we streamlined some of the original code base and replaced the menu with an open source version.


The upstream version comes with 2 interfaces: A traditional html version and a version written in Flex. The Flex version is decidedly more pretty and functional than the html version. However, the flex variant is compiled and the sources are not provided. Our efforts are currently concentrated on making the html version a mirror of the Flex version in layout and functionality. While we are doing this we are reworking the menus, javascript and css to be responsive and mobile friendly.


Once we achieved this makeover our effort will turn into the direction of enabling functionality. Some of the advanced functionality depends on proprietary (non AGPL) modules which are not shipped with the open source version. A commercial version exists which includes these modules and functionality. Our aim is to see if we can write these missing modules ourselves which provide similar functionality. We are aided in that effort by some hints left in the open source version as to what the database fields and tables are that are missing.


System Requirements
===================

- Windows 2000+ or Linux
- IIS, Apache, nginx, lighttpd or other similar web servers
- MySQL v4.1.3+ or PostgreSQL v8.0+ (PostgreSQL is recommended because it deals with timezones much better)
- PHP v5.2+ with PEAR and Safe Mode, magic_quotes_gpc disabled and memory limit 128M or higher
- PHP PGSQL or MySQLi extension
- PHP BCMATH extension
- PHP GETTEXT extension
- PHP CALENDAR extension
- PHP SOAP extension
- PHP GD extension
- PHP MCRYPT extension
- PHP SimpleXML extension
- PHP MAIL extension

