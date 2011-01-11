=== AutoNav Graphical Navigation and Gallery Plugin ===
Contributors: wlindley
Donate link: http://www.wlindley.com/website/autonav/
Tags: child, pages, navigation, gallery, thumbnail, thumbnails
Requires at least: 2.8
Tested up to: 3.0.1
Stable tag: trunk

Creates a list/tables of text/thumbnail links to the current page's children; OR Creates tables of thumbnail links to gallery directories, posts by category/author/tag, or attachments.

== Description ==

Auto Graphics Site Navigation with Gallery

I wrote this plugin to make it easy for me to write graphical page-based Wordpress sites.  I wanted to address these issues:

  * I wanted to create websites with many nested pages, and permit the
    user to navigate among them by clicking on pictures within the
    pages, rather than having to use the Page List.  I wanted to list
    child pages in tables, and have the size of the tables, and the
    number of rows, automatically computed to fit my layout, based on
    the thumbnail sizes.

  * I wanted the thumbnail pictures, and the galleries of pictures I
    added in each page, to have be automatically resized either
    through a single default setting in the Wordpress administration
    page, or by specifying a size in a page I was editing.  I further
    wanted those thumbnails to be automatically generated, or
    regenerated, so there would never be a missing image.

  * I wanted to put all the images for a specific page, in a single
    directory under the wp-content/uploads directory.  This makes it
    easy to add or remove images from FTP or from a command line.  It
    also makes it possible to move an image from one page to another
    -- which is maddeningly difficult if not impossible using
    Wordpress's built-in Attachment system.

This plugin does all that, with two modes.

In navigation mode: Creates a list or table of the current page's
child pages. Tables are composed of linked thumbnail pictures based a
custom field in each child page, or the child page's attached picture.
Example:

    [autonav display="images" pics_only="1"]

displays a table of the current page's child pages.  Only child pages
that have associated pictures will be displayed.  The table will have
3 or 4 columns depending on the default size of the thumbnails and
depending on the column settings in the Wordpress administration
screen.

In gallery mode: Creates one or more tables of linked thumbnail
pictures based on the current page's attachments, or on specified
directories of picture files under the uploads directory. Example:

    [autonav display="/project2" include="32,37,56"]

Displays a table, with a gallery of three pictures from the
wp-content/uploads/project2 directory, in the specified order.

     display="x"     Chooses a display mode based on "x" as follows:
		     images -- displays a table of images, one for each of the child
		          pages of this post. 
		     list -- displays a list of links to the child pages of this post.
		     attached -- displays a table of images attached to the post
		     attachments -- displays attachments selected with J. Christopher's
		          "Attachments" plugin
		     posts -- displays table of posts listed in the postid="" parameter
		     posts:TYPE -- displays posts in custom post type TYPE
		     /folder -- displays a table of images located in the
		          wp-content/uploads/folder directory
		     Optional parameters, in a comma-separated list:
			  excerpt  -- Display the child page's manual excerpt (see FAQ)
			  thumb    -- Display the page's thumbnail
			  title    -- Display the page's title
			  siblings -- Display sibling pages (other children of parent)
			  self     -- Include this page in siblings (normally excluded)
			  list     -- Used with display="posts" for list, not table
			  image    -- For posts, link to full-size of thumbnail
			  	      instead of to post itself
			Example: display="list,thumb,excerpt"
     caption="x"     Adds a caption to the table. (First table only, see combine below)
     columns="4"     Displays 4 columns of images
     size="x"	     Choose a display size 'x' as:
		     thumbnail, medium, large, full -- Wordpress standard sizes
		     300x200 -- force images to be resized/cropped to an exact size
		     auto -- uses settings from autonav control panel
     titles="1"      Displays page titles below images if 1 (default: "0")
		     (Also set by 'title' parameter to 'display=')
     pics_only="1"   When displaying child pages, only show those with associated images
     include="1,7"   The resulting table will have only two pictures, the first
		     found ending in "1" and "7" -- note that because both 1 and 7
		     are numeric, the image "pic11.jpg" would not be included, but
		     "pic1.jpg" or "pic01.jpg" would be.  For non-numeric values, the 
		     first found picture whose name ends with the value given will
		     be selected.
     combine="x"     Combines table rows as follows (default: "all")
		     all -- all rows combined into one table
		     none -- each row a separate table
		     full -- combine all full rows into one table, with trailing
			  row a separate table (so it can be centered)
     crop="1"        Crops images to fit exact size, or "0" to fit maximum into size.
     sharp="1"       Uses (blocky) 'resize' instead of (smooth) 'resample' to downsize.
     start="1"       Starts at the second image or page (counting from zero)
     count="2"       Includes only two images or pages
     paged="12"      Displays 12 images on one 'page' along with next/prev, and page
     		     numbers.  NOTE: 'start' and 'count' are applied first to trim
		     which images are included in those displayed and paged.
     order="desc"    Sort order: "asc" ascending, "desc" descending, "rand" random
     orderby="x"     Where 'x' is one of the orderby parameters from:
                     http://codex.wordpress.org/Template_Tags/query_posts#Orderby_Parameters
		     The orderby parameter is not used when displaying attachments or
		     images from a directory. 'pagemash' uses the PageMash plugin's order.
     imgrel="lightbox" Sets the relation tag of the <a> to be: rel="lightbox"
     group="vacation1" When combined with imgrel="lightbox*" this sets the relation
		       tag to be: rel="lightbox[vacation1]
     exclude="3,5"   Excludes pages with ID 3 and 5 from the list (with display="list")
     postid="123"    Displays images or subpages attached to the page(s) or post(s)
     		     with the given ID, or comma-delimited list of IDs, instead of the
		     current page or post. Can also select posts in category/tag/author.

