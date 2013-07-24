;
;
; TimeTrex Configuration File
; *Linux* Example
;
;

;
; System paths. NO TRAILING SLASHES!
;
[path]
;URL to TimeTrex web root directory. ie: http://your.domain.com/<*BASE_URL*>
;DO NOT INCLUDE http://your.domain.com, just the directory AFTER your domain
base_url = /fairness/interface

;
;log directory
;
;Linux
log = /Applications/mampstack-5.4.17-0/apache2/htdocs/fairness.logs

;
;Misc storage, for attachments/images
;
;Linux
storage = /Applications/mampstack-5.4.17-0/apache2/htdocs/fairness.storage

;
;Full path and name to the PHP CLI Binary
;
;Linux
php_cli = /usr/bin/php



;
; Database connection settings. These can be set from the installer.
;
[database]
;type = mysqli
type = postgres8

host = localhost
database_name = timetrex
user = timetrex
password = timetrex


;
; Email delivery settings.
;
[mail]
;Least setup, deliver email through TimeTrex's email relay via SOAP (HTTP port 80)
;delivery_method = soap

;Deliver email through local sendmail command specified in php.ini
;delivery_method = mail

;Deliver email through remote SMTP server with the following settings.
delivery_method = smtp
smtp_host=smtp.gmail.com
smtp_port=587
smtp_username=aydan.ayfer.coskun@gmail.com
smtp_password=7f1355599


;
; Cache settings
;
[cache]
enable = FALSE
;Linux
dir = /Applications/mampstack-5.4.17-0/apache2/htdocs/fairness.cache



[debug]
;Set to false if you're debugging
production = TRUE

enable = TRUE
enable_display = TRUE
buffer_output = TRUE
enable_log = FALSE
verbosity = 10



[other]
; Force all clients to use SSL.
force_ssl = FALSE
installer_enabled = FALSE
primary_company_id = 1
hostname = localhost

;default_interface = flex

;WARNING: DO NOT CHANGE THIS AFTER YOU HAVE INSTALLED TIMETREX.
;If you do it will cause all your passwords to become invalid,
;and you may lose access to some encrypted data.
salt = f57786da0c9efb0b928834634fff3a8f

[branding]
application_name = TimeTrex-test
organization_name = TimeTrex
organization_url = www.TimeTrex.com
version = 7

;product_edition options = Community(10), Professional(15), Corporate(20), Enterprise(25)
product_edition = Community

product_name = noidea
registration_key = hasfh78zruwgefhjdb






























;<?php if (; //Cause parse error to hide from prying eyes, just in case. DO NOT REMOVE?>
