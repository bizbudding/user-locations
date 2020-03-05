# Changes

## 1.4.3 (3/5/20)
* Fixed: Undefined variable when using additional hours in each day.

## 1.4.2 (2/20/20)
* Changed: Filter prefix is now consistent with other filters. `userlocations_location_posts_query_args`.

## 1.4.1 (2/20/20)
* Added: `ul_location_posts_query_args` filter to change the location blog query.

## 1.3.3 (10/20/17)
* Fixed: GA code syntax for Locations.

## 1.3.2 (10/9/17)
* Changed: Display location menu on single location blog posts.

## 1.3.1.1 (9/21/17)
* Fixed: Extra spaces before php tag causing issues.

## 1.3.1 (9/21/17)
* Fixed: Location child pages not showing on frontend.

## 1.3.0 (9/19/17)
* Changed: Allow locations to have child (grandchild) pages.
* Changed: Added updater script in plugin so no longer relying on GitHub Updater.

## 1.2.6.1
* Update GitHub URI to point to bizbudding account

## 1.2.6
* Update ul_do_location_posts() function to use location_feed taxo instead of location page author to get posts

## 1.2.5
* Add 'publicly_queryable' => true to location_feed taxonomy, so location specific RSS feeds are now viewable via https://example.com/feed/?location_feed=location_641

## 1.2.4
* Add new Location Info tab for 'Options'
* Add new Location Info field for Google Analytics tracking ID
* Display Google Analytics code on all location content/pages
* Added ul_get_location_id() helper function
* Added ul_get_location_ga_code() display helper function
* Added ul_get_ga_code() display helper function
* Fix flush_rewrite_rules() call upon plugin activation

## 1.2.3
* Remove 'Add Form' button when locations are editing pages
* Allow locations to re-order their pages via Simple Page Ordering plugin
* Add 'ul_contact_details' filter
* Add 'ul_url_display' filter
* Add 'ul_opening_hours_display' filter
* Add 'ul_opening_hours' filter
* Add 'ul_opening_hours_alt' filter

## 1.2.2.1
* Hotfix URL word wrap

## 1.2.2
* Fix widget - hours still displaying if closed every day

## 1.2.1.1
* Hot fix meta title on location parent page

## 1.2.1
* Fix street issue when showing address
* Add meta and og data to <head>

## 1.2.0
* Rebuild widget, including location info display helper functions
* Breaks existing widget instances

## 1.1.10.1
* Hotfix for location author link filter

## 1.1.10
* Fix Locations plural name displaying wrong in breadcrumbs of not logged in
* Filter Yoast breadcrumbs with Location data
* Allow Address Widget to display on blog posts where the post author is a location

## 1.1.9
* Remove Gravity Forms editor form button if logged in as a location

## 1.1.8
* Add support for User Switching plugin
* New User Switching link in Toolbar if user is an admin

## 1.1.7
* Add nav menu support for location_page CPT

## 1.1.6
* location_page CPT has_archive now defaults to true, with a filter to change

## 1.1.5
* Fix load_location_feeds() method failing on some admin pages (FacetWP)

## 1.1.4
* Fix non-location authors being able to see all posts
* Add location fields to Add User form to make it easier when trying to create a user and assign them as a location

## 1.1.3
* Add back location posts to main blog. If a site wants to limit this, it should be on a per site basis

## 1.1.2
* Remove slug metabox if a location user is viewing a parent location page

## 1.1.1
* Add draft and future post status to parent page metabox

## 1.1.0
* Convert location role to custom capabilities
* Location parent pages use core WP edit screen. Remove many acf_form() releated functions
* New ACF location for location_parent_page

## 1.0.5
* Save featured image to main location page
* Add location menu to single posts from locations

## 1.0.4
* More custom experience for managing Publicize via Jetpack

## 1.0.3
* Move Location Settings page/form to a submenu of core Settings
* This allows Publicize to share the same top level menu

## 1.0.2
* Fix widget

## 1.0.1
* Save posts to location_{ID} custom tax term slug

## 1.0.0
* lets do this
