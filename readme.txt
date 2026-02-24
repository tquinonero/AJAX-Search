=== Custom Search by ToniQ ===
Contributors: tquinonero
Tags: ajax, search, posts, taxonomies, custom post types, custom fields
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.0
Stable tag: 2.0.0
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Custom Search by ToniQ is a modern AJAX search plugin for WordPress that lets visitors search posts, custom post types, taxonomies, and custom fields in real time.

It is based on the original "Tsearch" plugin and has been refactored and restyled, keeping the same powerful search capabilities while offering a cleaner UI and improved UX.

== Features ==

* AJAX-powered search results that update as you type.
* Search across multiple post types, taxonomies, and custom fields.
* Settings page to choose which content types and fields to include.
* Option to automatically display the search bar on a specific page (before or after the content).
* Accessible markup and keyboard navigation for the results list.
* Optional voice search button (using the browser Speech Recognition API where available).
* Lightweight, modern UI with responsive styling.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install it via your WordPress dashboard.
2. Activate **Custom Search by ToniQ** from the "Plugins" screen in WordPress.
3. In the admin menu, look for **Custom Search** and open the settings page.
4. Select the post types, taxonomies, and custom fields you want to be searchable.
5. (Optional) Choose a page and position (before/after content) where the search bar should automatically appear.

== Usage ==

There are two main ways to display the search bar:

1. **Automatically on a page**

   Go to the **Custom Search** settings page and choose a page and display position. The search bar will be injected before or after the content on that page.

2. **Shortcode**

   Use one of these shortcodes in any post, page, or widget:

   * `[custom_search_by_toniq]` – primary shortcode.
   * `[ajax_search]` – legacy alias kept for backward compatibility.

== Frequently Asked Questions ==

= How do I customize what gets searched? =

In the WordPress admin, go to **Custom Search**. From there you can select:

* Which post types are included in searches.
* Which taxonomies and terms should be searchable.
* Which custom fields/metadata keys should be searched.

= Does it work with Full Site Editing (FSE) themes? =

Yes. The plugin detects block themes (FSE) and uses a block rendering filter to inject the search bar around the post content. For classic themes, it hooks into `the_content`.

= Is voice search required? =

No. Voice search is optional and only works in browsers that support the Speech Recognition API. If unsupported, the microphone button will simply not function.

= Can I keep using the old [ajax_search] shortcode? =

Yes. The `[ajax_search]` shortcode is still registered as a backward-compatible alias and will render the same search UI as `[custom_search_by_toniq]`.

== Changelog ==

= 2.0.0 =
* Renamed plugin to **Custom Search by ToniQ**.
* Consolidated all logic into `custom-search-by-toniq.php` as the main entry file.
* Refined admin settings page and labels.
* Added a new primary shortcode `[custom_search_by_toniq]` with `[ajax_search]` kept as an alias.
* Restyled the search UI (input, dropdown, microphone button, loading/error states).

= 1.2 =
* Added support for FSE themes.
* Added a dropdown in the settings page to select the page where the results will be shown.
* Added a dropdown in the settings page to select the position of the search box (before content or after content).

= 1.1 =
* Added support for custom fields.
* Added support for custom post types.
* Added support for custom taxonomies.
* Added support for voice search.

= 1.0 =
* Initial release as Tsearch.

== Screenshots ==

1. The Custom Search by ToniQ search box in action.
2. Search results displayed dynamically as you type.
3. Settings page for configuring searchable content and display options.

== License ==

This plugin is licensed under the GPL2 license. See the LICENSE file for more details.