Parameters not specified will be taken from the values set in the WordPress admin panel.

In addition to a numeric postid, you may select posts as follows:

     postid="category:17"    posts in a numeric category or categories
     postid="category:-17"   posts *not* in a numeric category
     postid="category:cakes" posts by category name
     postid="tag:37,38,53"   posts with numerically specified tag(s)
     postid="tag:chocolate"  posts by tag name
     postid="author:27"      posts by a specific author by ID
     postid="author:Todd"    posts by author name

Categories and tags can also have multiple values separated by commas (posts in
any of the categories or tags) or '+' plus signs (posts which are in all of the
categories or tags).

NOTE: J. Christopher's Attachments plugin lets you attach anything in Wordpress's
Media Gallery to any post.  See:  http://wordpress.org/extend/plugins/attachments

NOTE: Additional example values for Sharp parameter:

   * 0 -- standard smooth resample
   * 1 -- standard blocky resize
   * 60 -- resize, with 60% image quality on JPEG save
   * 95.75 -- intermediate image resized down by 75%, then resampled to final
          giving a "75% sharpness" factor, then saved with 95% image quality
   * 90.50 -- "50% sharpness" and 90% image quality
   * -60 -- resampled, and saved with 60% image quality

== Installation ==

This section describes how to install the plugin and get it working.

1. Create the autonav-wl directory in the `/wp-content/plugins/` directory,
   and place the plugin files there.
2. Activate the plugin through the administration menus in Wordpress.
3. Configure the plugin under Settings in the Wordpress administration menu.

== Frequently Asked Questions ==

= How do I set the thumbnail for a page? =

