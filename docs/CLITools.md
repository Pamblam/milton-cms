<p align="center">
	<img src="images/milton.png" />
</p>

# Command Line Tools

Milton CMS includes command-line NPM scripts. The nature of NPM requires that these scripts be run from the root directory of your Milton CMS installation.

## `npm run build`

The `build` script compiles your app along with your chosen theme. This needs to be run after your configuration changes, or anytime your theme changes to rebuild your blog's source code.

## `npm run create_user`

The `create_user` script creates a new user that can manage your content. The installation script will create your first user account (and grants it administrator privileges), but if you want more than one account managing your content, this script can add another one. This is an interactive command that will prompt you for inputs via the command line and valid your inputs. Users created with this script are **not** administrators by default; use `edit_user` to promote them.

## `npm run edit_user <username or ID>`

The `edit_user` script allows you to change a password or username for an existing user. Since both usernames and user IDs are unique, you can pass either one as the first and only argument. Like the `create_user` script, this is also an interactive command that will prompt you for inputs and validate them.

After updating the user's details, the script will also ask whether you want to **grant or revoke administrator privileges** for that user. Administrators can see and edit every user's account from the *Edit Users* admin page; non-administrators can only see and edit their own account. As a safeguard, the script will refuse to remove the last remaining administrator.

## `npm run update`

The `update` script is used to copy core system files from one Milton CMS repository to another, as well as modify the `package.json` file. This script can be used to update (or downgrade) a Milton CMS installation while maintaining your themes, configuration settings, and database content. Step-by-step instructions for updating are included in the [Updating Docs](Updating.md).