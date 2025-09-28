=== Polarsteps Importer ===
Contributors: Kaj-Sören Mossdorf
Tags: polarsteps, import, travel, blog, posts, images
Requires at least: 5.0
Tested up to: 6.0
Stable tag: 1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Import your Polarsteps trips as WordPress posts, including images, location data, and original publication dates.

== Description ==

The Polarsteps Importer plugin provides a seamless way to transfer your travel stories from Polarsteps directly into your WordPress site. Each step of your journey can be imported as a separate post, preserving your content, images, and location data. This is perfect for travel bloggers who want to own their content and display it on their own website. This plugin is part of the https://rueckenwin.de roadtrip along the panamericana. You also find us on Youtube (https://www.youtube.com/@rueckenwinde) and Instagram (https://www.instagram.com/rueckenwin.de/) If you want to support this plugin, consider donating to us!

= Features =

*   **Automatic & Manual Import:** Set up a recurring schedule to automatically check for new steps, or trigger an import manually at any time.
*   **Detailed Post Creation:** Each step is converted into a WordPress post, using the original creation date, title, and description.
*   **Flexible Post Settings:** Choose the post type (e.g., `post`, `page`, or a custom post type) and the post status (e.g., `draft` or `publish`) for imported content.
*   **Advanced Image Handling:**
    *   Disable image imports completely for text-only content.
    *   Choose to embed images individually within the post content or append them as a WordPress gallery.
    *   Images are intelligently named based on the post title for better SEO.
*   **Smart Categorization:**
    *   Assign all imported posts to a default category.
    *   Optionally, use the location detail (e.g., "Paris, France") from your step as a category, which will be created automatically if it doesn't exist.
*   **Leaflet Map Integration:** If the Leaflet Map plugin is active, you can automatically add an interactive map with a marker for the step's location at the end of each post.
*   **Fine-Grained Control:**
    *   Ignore specific steps by providing a comma-separated list of their IDs.
    *   Optionally skip importing steps that do not have a title.
    *   Limit the number of steps imported in a single run to prevent server timeouts.
*   **Secure:** Your Polarsteps "Remember Token" is stored encrypted in the database.
*   **Debug Mode:** A built-in logger helps you troubleshoot any issues with the API connection or import process.

== Installation ==

1.  Upload the `polarsteps-wordpress-import` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to **Settings > Polarsteps Importer** to configure the plugin.

= How to Use =

1.  **Find your Trip ID and Remember Token:**
    *   **Trip ID:** Navigate to your trip on the Polarsteps website. The ID is the number in the URL (e.g., `https://www.polarsteps.com/YourUsername/1234567-your-trip-name`). The Trip ID is `1234567`.
    *   **Remember Token:** Use your browser's developer tools to inspect the cookies while you are logged into the Polarsteps website. Find the cookie named `remember_token` and copy its value.
2.  **Configure the Settings:**
    *   Go to **Settings > Polarsteps Importer**.
    *   Enter your **Trip ID** and **Remember Token**.
    *   Customize the other settings to fit your needs. See the "Settings Explained" section below for details on each option.
    *   Click **Save Settings**. The first automatic import will be scheduled if not already.
3.  **Manual Import:**
    *   To run an import immediately, click the **Import Now** button on the settings page.

== Settings Explained ==

*   **Trip ID**: The unique identifier for your Polarsteps trip. This is required.
*   **Remember Token**: Your private access token, which is necessary to import private trips. The token is stored securely encrypted in the database.
*   **Update Interval (in hours)**: How often the plugin should automatically check for new steps. For example, setting this to `1` will check every hour.
*   **Max Steps per Run**: The maximum number of new steps to import in a single run. This is useful for preventing server timeouts on shared hosting or with very large trips.
*   **Ignore Step IDs**: A comma-separated list of specific Step IDs you wish to exclude from the import. You can find a step's ID in the debug logs.
*   **Ignore steps without title**: If checked, steps that do not have a custom title on Polarsteps will be skipped. This is useful for filtering out auto-generated "summary" steps.
*   **Disable Image Import**: Check this box to prevent any images from being imported. Only the text content of your steps will be imported.
*   **Image Import Mode**: Choose how images are handled. This setting is hidden if image import is disabled.
    *   `Append as gallery shortcode`: All images from a step are added to the Media Library and displayed as a WordPress gallery at the end of the post.
    *   `Embed individually in content`: Each image is added to the Media Library and inserted directly into the post content.
*   **Post Type**: The content type for your imported steps. By default, this is `Post`, but you can choose `Page` or any other public custom post type.
*   **Post Status**: The status of newly imported posts. Choose `Published` to make them live immediately, or `Draft` to review them before publishing.
*   **Category**: Assign a default category to all imported posts. This only applies to post types that support categories.
*   **Use location detail as category**: If checked, the plugin will use the location detail from your step (e.g., "Paris, France") as a category. This category will be created if it doesn't exist and is assigned in addition to the default category above.
*   **Add Leaflet Map**: If you have the "Leaflet Map" plugin installed, this option will automatically add an interactive map with the step's location to the end of each imported post.
*   **Enable Debug Mode**: A safe mode for testing. When enabled, the plugin will log the data it receives from the Polarsteps API but will not create or modify any posts.

== Frequently Asked Questions ==

= Does this work with private trips? =

Yes, as long as you provide a valid `remember_token` from a logged-in session that has access to the trip, the plugin can fetch the data.

= Where can I find the logs? =

The debug logs are displayed at the bottom of the **Settings > Polarsteps Importer** page.

== Changelog ==

= 1.1 =
*   Feature: Added setting to disable image import and hide related options.
*   Feature: Added setting to use location detail as a category.
*   Feature: Added integration with "Leaflet Map" plugin to display a map.
*   Improvement: Renamed uploaded images based on post title for better SEO.
*   Improvement: Cleaned up the settings page layout.
*   Improvement: Added detailed explanations for all settings in the readme.txt file.
*   Fix: Major overhaul of the cron job logic for better stability and predictability.
*   Fix: Manual import now uses a dedicated, separate cron hook to avoid conflicts.
*   Fix: Ignored steps where `is_deleted` is true.

= 1.0 =
* Initial release.