In Wordpress 2.9 and later, the thumbnail you choose in the page's
edit screen becomes the default thumbnail. If you do not choose a
thumbnail there (or in Wordpress versions below 2.9), the attached
image with the lowest order (chosen in the Gallery section of the
page's image attachment dialog) becomes the default thumbnail.

You can override the default thumbnail by creating a Custom Field
called subpage_thumb for the page.  Set it to either a URL:

    http://www.example.com/images/thumbnail3.jpg

or to a local file (assumed to be under the uploads directory of your
wp-content):

    optional_directory/picture3.jpg

In the latter case, point to the full-sized image, and the thumbnail
will automatically be resized.

= How do I enable post thumbnails in Wordpress? =

If you don't see the Post Thumbnail section in your administration
screens, add this to your theme's functions.php --

  <?php add_theme_support( 'post-thumbnails' ); ?>

= How do I override the title for a child page? =

Create a Custom Field called subpage_title for the page.  Set it to
what you would like displayed in the table or list of child pages.

= What CSS classes does this plugin create? =	    

In navigation mode, when a list is selected:

   * ul elements have class: subpages-list
   * li elements have class: subpages-item
   * Excerpt text: subpages-excerpt
   * Thumbnail images: subpages-list-image

In table modes:

   * table elements: subpages-table
   * tr elements: subpages-row
   * td elements: subpages-cell
   * p elements inside each td: subpages-text
   * Excerpt text: subpages-excerpt

The 'subpages' prefix may be overridden by the 'class' parameter or 
on the administration screen.

= I updated the plugin, but the new parameters are not recognized. =

Go through the Autonav Options on the Wordpress administration screen
once, and save the options. That will add the new parameter names to
the list of recognized ones.

= Can I re-attach an attachment to another page? =

Thry the Change Attachment Parent plugin, which is an easy way to
reset the parent page or post for any attachment, right on the
attachment's Edit screen in the Media Library. You do need to know the
new parent's ID, though.

    http://wordpress.org/extend/plugins/change-attachment-parent/

= How can I rearrange my pages? =

Try the Pagemash plugin which lets you move pages up, down, in, out,
and around your hierarchy with the mouse.  It automatically changes
the pages' parents and menu order.

    http://wordpress.org/extend/plugins/pagemash/

= Does this plugin create database tables? =

No, only the one entry which holds the settings.  This is in the
wp_options table, with option_name = "autonav_wl" and that will be
updated by going through the AutoNav administration screen (see above).

= I get an error that imagecreatefromjpeg is not defined =

Your webserver needs GD image support in PHP. On Debian or Ubuntu,
you can install that with apt-get, and then restart Apache. 
For example:

   $ sudo apt-get install php5-gd
   $ sudo /etc/init.d/apache2 restart

= Can I disable certain attached images? =

Yes, using the Media Library in the admin screens, set an image's Order
to -101 or less, and it will not be shown with [autonav display=attached]

You can also set the post_status of an attachment to 'private' or
'draft' although Wordpress gives you no menus to do this, and as of
Wordpress 2.9 the Media Manager has some problems (showing size=0)
with attachments so set.

= Some of my images do not appear. =

If you upload a picture with a filename that resembles Wordpress' resized
image names, the Autonav plugin may not be able to find it.  For example,
if you upload a picture called mybike-640x528.jpg, Wordpress will create
thumbnails and there will be three files in the uploads directory:

	mybike-640x528.jpg
	mybike-640x528-150x150.jpg
	mybike-640x528-300x247.jpg

Autonav will not find your "full size" picture, the 640x528 one, because it
ends in a dash followed by two numbers with an 'x' inbetween.  You will have 
to rename your original picture before uploading it into Wordpress.

= How do I add manual excerpts to pages? =

By default, Wordpress (as of v2.9) includes the ability to edit manual excerpts
only for Posts, not Pages.  You can add a few lines of code to your theme's
functions.php to enable the functional for Pages as well, see:

     http://mfields.org/2008/04/02/how-to-activate-excerpts-for-pages-in-wordpress-admin-panel/

= Can I call the plugin from a template? =

Yes, you may use this code in your template, for example, where you
wish a table of child pages' thumbnails:

  <?php print autonav_wl_shortcode(array('display'=>'images')); ?>

or where you would like a table of all attached images:

  <?php print autonav_wl_shortcode(array('display'=>'attached')); ?>

= Can I show all attachments, but highlight the first couple? =

Yes; try these three lines:

    [autonav display=attached columns=1 count=1 size=500x500]
    [autonav display=attached columns=1 start=1 count=1 size=300x300]
    [autonav display=attached columns=4 start=2]

You could even include similar calls in a template (see above), showing the 
first attachment in the large size in one place, the second attachment in
medium size elsewhere, and all remaining attachments at perhaps the bottom.

= Can I display tooltips? =

Yes, when displaying attached images, the image's Title as set through the
Wordpress admin screen is put into the 'a' tag's title attribute. On modern
browsers this becomes a tooltip when hovering over the image inside the anchor.

== Changelog ==

= 1.0 =
* Initial version on wordpress.org

= 1.1 =
* Add page exclude parameter

= 1.1.1 =
* Resolve resize warnings when PNG images included

= 1.1.2 =
* Display=attached could result in error; corrected.

= 1.1.3 =
* Add postid="n" parameter.
* Attached images with a menu_order of less than -100 will not be
  displayed.  This is the "Order" you can set in the media library.

= 1.1.4 =
* Regard menu_order in attached files list.
* Permit parameter:  order=desc   to display attached files in 
  descending attachment order.
* Corrected handling of images with capitalized extensions (e.g., .JPG)
* postid= parameter accepts multiple values. For example:
    [autonav display=images postid=7,15]
  will display a table consisting of thumbnails linked to the child
  pages, of the pages with ids 7 and 15.

= 1.1.5 =
Corrected typo

= 1.1.6 =
* When listing pages with display=images or display=list and specifying a
  postid= parameter, each item in the postid= list will:
    - if the page has children, list that page's children (with display=list)
      or the children's thumbnails (with display=images)
    - if the page has NO children, list that page or its thumbnail.

= 1.1.7 =
* Wordpress 2.9: If you select a thumbnail in a page's edit screen,
  that thumbnail will pre-empt the "choose the first attached image" logic,
  although specifying a subpage_thumb custom field still has priority.

= 1.1.8 =
* Move options under Settings in adminstration screens
* Could not pics_only option unless checked in admin screen
* Images with subpages_thumb were not displayed in some cases

= 1.1.9 =
* Improve admin screen formatting.  
* Add option for default number of columns.
* Compatibility with 2.9.0beta and 2.9.0rc1

= 1.2.0 =
* Resolve incompatibility with Windows-hosted paths

= 1.2.1 =
* W3C validation, correct case of incorrect table and row markup nesting
* Caption parameter added (be sure to go thru Settings screen in admin and
  save settings, even if not changed, to permit new parameter)
* Picture 'alt' tag will be title if available

= 1.2.2 =
* Remove superceded v2.9.0 beta functions
* If a page defines the custom field 'subpage_excerpt' or has a manual excerpt
  defined, that will be displayed when the 'excerpt' parameter is included
  with 'display' (e.g., "[autonav display=list,excerpt]" )

= 1.2.3 =
* Add optional "siblings" parameter, e.g., [autonav display="images,siblings"]
  which will select the current page's siblings (other children of the
  same parent).  Also "self" parameter which when used with "siblings" will
  include the currently displayed page in the list of siblings.

