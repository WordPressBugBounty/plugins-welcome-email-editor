=== Swift SMTP (formerly Welcome Email Editor) ===
Contributors: davidvongries
Tags: smtp, wp mail smtp, email log, welcome email, mailjet
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free & simple SMTP plugin: send WordPress emails through your SMTP server or Mailjet, log every email and customize the welcome emails.

== Description ==

**Swift SMTP** is a free & simple SMTP plugin for WordPress. Are emails from your website landing in spam or not arriving at all? Send them through your own SMTP server or the Mailjet API, keep a log of every email and customize the emails WordPress sends to new users.

== Why choose Swift SMTP? ==

= 📤 Reliable email delivery =
Send all WordPress emails through your own SMTP server, with SSL or TLS encryption and login, or through the Mailjet API.

* Set the "From" email address and name, and force them for all emails
* Send emails as plain text or HTML
* Works with any SMTP server: your host, Gmail, Outlook, Mailjet, Brevo, SendGrid & more
* Send a test email to check your settings

= 💾 Email logging =
Keep a record of every email your website sends. See the subject, sender, recipient and status of each email, search the logs and filter them by status. An overview shows how many emails were sent and how many failed in the last 7 and 30 days.

= 📨 Custom welcome emails =
When a user signs up or is added to your website, WordPress emails the new user and the site admin. Swift SMTP lets you change those emails.

* Change the subject & content
* Use template tags like the user's name, username or the link to set a password
* Add an attachment
* Set the "Reply-To" email address & name and add custom headers
* Send the admin notification to additional users

= 🔐 Custom reset password email =
Change the subject & content of the WordPress **Forgot Password** email.

= ✨ Simple & lightweight =
One clean settings page, no upsells, no bloat.

== How to use ==

1. Go to *Swift SMTP → SMTP Settings*.
2. Choose how to send emails: *SMTP* or *Mailjet API*.
3. Enter your SMTP host, encryption, port, username and password (or your Mailjet API keys) and click *Save Changes*.
4. Send a test email from the box on the right to check that everything works (it saves your settings first).
5. Optional: turn on *Email Logging* and customize the emails under *Welcome Email Editor*.

== What's next? ==

If you like Swift SMTP, make sure to check out our other products:

