=== Polarsteps Importer ===
Contributors: [DEIN-WP.ORG-BENUTZERNAME]
Tags: polarsteps, import, importer, travel, blog, posts, automation
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Import your Polarsteps trips as WordPress posts, including images, location data, and original publication dates.

----
== Description ==

The **Polarsteps Importer** plugin allows you to automatically import your **Polarsteps "Steps"** (your travel entries) as WordPress posts.

**Core Features:**

*   **Automatic Import**: Uses WordPress cron to fetch new steps at a configurable interval.
*   **Manual Import**: A "Run Now" button to trigger an import whenever you want.
*   **Image Import**: Imports the main photo of a step and sets it as the featured image.
*   **Location Data**: Saves latitude, longitude, location name, and country code as post meta.
*   **Original Dates**: Sets the post publication date to the original creation date of the step.
*   **Secure Token Storage**: Your Polarsteps `remember_token` is encrypted before being saved in the database.
*   **Flexible Post Creation**: Choose the post type, post status (published or draft), and a default category.
*   **Filtering Options**: Ignore steps without a title or exclude specific steps by their ID.
*   **Debug Mode**: Test your connection and see the API data in the logs without creating any posts.

----
== Installation ==

1.  Upload the plugin files to the `/wp-content/plugins/polarsteps-wordpress-import` directory, or install the plugin through the WordPress plugins screen directly.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Go to **Settings → Polarsteps Importer** to configure the plugin.

### Configuration

On the settings page, you need to provide the following:

----
*   **Trip ID**: The unique identifier for your Polarsteps trip.
*   **Remember Token**: Your private token to access the trip data.
*   Adjust the other settings to your needs and click "Save Settings".

### How to find your Trip ID and Remember Token

**Trip ID**

The Trip ID is part of the URL when you view your trip on the Polarsteps website.
*   Example URL: `https://www.polarsteps.com/YourName/1234567-your-awesome-trip`
*   Your Trip ID is: `1234567-your-awesome-trip`

**Remember Token**

The `remember_token` is required to access your private trip data.

1.  Log in to your account on polarsteps.com in your web browser.
2.  Open your browser's developer tools (usually by pressing `F12` or right-clicking and selecting "Inspect").
3.  Go to the **"Application"** tab (in Chrome/Edge) or **"Storage"** tab (in Firefox).
4.  On the left side, expand the **"Cookies"** section and select `https://www.polarsteps.com`.
5.  Find the cookie named `remember_token` in the list.
6.  Copy the long string from the "Value" column. This is your token.

----
== Frequently Asked Questions ==

= What are the settings for? =

*   **Ignore steps without title**: If checked, steps that don't have a custom title on Polarsteps will be skipped. This is useful for filtering out auto-generated "summary" steps.
*   **Ignore Step IDs**: A comma-separated list of Step IDs that you want to exclude from the import (e.g., `12345,67890`). You can find a step's ID in the logs after an import.
*   **Post Type**: Choose what content type your steps should be saved as (e.g., "Post", "Page", or a Custom Post Type).
*   **Category**: If the selected post type supports categories, you can assign a default category to all imported posts.
*   **Post Status**: Decide if imported posts should be **"Published"** immediately or saved as a **"Draft"**.
*   **Update Interval (in hours)**: Defines how often the plugin should automatically check for new steps (e.g., `1` for every hour).
*   **Enable Debug Mode**: If checked, no posts will be created or updated. Instead, the data fetched from the Polarsteps API will be printed to the logs. This is great for testing the connection and finding Step IDs.

= Will this import my old steps? =

Yes. The first time the import runs, it will try to import all steps from your trip that have a description. On subsequent runs, it will only import new steps it hasn't imported before.

= Is my remember_token secure? =

Yes. The token is encrypted using WordPress's built-in encryption functions before it is stored in your database. It is only decrypted right before making an API call.

== Changelog ==

= 1.0.0 =
* Initial release.