= 1.2.4 =
* Handle edge case of no pictures to display

= 1.2.6 =
* Escaped attribute values on alt=""
* Add background parameter for later support of transparency in PNG images
* Support display=posts parameter, to display posts instead of pages or attached images.
* Alternate text and title text for attached images, as set in admin screens, is used.

= 1.2.7 =
* Ability to select posts by "tag:x", "author:x", or "category:x" where "x" is
  a numeric id or text (tag slug, author name, or category name); or a series
  thereof ("5,7,9" to select posts in category 5, 7, or 9; or "cakes+vanilla" for
  posts tagged with both cakes and vanilla; see query_posts() in codex for details)
  Example: [autonav display="posts" postid="tag:7"]

= 1.2.8 =
* Plugin activation hook to create default settings, or otherwise provide reasonable
  defaults for new parameters. This should eliminate plugin failures even when admin
  does not go through Settings screen.

= 1.3.0 =
* Add 'paged' parameter.  Correction on page selection (formerly, the start and count
  parameters were applied twice, resulting in too few pages being displayed)

= 1.3.1 =
* Support random order for pages. Thanks http://wordpress.org/support/profile/thomas_n for the patch.

= 1.3.2 =
* Support Jonathan Christopher's Attachments plugin http://mondaybynoon.com/wordpress-attachments/

= 1.3.3 =
* Permit order="desc" on pages as well as posts http://wordpress.org/support/topic/autonav-order-desc?post-1823500

= 1.3.4 =
* Correct handling of order, orderby, and count parameters in various combinations of display="posts"
* Add modifier "image" which, used as: [autonav display="posts,image"] will create a link to the (thumbnail) image for the post, rather than to the post itself.

= 1.3.5 = 
* Add sharp parameter to use resize instead of resample.

= 1.3.6 =
* Add subpages-image class to images
* Permit display="posts:foods" to display custom posts of type "foods"
* Added Sharpness factor and image quality level
* Better memory handling, with imagedestroy while resizing images
* Explicitly specifying an image, even if thumbnail-sized or smaller, will work
  (so long as it does not end in a size; "foo.jpg" OK but "foo-64x64.jpg" will be skipped)
