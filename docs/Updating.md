<p align="center">
	<img src="images/milton.png" />
</p>

# Updating Milton CMS

These steps will update your Milton CMS install while maintaining all content and themes.
 
  1. Clone the new software alongside your existing install, eg, if you're in the parent directory that contains your existing Milton CMS instance Run: `git clone git@github.com:Pamblam/milton-cms.git ./milton_update` - Now you should have a `milton` and a `milton_new` directory in your working directory.
  2. It's a good idea to make a backup of your existing copy: `cp -R milton milton_backup`
  3. Move into the existing copy that you want to update and run the install script, passing it the path to the updated copy as the first argument, eg: `cd milton && npm run update ../milton_update`
  4. If all went as planned, you can now run the build script: `npm run build`
  5. Check it out in the browser to make sure it all looks good. If so, go ahead and delete your updated copy: `rm -rf ../milton_update`

> **Note:** If the new version introduces database tables that your install doesn't have yet, the first time you load the site after updating it will briefly show the installer, which creates the missing tables automatically before handing you back to your site. New columns on existing tables are applied by the `update` script itself.