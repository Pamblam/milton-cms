<p align="center">
	<img src="docs/images/milton.png" />
</p>

**Milton CMS** is a modern, portable, lightweight CMS built on PHP, SQLite, and React. It does less on purpose, and that makes it better.

 - 🚀 Single-Page App (SPA). Page reloads are for amateurs.
 - 🔍 SEO + OpenGraph tags baked in.
 - ✍️ Markdown only. If you want WYSIWYG, try Word.
 - 🐧 Designed for Apache on Unix/Linux.
 - 🍪 No PHP sessions, no cookies. Bring your own snacks.

## Philosphy

Milton CMS is the Vim of content management systems: lean, efficient, and unapologetically text-driven. Nothing here is WYSIWYG, because if you want to drag boxes around, other tools already exist. If you’d rather stay close to the command line and keep full control, Milton CMS rewards you with speed and simplicity.

Most CMS platforms still behave like it’s 2008, rendering every page from scratch. Milton CMS, by contrast, is a true SPA. Everything runs from a single index.php file, with PHP handling only the essentials (like inserting OpenGraph tags), while React’s router takes care of the rest.

Mama said cookies are the devil, so Milton CMS doesn’t use them. Authentication relies on tokens tied to your user-agent and IP, stored in LocalStorage, and refreshed with every request. It’s a cleaner, more secure model.

## Quick Start Installation

After Apache is configured, installation and setup happens in the browser. This is because Apache setups and permission requirements can vary. Running it from the same environment that will serve the app is the most reliable way to ensure file permissions are configured correctly.

 - Clone the app to your Linux/Apache/PHP server: `git clone git@github.com:Pamblam/milton-cms.git`
 - Set your Apache Config to point the to the `/public` directory as your `DocumentRoot`, enable overrides and `mod_rewrite`
 - Navigate to your site in the browser and follow the on-screen instructions.

For detailed setup instructions, refer to the [Installation Guide](docs/Installation.md).

## Managing Content

From the browser, navigate to the `/admin` directory of your app (or click the **Admin** link at the bottom of the default theme) and log in with the user and password you created during setup. You can create, preview, edit, delete, publish and unpublish all your posts here. You can also manage your uploaded images from the **Media** page and manage user accounts from the **Edit Users** page. For a more in-depth look at content management, have a look at the [Content Management Guide](docs/ContentManagement.md).

Milton has a simple two-tier permission model: **administrators** can see and edit every user's account, while everyone else can only manage their own. The first user created at install time is an administrator.

## Themeing and Customizing

Milton CMS is designed to be customizable. Theme components and custom pages are written with React and stored in the app's `/themes` directory. If you're ready to dive into theming, have a look at the [Theme Building Docs](docs/ThemeBuilding.md).

## Updating Milton CMS

As updates become available, your Milton CMS instance can be updated without losing any of your content or settings. For full instructions, see the [Updating Documentation](docs/Updating.md). 

## Version Controlling

Milton CMS is designed to be fully self-contained for quick and easy backups. It's use of SQLite means you can have a local development installation that mirrors your live web site. Milton loves Git, but you can use any version control method you like. There's more information on this available in the [Version Control Docs](docs/VersionControl.md).

## Site Administration

Milton CMS includes CLI scripts for administrative tasks. Here's a quick rundown, a complete list can be found in the [CLI Tools Docs]().

 - `npm run build`: Installs and transpiles your theme code, building the app.
 - `npm run create_user`: If you want to have multiple users creating content, you can add another one with this script.
 - `npm run edit_user <username or id>`: Conveinience script to edit a user based on their ID or username, including granting or revoking administrator privileges.
 - `npm run update <path to new version>`: Update your CMS instance to a new version.
 
## License

Milton CMS includes an MIT License.