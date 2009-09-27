=== AutoNav Graphical Navigation and Gallery Plugin ===
Contributors: wlindley
Donate link: http://www.wlindley.com/website/autonav/
Tags: pages, navigation, widget
Requires at least: 2.8
Tested up to: 2.8.4
Stable tag: trunk

Plugin has two modes. In navigation mode: Creates a list or table of the current page's child pages. Tables are composed of linked thumbnail pictures based a custom field in each child page, or the child page's attached picture. In gallery mode: Creates one or more tables of linked thumbnail pictures based on the current page's attachments, or on specified directories of picture files under the uploads directory.

== Description ==

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

Parameters not specified will be taken from the values set in the WordPress admin panel.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Create the autonav-wl directory in the `/wp-content/plugins/` directory,
   and place the plugin files there.
2. Activate the plugin through the administration menus in Wordpress.
3. Configure the plugin under Settings in the Wordpress administration menu.

== Frequently Asked Questions ==

= What CSS classes does this plugin create? =	    

In navigation mode, when a list is selected:

   <ul> elements have class: subpages-list
   <li> elements have class: subpages-item

In table modes:

   <table> elements: subpages-table
   <tr> elements: subpages-row
   <td> elements: subpages-cell
   <p> elements inside each <td>: subpages-text

== Changelog ==

= 1.0 =
* Initial version on wordpress.org

