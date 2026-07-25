<p align="center">
	<img src="images/milton.png" />
</p>

# Content Management

From the browser, navigate to the `/admin` directory of your app and log in with the user and password you created during setup. You can create, preview, edit, delete, publish and unpublish all your posts here.

## Anatomy of a Post

Here' what you need to know when creating a new post

 - **Title**: The title is converted to a unique slug that is used as a permalink for this post, for SEO purposes. If you create a post call "Hello, world!" it will be accessible from `yourwebsite.com/hello-world`. The title is also added to the OpenGraph tags of the page. 
 - **Summary**: The summary may be used by the theme, or not. It is important and included for SEO purposes though as it is appended to the the page's OpenGraph description tag.
 - **Post Body**: Type your post body in the `compose` section and preview it in the `preview` tab. Posts are formatted with Markdown. There are no cute little buttons to make your text bold.
 - **Images**: There is an *Insert Image* button that uploads an image and drops the markdown to display it into the `compose` textarea. The markdown is inserted **at your cursor's position** (replacing any selected text); if you haven't placed your cursor in the textarea, it is appended to the end. Images are limited by whatever size restriction you indicated in the setup script. The first image in the post is also used as the OpenGraph image for the post.
 - **Tags**: Tags are used to categorize content. Can be useful for themeing.
 - **Publish**: You can write and save posts without publishing them. Any post that is not published will not be available to the public, and can only be previewd by a logged-in user.

## Managing Media

The **Media** admin page catalogs every image that has been uploaded (whether from the *New Post* page or the Media page itself). From here you can:

 - **Upload** one or more new images directly, without attaching them to a post.
 - **Copy** an image's markdown to paste into any post.
 - **Delete** an image, which removes both the database record and the file on disk. Note that deleting an image that is still referenced by a post will cause that image to stop displaying in the post.

## Managing Users

The **Edit Users** admin page lets you manage user accounts:

 - **Administrators** can see and edit *every* user's account — username, display name, password, and whether each user is an administrator.
 - **Non-administrators** can only see and edit their own account.

The first user created during installation is automatically an administrator. You can promote or demote other users from this page (or with the [`edit_user` CLI script](CLITools.md)). Milton will never let you remove the last remaining administrator, so you can't accidentally lock yourself out.