=== AutoNav Graphical Navigation and Gallery Plugin ===
Contributors: wlindley
Donate link: http://www.wlindley.com/website/autonav/
Tags: pages, navigation, widget
Requires at least: 2.8
Tested up to: 2.8.4
Stable tag: trunk

Plugin has two modes. In navigation mode: Creates a list or table of the current page's child pages. Tables are composed of linked thumbnail pictures based a custom field in each child page, or the child page's attached picture. In gallery mode: Creates one or more tables of linked thumbnail pictures based on the current page's attachments, or on specified directories of picture files under the uploads directory.

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

Uses parameters from the Gallery Shortcode (introduced in Wordpress 2.5):

     display="x"     Chooses a display mode based on "x" as follows:
		     images -- displays a table of images, one for each of the child
		          pages of this post. 
		     list -- displays a list of links to the child pages of this post. 
		     attached -- displays a table of images attached to the post
		     /folder -- displays a table of images located in the
		          wp-content/uploads/folder directory
     columns="4"     displays 4 columns of images
     size="x"	     Choose a display size 'x' as:
		     thumbnail, medium, large, full -- Wordpress standard sizes
		     300x200 -- force images to be resized and cropped to exact size
		     auto -- uses settings from autonav control panel
     titles="1"      Displays page titles below images if 1 (default: "0")
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
     imgrel="lightbox" Sets the relation tag of the <a> to be: rel="lightbox"
     group="vacation1" When combined with imgrel="lightbox*" this sets the relation
		       tag to be: rel="lightbox[vacation1]
     exclude="3,5"   Excludes pages with ID 3 and 5 from the list (with display="list")
     postid="123"    Displays images or subpages attached to the page or post
     		     with the given ID, instead of the current page or post.

Parameters not specified will be taken from the values set in the WordPress admin panel.

== Installation ==

This section describes how to install the plugin and get it working.

1. Create the autonav-wl directory in the `/wp-content/plugins/` directory,
   and place the plugin files there.
2. Activate the plugin through the administration menus in Wordpress.
3. Configure the plugin under Settings in the Wordpress administration menu.

== Frequently Asked Questions ==

= How do I set the thumbnail for a page? =

By default, the thumbnail for a child page is assumed to be its first
attached image.  If you wish to override this, create a Custom Field
called subpage_thumb for the page.  Set it to either a URL:

    http://www.example.com/images/thumbnail3.jpg

or to a local file (assumed to be under the uploads directory of your
wp-content):

    optional_directory/picture3.jpg

In the latter case, point to the full-sized image, and the thumbnail
will automatically be resized.

= How do I override the title for a child page? =

Create a Custom Field called subpage_title for the page.  Set it to
what you would like displayed in the table or list of child pages.

= What CSS classes does this plugin create? =	    

In navigation mode, when a list is selected:

   * ul elements have class: subpages-list
   * li elements have class: subpages-item

In table modes:

   * table elements: subpages-table
   * tr elements: subpages-row
   * td elements: subpages-cell
   * p elements inside each td: subpages-text

= I updated the plugin, but the new parameters are not recognized. =

Go through the Autonav Options on the Wordpress administration screen
once, and save the options. That will add the new parameter names to
the list of recognized ones.

= Can I disable certain attached images? =

Yes, using the Media Library in the admin screens, set an image's Order
to -101 or less, and it will not be shown with [autonav display=attached]

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


