;<?php die('Unauthorized Access...'); //SECURITY MECHANISM, DO NOT REMOVE//?>
;
; FairnessTMA Configuration File
;

;
[path]
; URL to FairnessTNA web root directory. ie: http://your.domain.com/<*BASE_URL*>
; DO NOT INCLUDE http://your.domain.com, just the directory AFTER your domain
base_url = "/interface/"

;
; Log directory
;
; NOTICE: For security reasons, this must be outside the web server document root.
log = "/var/log"
;log = c:\fairness\log

;
; Misc storage, for attachments/images
;
; NOTICE: For security reasons, this must be outside the web server document root.
storage = "/var/storage"
;storage = c:\fairness\storage

;
; Full path and name to the PHP CLI Binary
;
php_cli = "/usr/bin/php"
;php_cli = c:\php\php.exe

;
; Database connection settings. These can be set from the installer.
;
[database]
type = postgres8
host = localhost
database_name = 
user = 
password = 
persistent_connections = FALSE


;
; Email delivery settings.
;
[mail]
; Deliver email through remote SMTP server with the following settings.
delivery_method = smtp
smtp_host = localhost
smtp_port = 25
smtp_username =
smtp_password =
; The domain that emails will be sent from, do not include the "@" or anything before it.
email_domain = 
; The local part of the email address that emails will be sent from, do not include the "@" or anything after it.
email_local_part = DoNotReply

;
; Cache settings
;
[cache]
enable = TRUE
; NOTICE: For security reasons, this must be outside the web server document root.
dir = "/tmp"
;dir = c:\temp\fairness

;
; debug settings
;
[debug]
; Set to false if you're debugging
production = TRUE
enable = FALSE
enable_display = FALSE
buffer_output = TRUE
enable_log = FALSE
verbosity = 10

[other]
primary_company_id = 0
;override_password_prefix = 
default_interface = html5
; Force all clients to use SSL.
force_ssl = FALSE
installer_enabled = FALSE
: you want to keep disable_feedback=TRUE as its anyway not sending anything in the moment
disable_feedback = TRUE
; Specify the URL hostname to be used to access FairnessTNA.
; The BASE_URL specified above will be appended on to this automatically.
; This should be a fully qualified domain name only, do not include http:// or any trailing directories.
hostname = localhost

; ONLY when using a fully qualified hostname specified above, enable CSRF validation for increased security.
;enable_csrf_validation = TRUE

; System Administrators Email address to send critical errors to if necessary. Set to FALSE to disable completely.
system_admin_email = false

; WARNING: DO NOT CHANGE THIS AFTER YOU HAVE INSTALLED FairnessTNA.
; If you do it will cause all your passwords to become invalid,
; and you may lose access to some encrypted data.
salt = 0

[branding]
application_name = FairnessTNA
disable_powered_by_logo = TRUE
