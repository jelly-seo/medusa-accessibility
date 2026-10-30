=== Medusa Accessibility Tools ===
Contributors: Jelly
Tags: accessibility, dyslexia, colour blind, text size, a11y
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A floating accessibility button: larger text, a dyslexic friendly font, inverted colours and colour blind modes. Free from Jelly.

== Description ==

Medusa Accessibility Tools adds a round button to every page of your site. It opens the Accessibility Menu, where visitors can:

* Make text larger, from 100% to 150% in five steps
* Switch to a dyslexic friendly font (OpenDyslexic, bundled with the plugin, nothing loaded from Google)
* Invert colours, with photos and video kept as they are
* Turn on a colour blind mode for protanopia, deuteranopia or tritanopia, which shifts colours they can't tell apart into ones they can
* Reset everything

Choices are remembered in the visitor's browser and applied before the page appears, so there's no flash as they move between pages. The menu works fully by keyboard and with screen readers.

More about web accessibility: https://jellyseo.com/accessibility

== Installation ==

1. Download medusa-accessibility-tools.zip from https://jellyseo.com/accessibility
2. In WordPress, go to Plugins > Add New Plugin > Upload Plugin, choose the zip and click Install Now
3. Activate the plugin
4. Go to Settings > Accessibility Tools to choose the button colour, position, distance from the edge, size and whether it shows on phones

Updates appear on Dashboard > Updates like any other plugin.

== Frequently Asked Questions ==

= Does it work with my theme? =

It works with any WordPress theme. With a theme that has dark, primary or accent colours in its palette, such as any Medusa theme by Jelly, the button can use them. Otherwise choose a custom colour.

= Does it make my site compliant with accessibility law? =

No plugin can do that on its own. It gives visitors useful controls, but an accessible site also needs good structure, alt text, colour contrast, captions and keyboard support built in.

= Can I hide the button on certain pages? =

Yes. Return false from the medusa_a11y_show filter on the requests where it shouldn't appear.

== Credits ==

OpenDyslexic by Abbie Gonzalez, used under the SIL Open Font License 1.1 (assets/fonts/OFL.txt).

== Changelog ==

= 1.0.0 - 2026-10-30 =

- First public release
- Floating button opening the Accessibility Menu: text size from 100% to 150%, dyslexic friendly font, invert colours, colour blind modes for protanopia, deuteranopia and tritanopia, and reset
- Choices remembered in the visitor's browser and applied before the page appears
- Settings for button colour, position, distance from the edge, size and showing on phones
- Learn more link to jellyseo.com/accessibility
- Updates from GitHub releases on Dashboard > Updates, with release notes in View details
