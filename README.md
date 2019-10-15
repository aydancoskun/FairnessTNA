FairnessTNA Time & Attendance
==========================

Introduction

FairnessTNA is a third-party fork of the TimeTrex Workforce Management Software. FairnessTNA attempts to add functionality to TimeTrex with a goal of making things even simpler and faster. We are very grateful to the TimeTrex team for providing the base that we built FairnessTNA on. Since this is a fork we'll pull in changes from the original TimeTrex regularly as they are released.


Some of the documentation in this readme was taken from the TimeTrex website and provided here for convenience, so that you can read this document and know about all features provided. The original documentation can be found here: https://help.timetrex.com/latest/enterprise/Introduction/Administrator-Guide-Use.htm.


Since FairnessTNA is intended to replace TimeTrex, it still uses most of the same database structure. Generaly speaking FairnessTNA should be a drop in replacement for the TimeTrex Community Edition, but we are a small team. Please back up your TimeTrex install carefully before attempting a migrations.


FairnessTNA is licensed under the AGPL as is its upstream counterpart. See LICENSE file for the full license text and COPYRIGHT file for attribution and notes.


To install FairnessTNA follow INSTALL.txt. It should run on any linux as well as other proprietary OSs as long as the system requirements are met.


So far we streamlined some of the original code base.


Some of the advanced functionality depends on proprietary (non AGPL) modules which are not shipped with the open source version. A commercial version exists which includes these modules and functionality. Our aim is to see if we can write these missing modules ourselves which provide similar functionality. We are aided in that effort by some hints left in the open source version as to what the database fields and tables are that are missing.

Currently we are not 100% sure the install will go smooth as we have not fully tested it. Any pull requests are welcome.

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

