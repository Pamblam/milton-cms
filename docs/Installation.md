<p align="center">
	<img src="images/milton.png" />
</p>

# Installing Milton CMS

Milton CMS is designed to be easy to install. Clone the repository, configure Apache, and fire up the browser.

## 1. Download the Software

Git is the easiest, but you're an adult, you can do it however you want. Copy the software to somewhere where Apache can access it. This is usually `/var/www`, but can vary.

Generally, this step could look like this: `cd /var/www/ && git clone git clone git@github.com:Pamblam/milton-cms.git`

## 2. Configure Apache

 1. Determine the location of your Apache config file. On most systems you can run `apachectl -V` and the location of your Apache config file will be shown after `SERVER_CONFIG_FILE`.
 2. Open your Apache config file. You can then use whatever you like to open this file. I like Nano: `nano /path/to/httpd.conf`.
 3. Change the `DocumentRoot` to the full path to the `/public` directory of your Milton Installation, eg: `DocumentRoot "/var/www/milton-cms/public"`. Note: you can skip this step if you're setting up a development copy, or using a VirtualHost, but that is outside the scope of this tutorial.
 4. In the `<directory>` section of your DocumentRoot, ensure you have `AllowOverride All`.
 5. Ensure that the `mod_rewrite` module is enabled. Find the line that looks like this `LoadModule rewrite_module lib/httpd/modules/mod_rewrite.so` and make sure it isn't commented out.
 6. Save your changes and restart Apache. 
   - For most modern systems: `sudo systemctl restart apache2.service`
   - For Red Hat based systems: `sudo systemctl restart httpd.service`

## 3. Run the Installer

The installer is browser-based because the user you're running in the command line may have different access to the core files then the user that is running Apache, therefore, the easiest way to make sure everything is set up properly is to use the same Apache user that will serve the compiled application.

To get started, simply point your browser to your app and run the commands it gives you. After file permissions are set up, it will ask you to provide some information about your app and create a user account. The first user account you create is automatically granted administrator privileges, so it can manage every other user account you add later. After each step is completed, click the "Continue Installation" button.