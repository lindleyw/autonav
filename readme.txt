=== AutoNav Graphical Navigation and Gallery Plugin ===
Author: William Lindley
Author URI: http://www.saltriversystems.com/
Contributors: wlindley
Donate link: http://www.saltriversystems.com/website/autonav/
Tags: child, pages, posts, navigation, gallery, thumbnail, thumbnails, attachments, subpage, taxonomy, custom post types, custom fields
Requires at least: 3.0
Tested up to: 3.4.1
Stable tag: 1.4.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Creates customizable lists/tables of text/thumbnails/links to posts, pages, taxonomies, attachments, custom post types, and image directories.

== Description ==

Auto Graphics Site Navigation with Gallery

> NOTE: If you experience errors or missing pages, try the [previous
stable
version](http://downloads.wordpress.org/plugin/autonav.1.4.2.zip)
first, and then look in the [support
forum](http://wordpress.org/support/plugin/autonav).

This plugin simplifies the creation of graphically navigable Wordpress
sites, creating a list or tables of pages, posts, taxonomies, and
custom post types, selected in a variety of ways. Highly customizable
output using a variety of optional user-defined filters.

  * Sites with nested pages can show a table of clickable thumbnails
    of child pages, with size of the tables, and the number of rows,
    automatically computed to fit, based on the thumbnail sizes.
  * A table of posts selected by tag, category, or author can be
    displayed in the same manner.
  * Thumbnails of pictures, and the galleries of pictures added in
    each page, can be automatically resized either through a single
    default setting in the Wordpress administration page, or by
    specifying a size in each page.  Missing thumbnails will be
    automatically generated.
  * A gallery of images can be created simply by placing them in a new
    directory under the wp-content/uploads directory.  Standard
    command-line or FTP tools can then be used to move, rename, or
    delete images.
  * Output and page/post selection is extensible through the use of
    filters.
  * Works with WordPress's standard attachment mechanism, and with
    J. Christopher's "Attachments" plugin (see FAQ)

The plugin is invoked with the [autonav] shortcode, with two basic modes:

NAVIGATION.

Creates a list or table of the current page's child pages. Tables are
composed of linked thumbnail pictures (see "How is a Child Page's
"Associated Image" determined?" in the FAQ). Example:

    [autonav display="images" pics_only="1"]

displays a table of the current page's child pages.  Only child pages
that have associated pictures will be displayed.  The table will have
3 or 4 columns depending on the default size of the thumbnails and
depending on the column settings in the Wordpress administration
screen.

GALLERY.

Creates one or more tables of linked thumbnail pictures based on the
current page's attachments, or on specified directories of picture
files under the uploads directory. Example:

    [autonav display="/project2" include="32,37,56"]

Displays a table, with a gallery of three pictures from the
wp-content/uploads/project2 directory, in the specified order.

== Screenshots ==

1. Child pages displayed in two different ways, first as a list and
   then as a table.
2. AutoNav used together with the Hierarchical Pages plugin on the
   Fisher Shotcrete site. Along with the third screenshow, demonstrates
   how graphical navigation of a large site can work.
3. Second-level page on the Fisher site. Note, with Hierarchical
   Pages, only the pages that are "siblings" to the current page are 
   listed in the sidebar (otherwise there could be hundreds).
4. Demonstrating a "magazine style page template" where the first
   attachment (a) is displayed at large size, the second (b) in a
   medium, and all subsequent ones (c) as small thumbnails.  Merely
   reordering the attachments updates the page and creates any needed
   thumbnails.

== Installation ==

This section describes how to install the plugin and get it working.

1. Create the autonav-wl directory in the `/wp-content/plugins/` directory,
   and place the plugin files there.
2. Activate the plugin through the administration menus in Wordpress.
3. Configure the plugin under Settings in the Wordpress administration menu.

Additional add-ons, which are plugins that tie into AutoNav's filters,
are in the addons.zip file.  (The additional complication of this zip
within a zip is because WordPress's plugin system has no way to handle
"optional but part of the distribution" plugins).

== Shortcode Parameters ==

Parameters not specified will be taken from the values set in the WordPress admin panel.

     display="x"  Chooses a display mode based on "x" as follows:
		    images -- displays a table of images, one for each of the child
		       pages of this post. 
		    list -- displays a list of links to the child pages of this post.
		    attached -- displays a table of images attached to the post
		    attachments -- displays selections from the "Attachments" plugin
		    posts -- displays table of posts listed in the postid="" parameter
		    posts:TYPE -- displays posts in custom post type TYPE
		    /folder -- displays a table of images located in the
		         wp-content/uploads/folder directory
		  Optional parameters, in a comma-separated list:
		    excerpt  -- Display the child page's manual excerpt (see FAQ)
		    thumb    -- Display the page's thumbnail
		    title    -- Display the page's title
		    siblings -- Display sibling pages (other children of parent)
		    		NOTE: Always means siblings of CURRENT page.
		    family   -- Display all children, grandchildren, etc. of page
		    self     -- Include this page in siblings, or this page
		    	        (normally the current page or post is excluded)
		    list     -- Used with display="posts" for list, not table
		    image    -- For posts, link to full-size of thumbnail
		    	        instead of to post itself
		    page     -- For attachments, link to attachment page
		    nolink   -- Disables links entirely
		    plain    -- Replaces unordered-list with a div, for use
		    	        with JavaScript/jQuery slideshows, etc.
		  The above parameters may be preced by 'no' to disable the feature
		  (as when set by default or in the plugin options page).
		  Example: display="list,notitle,thumb,excerpt"
     caption="x"  Adds a caption to the table. (First table only, see combine below)
     columns="4"  Displays 4 columns of images
     size="x"	  Choose a display size 'x' as:
		    thumb (or: thumbnail), medium, large -- Wordpress standard sizes		    
		    size_small, size_med, size_large -- sizes from AutoNav settings
		    300x200 -- force images to be resized/cropped to an exact size
		    auto -- uses settings from autonav control panel
		    Sizes registered with add_image_size() should also work.
     titles="1"   Displays page titles below images if 1 (default: "0")
		  (Also set by 'title' parameter to 'display=')
     pics_only="1" When displaying child pages, only show those with associated images
     include="1,7" Used with display=/folder syntax only; others, see postid parameter.
		  The resulting table will have only two pictures, the first
		  found ending in "1" and "7" -- note that because both 1 and 7
		  are numeric, the image "pic11.jpg" would not be included, but
		  "pic1.jpg" or "pic01.jpg" would be.  For non-numeric values, the 
		  first found picture whose name ends with the value given will
		  be selected.
     exclude="3,dessert" Excludes posts/pages with ID 3 and the slug 'dessert'
     combine="x"  Combines table rows as follows (default: "all")
		    all  -- all rows combined into one table
		    none -- each row a separate table
		    full -- combine all full rows into one table, with trailing
			    row a separate table (so it can be centered)
     crop="1"     Crops images to fit exact size, or "0" to fit maximum into size,
     		  centering image; "2" crops from upper-left; "3" from top middle
		  (useful with head-and-shoulders portraits)
     sharp="1"    Changes downsize algorithm from (smooth) 'resample' to
		  (blocky) 'resize' (see below)
     start="1"    Starts at the second image or page (counting from zero)
     count="2"    Includes only two images or pages
     paged="12"   Displays 12 images on one 'page' along with next/prev, and page
		  numbers.  NOTE: 'start' and 'count' are applied first to trim
		  which images are included in those displayed and paged.
     order="desc" Sort order: "asc" ascending, "desc" descending, "rand" random
     orderby="x"  Where 'x' is one of the below, or any orderby parameter from:
		  http://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters
		    postmash -- use order defined by PostMash plugin
		    meta:subpage_title -- sorts by any custom field; here we
		        sort by the child page's title as overridden by the
		      	subpage_title custom field (see FAQ section)
		  The orderby parameter is not used when displaying attachments or
		  images from a directory.
     imgrel="lightbox" Sets the relation tag of the <a> to be: rel="lightbox"
     group="vacation1" When combined with imgrel="lightbox*" this sets the relation
		  tag to be: rel="lightbox[vacation1]
     postid="123" Displays images or subpages attached to the page(s) or post(s)
		  with the given ID, or comma-delimited list of IDs, instead of the
		  current page or post. Can also select posts in category/tag/author;
		  or pages with specified path, author or custom field value.

In addition to a numeric postid, you may select posts or pages as follows:

     postid="cat:17"         posts in a numeric category or categories
     postid="category:17"    (same, 'cat' is abbreviation)
     postid="category:-17"   posts *not* in a numeric category
     postid="category:cakes" posts by category name
     postid="category__and:3,7" posts that must be in both categories
     postid="tag:37,38,53"   posts with numerically specified tag(s)
     postid="tag:chocolate"  posts by tag name
     postid="tag__and:chocolate,hot" posts that have both tags
     postid="author:27"      posts or child pages with a specific author by ID
     postid="author:Todd"    posts or child pages by author name
     postid="status:draft"   draft posts or pages. Can also use custom status types.
     postid="movies:comedy"  posts tagged in a custom taxonomy
     postid="movies:drama,horror"  posts with any of those tags in custom taxonomy
     			     (if 'movies' taxonomy is defined) or with custom field
     postid="month:january"  subpages of current page, with custom field "month"="january"
     			     NOTE: selection of Pages by taxonomy not yet supported
     postid="recipes/desserts" page by its full path (NOT merely its slug)

As of version 1.4.5, you may also select attachments based on their parent
(given by slug or post-ID), their author (which WordPress sets when the 
attachment is uploaded; there is no built-in way to edit an attachment's
author, although a plugin may provide one), or by the tags set through the
Media Tags (http://wordpress.org/extend/plugins/media-tags/) plugin:

      postid="tag:dessert"

Categories and tags can also have multiple values separated by commas (posts in
any of the categories or tags) or '+' plus signs (posts which are in all of the
categories or tags).

Note, you can specify both a page/post ID _and_ one of the above.  For example,
postid="27,author:Todd" would show subpages of the page with ID=27 that have
author Todd.

The postid selectors category__and, category__in, category__not_in
permit more complex category selection, as described at:
http://codex.wordpress.org/Function_Reference/get_pages

NOTE: The Sharp parameter is now regarded only by an optional
addon. Additional example values are:

   * 0 -- standard smooth resample
   * 1 -- standard blocky resize
   * 60 -- resize, with 60% image quality on JPEG save
   * 95.75 -- intermediate image resized down by 75%, then resampled
          to final giving a "75% sharpness" factor, then saved with
          95% image quality
   * 90.50 -- "50% sharpness" and 90% image quality
   * -60 -- resampled, and saved with 60% image quality

== Frequently Asked Questions ==

= How is a Child Page's "Associated Image" determined? =

AutoNav uses the first of these that exist, as the associated picture
for a child page:

* The value of the custom field, subpage_thumb.  The value can be
either a URL (http://......) or a path relative to the
wp-content/uploads directory (2011/07/image.jpg).

* The post/page Thumbnail ("Featured Image") as set in WordPress
(assuming your theme supports them).

* The attached image with the lowest Order as chosen in the Gallery
tab of the attachment dialog.

NOTE: If a URL is given in subpage_thumb, that image will be used
directly; AutoNav cannot resize it.  In all other cases, AutoNav will
create the needed thumbnail images automatically.

= How do I enable post thumbnails in Wordpress? =

If you don't see the Post Thumbnail (or Featured Image) section in
your administration screens, add this to your theme's functions.php --

  <?php add_theme_support( 'post-thumbnails' ); ?>

= How do I override the title for a child page? =

Create a Custom Field called subpage_title for the page.  Set it to
what you would like displayed in the table or list of child pages.

= How do I use AutoNav on my "home" page, to show all my sub-pages? =

When invoked on the page set as the Static Home Page, if that page has
no children, then the other pages at top level (those with no parent)
are considered as children of the home page.  In all other cases,
if you wish to display pages at the same level as the current page
(i.e., the other children of the parent of the current page), you can
use the 'siblings' parameter.

= How can I display posts in a list? =

The following will display posts in the 'desserts' category, in a
bulleted list, with the title as a link to the page:

    [autonav display="posts,list,title,nothumb" postid="category:desserts"]

The "nothumb" parameter suppresses the "missing image" error message
for posts that do not have thumbnails.

To display posts not in a certain category, use the category's slug as
follows:

    [autonav display="posts,list,title,nothumb" 
    postid="category__not_in:desserts"]

You can use multiple category IDs or slugs with the three modifiers
category__in (posts in any of the categories), category__and (only
posts which must be in all the categories listed), or category__not_in
(posts which must not be in any of the categories listed).  This lists
posts that are in either the 'desserts' or the 'sweets' categories:

    [autonav display="posts,list,title,nothumb" 
    postid="category__in:desserts,sweets"]

= Only the first five posts get displayed. =

By default, WordPress's built-in function get_posts(), which is what
AutoNav uses to find posts and attachments, only returns the first
five results.  You can choose how many you wish displayed with
something like this:

    [autonav display="posts,list,title,nothumb" postid="category:desserts"
    count=10]

If you would like the viewer to be able to access more than the first
ten posts, you can use the paged parameter:

    [autonav display="posts,list,title,nothumb" postid="category:desserts"
    count=10 paged=10]

which will display 10 posts, and include "next/previous" links to
navigate through any additional ones. (Example courtesy spartaneye)

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
   * Thumbnail images: subpages-image
   * Excerpt text: subpages-excerpt

Next/Previous page links (with 'paged' parameter):

   * p elements: subpages-pages

The 'subpages' prefix may be overridden by the 'class' parameter or 
on the administration screen.

= What custom fields will AutoNav use? =

These are described in detail in other FAQ entries:

   * subpage_thumb: Set to a URL (http://example.com/image.jpg or
     https://...)  or to a path relative to your uploads directory
   * subpage_title: Overrides the title of a page or post
   * subpage_excerpt: Overrides the excerpt of a page or post

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

Another plugin adds the same feature to the Bulk Actions admin dropdown:

    http://wordpress.org/extend/plugins/bulk-change-attachment-parent/

= How can I rearrange my posts and pages? =

Try the Pagemash plugin which lets you move pages up, down, in, out,
and around your hierarchy with the mouse.  It automatically changes
the pages' parents and menu order.

    http://wordpress.org/extend/plugins/pagemash/

If it's posts you wish to rearrange, try this:

    http://wordpress.org/extend/plugins/postmash/

and use [autonav display="posts" orderby="postmash"]

= Can I disable certain attached images? =

Yes, using the Media Library in the admin screens, set an image's
Order to -101 or less, and it will not be shown with [autonav
display=attached] NOTE: rearranging attachments in WordPress's dialogs
will reset all those attachments' order to a positive number.

You can also set the post_status of an attachment to 'private' or
'draft' although Wordpress gives you no built-in menus to do this.

The Semi-Private Attachments plugin lets you mark an attachment as
private.  AutoNav respects this (as does the built-in gallery
shortcode) and will not display it.  The plugin also lets you disable
comments and pings for an attachment.

    http://www.saltriversystems.com/website/private-attachments/

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

= AutoNav does not detect a child page's, or a post's, thumbnail. =

Double-check that you have explicitly set a Featured Image for the
post or page. Merely invoking [autonav] in a post or page, although
that can cause a gallery to be displayed, does not set a featured
image.

= How do I use the addons? =

In your AutoNav plugin's directory you should find a file, addons.zip
which contains several add-on plugins in a subdirectory.  Move only
the files you wish to use, into the same directory as the
autonav-wl.php file itself.  Then enable whichever addons you wish.

= How do I use excerpts with pages and posts? =

Examples of displaying excerpts:

    [autonav display="list,excerpt"]
    [autonav display="posts,list,excerpt" postid="category:news" pics_only=0]

For any post or page, you can always use the custom field
'subpage_excerpt' which will override any WordPress excerpt.

NOTE: By default, Wordpress includes the ability to edit manual
excerpts only for Posts, not Pages.  You can add a single line of code
to your theme's functions.php to enable excerpts for Pages:

    add_post_type_support( 'page', 'excerpt' );

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

= How do I use AutoNav's filters? =

AutoNav provides filters which you can hook.  They are internally
called as follows.  Note that $class is the effective CSS class for
the item in question; $attr is the set of attributes for all pictures,
as passed in the [autonav] shortcode and taken from the default values
set in the administration screen; $pic is an array created for each
page, post, or the like.

* $pic_info = apply_filters('autonav_select', $pic_info, $attr); -- Is
  called whenever display= has a value which is not handled by any of
  the built-ins, so you can add your own display= values.  Return
  value should be an array in the style of pic_info (see source
  code). This filter is responsible for creating or finding all
  thumbnails and setting all links in the pic_info array. If your
  filter does nothing, you should return $pic_info to permit chaining
  multiple filters.

* $pic_info = apply_filters('autonav_thumb', $pic_info, $attr, $post )
  This filter is used to locate the featured thumbnail for a post or
  page. If $pic_info is not empty, successive filters assume that the
  thumbnail has been located, and return $pic_info unmodified.  The
  default behaviour is as follows:

    add_filter('autonav_thumb', 'autonav_thumb_featured', 10, 4);
    add_filter('autonav_thumb', 'autonav_thumb_specified', 20, 4);
    add_filter('autonav_thumb', 'autonav_thumb_attached', 30, 4);

* $attr = apply_filters('autonav_pre_select', $attr,
  $display_options); -- This permits you to modify the array which
  AutoNav uses to determine which pages or posts to display. Elements
  here are nearly same as values in the Options screen.

* $picked_files = apply_filters('autonav_pick_files', $picked_files, $attr, $pic_size_info);
  Given the calling arguments ($attr) and an array of image files found
  for the specified posts, pages, or directories. The latter array also
  includes information for each of the thumbnail sizes found for each
  of the full-size images.  The filter then picks which images are
  candidates to include, before the autonav_get_thumbnails filter.

* $pics_info = apply_filters('autonav_get_thumbnails', $pics_info, $attr, $pic_size_info);
  The filter chooses from available sizes in $pic_size_info, or
  creates resized images.

* $pic_info = apply_filters('autonav_post_select, $pic_info, $attr);
  This filter runs after AutoNav's internal page/post selection
  process, or after your custom display= selection filter (see above).
  At this point, AutoNav assumes that all links are set and any
  thumbnail images have been created.  With this filter, you can
  delete pages from the ready-to-format pic_info array of pages/posts
  to be displayed, or change values in the array, before the
  formatting code splits it into tables, or multiple pages (see WP:
  paginate_links).

* $html = apply_filters('autonav_create_list_item', $html, $class, $pic, $attr);
  $html = apply_filters('autonav_create_table_item', $html, $class, $pic, $attr);

  These create the individual list or table entries, and are called
  once for each post or page which exists in the 'selected' array as
  passed through the autonav_post_select filter.  The input $html is
  all the HTML output created so far; generally you will append to
  this and return the extended text. You may add additional filters
  here to be called in priority order along with the built-in methods;
  or you may remove the built-in filter and replace with your own.
  Note the following default priorities:
  Tables: 10 for Image and main content; 15, Title text; 20, Excerpt.
  Lists: 10 for Title text; 15, Image and main content; 20, Excerpt.
  
  For example, if you wanted to have list items add the picture first, and
  then the text, you could override the default order, and have the 
  built-in AutoNav functions called in a different order.  Put this in your
  theme's functions.php:

    remove_all_filters('autonav_create_list_item');
    add_filter('autonav_create_list_item', 'an_create_output_picture', 10, 4);
    add_filter('autonav_create_list_item', 'an_create_output_text', 15, 4);
    add_filter('autonav_create_list_item', 'an_create_output_excerpt', 20, 4);

* $html = apply_filters('autonav_create_page_links', $html, $class,
  $total_pages, $cur_page) -- is called in the case of a multi-page
  display.  Again you may wish to append, prepend, or replace.

* $html = apply_filters('autonav_html', $html, $attr);
  Permits you to filter the final HTML which AutoNav generates.

You can hook into any or all of these as in the example below. This
code simply displays the contents of the attributes array, so you can
see how it works:

    function show_the_attrs ($attr) {
      print "foo";
      print "<br><pre>";
      print_r($attr);
      print "</pre><br><hr>";
      return $attr;
    }

    add_filter('autonav_select', 'show_the_attrs', 10, 1);

Here is an example of adding information to the table output; it
appends the (reformatted) date but only to posts. Note that
$pic['page'] is the post/page object from WP_Query.

    function my_create_output_date ($html, $class, $pic, $attr) {
      if (is_object($pic['page'])) {
        if ($pic['page']->post_type == 'post' && 
          strlen($pic['page']->post_date)) {
          $html .= '<p class="' . $class . '-date">' . 
            mysql2date('j M Y', $pic['page']->post_date) . "</p>\n";
        }
      }
      return $html;
    }

    add_filter('autonav_create_table_item', 'my_create_output_date', 18, 4);

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

= Other Recommendations for Accompanying Plugins =

* Pagemash and postmash (see above) rearrange the order of posts and
  pages.

* J. Christopher's Attachments plugin lets you attach anything in
  Wordpress's Media Gallery to any post.  See:
  http://wordpress.org/extend/plugins/attachments

* Dion Hulse's Add From Server plugin makes it easy to upload images
  to your server with FTP and them add them directly into WordPress as
  attachments. In most cases this is now preferable to using AutoNav's
  "Gallery" mode. http://wordpress.org/extend/plugins/add-from-server/

* The Media Tags plugin lets you add tags to attachments. For example,
  if a post has twelve attachments, you could tag three of them with
  'dessert' and then display only those: [autonav display="attached"
  postid="tag:dessert"] See:
  http://wordpress.org/extend/plugins/media-tags/

== Upgrade Notice ==

= 1.4.8 =

When using display="post" you may need to add the nothumb argument:

     display="post,nothumb"

if you want the previous behaviour of titles and no thumbnails.
For display="list" the default is still titles and no thumbnails.

Support addons via filters. "notitle" and similar parameters for
suppressing default behaviors.

== Changelog ==

= 1.4.9b =

* Support category slugs in category__in, category__and,
  category__not_in

= 1.4.9a =

* Images with name "0" were incorrectly being chosen for thumbnails.

= 1.4.8 =
* In display= parameters, permit "no" prefix to suppress
  behavior, e.g.:  display="posts,list,title,nothumb"
* Use <p> tags within table elements, not list items.
* Display=posts supports same include= syntax as display=pages.
* exclude= parameter supports post/page slugs and page paths.
* New argument plain (e.g., display="posts,plain,thumb,notitle")
  places output items inside a <div> tag instead of an unordered list.

= 1.4.7 =
* id= argument is handled the same as postid= argument, for
  compatibility with WP's [gallery] shortcode.
* Support postids category__and, category__in, category__not_in,
  tag__and, tag__in, tag__not_in, tag_slug__and, tag_slug__in
  (see http://codex.wordpress.org/Function_Reference/get_pages)
  and status keyword for standard (or custom) status types.
* Missing-image and other errors displayed inside an 
  autonav-error span element, so their display can be disabled.
* Support custom post type and taxonomy names with dashes
* Additional filters autonav_get_thumbnails, autonav_thumb
* New filters for extensions like NextGEN Gallery thumbnail
  support, and for taxonomy-images plugin.  These are then
  implemented in auxiliary plugins, or theme files.

= 1.4.2 =
* Correct '0' to empty string for default value of post_id.

= 1.4.1 =
* For display="posts", postid="cakes:lemon" will select posts in the
  "cakes" taxonomy if it exists, otherwise it will select posts by the
  "cakes" custom field.
* Output an HTML comment with some information for missing images,
  instead of broken IMG tag.
* Adds filters: autonav_select, autonav_display_select, autonav_html,
  autonav_create_list_item, autonav_create_table_item,
  autonav_create_page_links for use with add_filter().
* Provide link to this Readme file from the Admin screen.

= 1.4.0 =
* Wordpress SVN release of 1.3.9 after several betas

= 1.3.9 =
* Resolves "Incorrect size specified" error immediately after installation,
  by setting default size_* parameters.
* For posts, postid can use custom taxonomies; for pages, postid can be 
  a page's path (e.g., "recipes/desserts" -- NOT merely the slug!), or
  "author:Todd,Mary" or "custom-field-type:value"
* When listing posts, normally the current post is excluded: Use
  'self' to include it.
* Add 'family' parameter to select all children, grandchildren, etc. pages
* Add 'page' parameter to link attached images to the attachment page
* Add 'nolink' parameter for no linking at all
* Additional crop origins at upper-left and top-middle
* subpage_thumb custom field permits paths relative to the WP uploads directory.
* Changed handling of pics_only when display="list"; formerly pics_only
  was forced to 0 for lists, but now it can be specified. If you set pics_only
  to 1 in the admin screen, you must now explicitly override that default
  when invoking [autonav display=list].
* When thumbnail cannot be created, replace output content with text of
  destination URL and a comment about which file could not be created.

= 1.3.8 = 
* Thumbnails were not always cropped when requested
* Handle case of displaying a "thumbnail" that is exactly the size of
  the full-size image.
* Fully support Wordpress sizes (thumb, thumbnail, medium, large),
  user registered sizes, and AutoNav size settings (size_small,
  size_med, size_large).

= 1.3.7 =
* Support orderby="meta:subpage_title" and other custom fields

= 1.3.6 =
* Add subpages-image class to images
* Permit display="posts:foods" to display custom posts of type "foods"
* Added Sharpness factor and image quality level
* Better memory handling, with imagedestroy while resizing images
* Explicitly specifying an image, even if thumbnail-sized or smaller, will work
  (so long as it does not end in a size; "foo.jpg" OK but "foo-64x64.jpg" will be skipped)

= 1.3.5 = 
* Add sharp parameter to use resize instead of resample.

= 1.3.4 =
* Correct handling of order, orderby, and count parameters in various
  combinations of display="posts"
* Add modifier "image" which, used as: [autonav display="posts,image"]
  will create a link to the (thumbnail) image for the post, rather
  than to the post itself.

= 1.3.3 =
* Permit order="desc" on pages as well as posts http://wordpress.org/support/topic/autonav-order-desc?post-1823500

= 1.3.2 =
* Support Jonathan Christopher's Attachments plugin

= 1.3.1 =
* Support random order for pages. Thanks http://wordpress.org/support/profile/thomas_n for the patch.

= 1.3.0 =
* Add 'paged' parameter.  Correction on page selection (formerly, the start and count
  parameters were applied twice, resulting in too few pages being displayed)

= 1.2.8 =
* Plugin activation hook to create default settings, or otherwise provide reasonable
  defaults for new parameters. This should eliminate plugin failures even when admin
  does not go through Settings screen.

= 1.2.7 =
* Ability to select posts by "tag:x", "author:x", or "category:x" where "x" is
  a numeric id or text (tag slug, author name, or category name); or a series
  thereof ("5,7,9" to select posts in category 5, 7, or 9; or "cakes+vanilla" for
  posts tagged with both cakes and vanilla; see query_posts() in codex for details)
  Example: [autonav display="posts" postid="tag:7"]

= 1.2.6 =
* Escaped attribute values on alt=""
* Add background parameter for later support of transparency in PNG images
* Support display=posts parameter, to display posts instead of pages or attached images.
* Alternate text and title text for attached images, as set in admin screens, is used.

= 1.2.4 =
* Handle edge case of no pictures to display

= 1.2.3 =
* Add optional "siblings" parameter, e.g., [autonav display="images,siblings"]
  which will select the current page's siblings (other children of the
  same parent).  Also "self" parameter which when used with "siblings" will
  include the currently displayed page in the list of siblings.

= 1.2.2 =
* Remove superceded v2.9.0 beta functions
* If a page defines the custom field 'subpage_excerpt' or has a manual excerpt
  defined, that will be displayed when the 'excerpt' parameter is included
  with 'display' (e.g., "[autonav display=list,excerpt]" )

= 1.2.1 =
* W3C validation, correct case of incorrect table and row markup nesting
* Caption parameter added (be sure to go thru Settings screen in admin and
  save settings, even if not changed, to permit new parameter)
* Picture 'alt' tag will be title if available

= 1.2.0 =
* Resolve incompatibility with Windows-hosted paths

= 1.1.9 =
* Improve admin screen formatting.  
* Add option for default number of columns.
* Compatibility with 2.9.0beta and 2.9.0rc1

= 1.1.8 =
* Move options under Settings in adminstration screens
* Could not pics_only option unless checked in admin screen
* Images with subpages_thumb were not displayed in some cases

= 1.1.7 =
* Wordpress 2.9: If you select a thumbnail in a page's edit screen,
  that thumbnail will pre-empt the "choose the first attached image" logic,
  although specifying a subpage_thumb custom field still has priority.

= 1.1.6 =
* When listing pages with display=images or display=list and specifying a
  postid= parameter, each item in the postid= list will:
    - if the page has children, list that page's children (with display=list)
      or the children's thumbnails (with display=images)
    - if the page has NO children, list that page or its thumbnail.

= 1.1.5 =
Corrected typo

= 1.1.4 =
* Regard menu_order in attached files list.
* Permit parameter:  order=desc   to display attached files in 
  descending attachment order.
* Corrected handling of images with capitalized extensions (e.g., .JPG)
* postid= parameter accepts multiple values. For example:
    [autonav display=images postid=7,15]
  will display a table consisting of thumbnails linked to the child
  pages, of the pages with ids 7 and 15.

= 1.1.3 =
* Add postid="n" parameter.
* Attached images with a menu_order of less than -100 will not be
  displayed.  This is the "Order" you can set in the media library.

= 1.1.2 =
* Display=attached could result in error; corrected.

= 1.1.1 =
* Resolve resize warnings when PNG images included

= 1.1 =
* Add page exclude parameter

= 1.0 =
* Initial version on wordpress.org

== TODO ==

* Revisit whether autonav_pick_files filter is be called for _each_
  attached file. This needs to happen _after_ all attachments have
  been added, and we need to have the ['menu_order'] of the attachment
  so we can implement:  [autonav display=attached include="#1,#3"]
  However that needs to happen _after_ the handling of start= and
  count= ...   (2011-11-12)

* BUG: the postid="foo:bar" for pages sets meta_tag and value for
  custom field types, but does not yet support custom taxonomies. For
  posts, if the taxonomy "foo" exists, that will be used; otherwise it
  will look for the custom field "foo".  

* Test the creation of $pic_size_info[]['date'] when scanning folders.
  Add code to permit orderby="date" to work with display="/folder"

* Several calculations of pic_full_path -- can we rationalize?

* Ensure that the 'include' parameter for e.g., posts, does not
  conflict with autonav_select_include() as called by the
  autonav_pick_files filter.  Perhaps we need a special flag in $attr?

* Consider: in autonav_wl_shortcode(), use autonav_get_
  postid_modifiers() to ALSO parse the display= parameter.

= Possible future extensions =

* Eventual Version 2.0 plan is for postid is to permit more advanced
  general queries as described here:
  http://ottopress.com/2010/wordpress-3-1-advanced-taxonomy-queries/

* Support S3 and similar plugins. Probably will only work with
  Attachments, and will add custom sizes to the 'sizes' array in the
  attachment's metadata.  Although the metadata gets flushed under
  certain circumstances [when?] this should allow AutoNav to work
  seamlessly with any S3 or similar plugin that keeps attachment
  images in places other than the local filesystem. On hold pending
  better definition, or a sponsor for this project.

* Support creation of thumbnails for PDF and other attachment types,
  possibly through filters and auxiliary plugins.

* [Note 20120111] Other filetypes handled in get_images_from_folder()
  must take care to let actual images take priority regardless of
  which appears first in directory. The switch statement will need to
  take this into account.