* **[Ultimate Dashboard](https://ultimatedashboard.io/?utm_source=weed&utm_medium=repository&utm_campaign=udb)** - The #1 WordPress plugin to customize your WordPress dashboard and admin area.
* **[Page Builder Framework](https://wp-pagebuilderframework.com/?utm_source=weed&utm_medium=repository&utm_campaign=wpbf)** - A fast & minimalistic WordPress theme designed for the new WordPress era.
* **[Better Admin Bar](https://betteradminbar.com/?utm_source=weed&utm_medium=repository&utm_campaign=bab)** - The plugin to make your clients enjoy WordPress. It replaces the default admin bar to provide the best possible user experience when editing & navigating a website.
* **[WP Video Popup](https://wp-video-popup.com/?utm_source=weed&utm_medium=repository&utm_campaign=wp_video_popup)** - Add a YouTube, Vimeo or Rumble video popup to WordPress. Lightweight, privacy-friendly lightbox that works with any page builder.

== External services ==

When *Mailjet API* is selected as the mailer, every email your website sends (recipients, subject, content and attachments) is sent to Mailjet's API (https://api.mailjet.com) to be delivered. Nothing is sent to Mailjet otherwise. Mailjet: [Terms](https://www.mailjet.com/legal/terms/), [Privacy](https://www.mailjet.com/legal/privacy-policy/).

When SMTP is used, emails are sent to the SMTP server you enter in the settings.

== Installation ==
1. In your WordPress admin, go to *Plugins → Add New Plugin* and search for "Swift SMTP".
2. Click *Install Now*, then *Activate*.
3. Go to *Swift SMTP* in the admin menu to set up your SMTP server.

== Frequently Asked Questions ==

= Why are my WordPress emails going to spam? =
By default, WordPress sends emails through your web server, which email providers often don't trust. Sending them through a real SMTP server or Mailjet, with a "From" address on your own domain, fixes that in most cases.

= Which SMTP settings do I need? =
Your email provider lists them, usually as "SMTP server" or "outgoing mail server". Common ports are 465 for SSL and 587 for TLS. Swift SMTP fills in the usual port when you pick the encryption.

= My SMTP server doesn't need a login. Does that work? =
Yes. Leave the username and password empty.

= Is the email log stored on my website? =
Yes. The logs are saved in your WordPress database and are only visible to administrators. Logs older than 60 days are deleted automatically; you can change that under *Swift SMTP → Email Logging*.

= Why is the password not included in the welcome email? =
Since WordPress 4.3, the password is no longer sent by email. Instead, the user gets a link to set a password. Use the `[reset_pass_url]` tag for that link.

= What happens to my settings when I delete the plugin? =
They are kept, unless you turn on *Remove on Uninstall* under *Swift SMTP → Other*. Deactivating the plugin never deletes anything.

== Screenshots ==
1. Swift SMTP settings

== Changelog ==

= 7.0 | November 12, 2026 =
* New: Redesigned settings page.
* New: Email logs open in a clean, read-only view that shows the message.
* New: Email logs overview: sent vs. failed emails in the last 7 and 30 days.
* New: Old email logs are deleted automatically after 60 days (change it under Email Logging, 0 keeps them forever).
* Tweak: The test email buttons save your settings first, so you always test what's on the screen.
* Tweak: Saved SMTP passwords and Mailjet secret keys no longer appear in the page source.
* Tweak: The Mailjet API now uses the "From" settings and the content type the same way SMTP does.
* Tweak: SMTP servers without a login now work.
* Tweak: The test email box follows the selected mailer.
* Tweak: Deactivating the plugin no longer deletes the settings when "Remove on Uninstall" is on. Data is now removed only when the plugin is deleted.
* Tweak: Hidden passwords in the [post_data] and [custom_fields] tags.
* Tweak: Security improvements.
* Tweak: Requires PHP 7.4 and WordPress 5.0.
* Tweak: Tested up to WordPress 7.1.
* Fixed: A "%" in a custom welcome email subject broke user registration.
* Fixed: The test of the admin notification email was also sent to the additional recipients.
* Fixed: SMTP passwords with special characters could get changed when saving.
* Fixed: The [bp_custom_fields] and [post_data] tags showed the wrong data.
* Fixed: The welcome email attachment didn't work when WordPress is installed in a subfolder.
* Fixed: A failed Mailjet API request was logged as failed even when the email was then sent.
= 6.3 | December 1, 2025 =
* New: Mailjet integration
= 6.2.2 | October 30, 2025 =
* Fixed: Translation notice issue
* Fixed: Added missing data sanitization
= 6.2.1 | January 4, 2025 =
* New: Email logs are now filterable
* Fixed: Broken reset password link
= 6.2 | September 3, 2024 =
* New: Email Logging
* Tweak: Tested up to WordPress 6.6
= 6.1.2 | May 10, 2024 =
* Tweak: Tested up to WordPress 6.5
= 6.1.1 | January 8, 2024 =
* New: Setting to force sender name & email
* Tweak: Add additional check to test email to be able to skip authentification
= 6.1 | January 8, 2024 =
* Tweak: We are now using Plain Text instead of HTML as the default content type for the welcome emails & smtp settings as this better aligns with WordPress' core default emails.
= 6.0.1 | January 3, 2024 =
* New: [logged_in] & [not_logged_in] tags for Welcome Email Editor
* Tweak: Visual feedback when sending test emails in Welcome Email Editor
* Tweak: Make sure the Welcome Emails mimick WordPress' default emails
* Tweak: Improved email template when triggering a test email from the SMTP settings tab
* Fixed: Multiple recipients didn't work in Welcome Email Editor
= 6.0 | December 30, 2023 =
* New: **Welcome Email Editor is now Swift SMTP!** We've revamped Welcome Email Editor to bring you an easy-to-use & free SMTP plugin for WordPress!
= 5.0.7 | December 12, 2023 =
* Fixed: Minor security issue
= 5.0.6 | December 1, 2023 =
* Tweak: Tested up to WordPress 6.4
* Fixed: Security issue
= 5.0.5 | December 7, 2022 =
* Tweak: Tested up to WordPress 6.1
= 5.0.4 | May 11, 2022 =
* Tweak: Replace [reset_url] with [reset_pass_url] to keep things uniform
* Fixed: Some links were not rendered properly in some cases if Mail Content Type was set to HTML
* Fixed: Wrong textdomains
= 5.0.3 | April 27, 2022 =
* Fixed: Wrong link target in the recommended section
= 5.0.2 | March 2, 2022 =
* Tweak: Tested up to 5.9
* Tweak: Updated plugin description
= 5.0.1 | June 16, 2021 =
* Fixed: Textdomain issue causing is_readable() warning
= 5.0 | May 22, 2021 =
* New: Hey there, David here :) We have taken over the Welcome Email Editor project from our friend Sean Barton. This is our initial release - Apart from some minor improvements, no new features were added. With this release we have reviewed & rewritten the codebase from ground up and greatly improved the settings page design. Stay tuned for more.
