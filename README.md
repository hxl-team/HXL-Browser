# HXLbrowser 

A web-based browser for the **Humanitarian eXchange Language (HXL)** (see [hxl.humanitarianresponse.info](http://hxl.humanitarianresponse.info)), based in jQuery. Displays the HXL data for *follow-your-nose* browsing, including a map interface for data containing geographic references.

To have the URIs resolved, the server needs to redirect all requests that have a certain fragment in the URI to the PHP script. Something like this should do the job:

<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule> 