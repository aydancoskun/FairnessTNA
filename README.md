# FairnessTNA
FairnessTNA is a time and attendance and payroll system forked from TimeTrex.

The FairnessTNA v11.6.1 series is powered by TimeTrex Community Edition v11.6.1

It is licensed under the AGPL as is its upstream counterpart. Files relevant to licensing
and copyright are: 3rd_party_credits.txt , COPYRIGHT and LICENSE for the full text of the
AGPL. If you would like to dive into the background of TimeTrex licensing LICENSE_NOTES
might be of interest as well.


FairnessTNA is a third-party fork of the Community Edition of the TimeTrex Workforce 
Management Software and in no way affiliated with TimeTrex. FairnessTNA removes some
limitations and restrictions of the TimeTrex software. We are very grateful to the 
TimeTrex team for providing the base that we built FairnessTNA on. We'll try to pull in 
any changes from the original TimeTrex software regularly as they are released. We are 
maintaining a mirror of the TimeTrex community edition releases here:
https://github.com/aydancoskun/timetrex-community-edition


FairnessTNA can be installed on both Windows and Linux based operating systems, though only
linux is tested by ourselves. The TimeTrex website has details on how to install on windows
https://www.timetrex.com/how-to-install-timetrex. FairnessTNA should run equally well on 
Apache, Nginx or any other web server that can serve PHP files. We are developing and
testing it using Nginx.


The original documentation can be found here: 
https://help.timetrex.com/latest/community/Introduction/Administrator-Guide-Use.htm


FairnessTNA can be used to replace TimeTrex Community Edition as it uses a compatible 
database structure. Do not attempt to upgrade TimeTrex software that is not the Community
Edition as the database structure will most likely not be compatible. Furthermore there
be no path back from upgrading a non Community Edition. Generally speaking FairnessTNA 
should be a drop in replacement for the TimeTrex Community Edition but please back up your
original TimeTrex install carefully before attempting a migration.


So far we streamlined some of the original code base and removed branding and restrictions
from the TimeTrex base. There are certain functionalities that are only available in the
commercial offerings of TimeTrex and we are working on implementing some of these by 
creating equivalent functionality using only hints left in the AGPL code as to the
missing database tables and missing classes and class names. We could use some help to 
create this. Pull requests are welcome!


The test suite in the unit_test directory from the original TimeTrex base was not working
for us in our original set up and we had to change quite a bit to get them to work.


When we finally got the tests to run on the original TimeTrex code base, quite a number of
them failed with trailing zero errors which should be able to be safely ignored. The same
tests ran later on FairnessTNA provided the same errors. If there are bugs in the TimeTrex
code then they are most likely the same bugs in the FairnessTNA base. But we are reasonably
confident that we did not create any new ones. If you do find any bugs please check if
the same bug exists in TimeTrex. In any case please open a ticket if you find any bugs.


The selenium tests we could not get to run at all. We got selenium up and running but the
tests failed to find the required DOM elements of the forms to fill in. We are not sure if
the selenium tests actually work in TimeTrex's setup or if they are using them. In any case
we are working on that. Any help would be appreciated. Since this is software that 
potentially affects peoples livelihood we would be very happy to have the full test suite
up and running.
