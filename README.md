# Medusa Accessibility Tools

A free WordPress plugin from [Jelly](https://jellyseo.com/accessibility) that adds a floating accessibility button to any site. It opens the Accessibility Menu, where visitors can:

- Make text larger, from 100% to 150% in five steps
- Switch to a dyslexic friendly font (OpenDyslexic, bundled, nothing loaded from Google)
- Invert colours, with photos and video kept as they are
- Turn on a colour blind mode for protanopia, deuteranopia or tritanopia
- Reset everything

Choices are remembered in the visitor's browser and applied before the page appears. The menu works by keyboard and with screen readers, and links to [jellyseo.com/accessibility](https://jellyseo.com/accessibility).

## Download

Always the latest version:

```
https://github.com/jelly-seo/medusa-accessibility/releases/latest/download/medusa-accessibility-tools.zip
```

Upload it at Plugins > Add New Plugin > Upload Plugin, activate it, then set it up at Settings > Accessibility Tools (Medusa > Accessibility Tools on Medusa sites): button colour, position, distance from the edge, size and whether it shows on phones.

## Updates

Every installed copy checks this repository's latest release twice a day and offers the update on Dashboard > Updates, with the release notes under View details. Nothing else is needed on the sites: no token, no account, no licence key.

## Repository

| Path | What it is |
| --- | --- |
| `medusa-accessibility-tools/` | The plugin, zipped as it stands |
| `tools/package.py` | Builds `dist/medusa-accessibility-tools.zip` |
| `tools/release.py` | Sets the version in the plugin header, constant and `readme.txt`, and adds the notes to `CHANGELOG.md` and `readme.txt` |
| `tools/check.py` | No comments, no em dashes, PHP lint |
| `.github/workflows/release.yml` | Builds the zip and attaches it to the release |

## Releasing

```bash
python3 tools/release.py 1.1.0 "What changed" "Another change"
python3 tools/check.py
git commit -am "Release 1.1.0"
git push origin main
```

Then publish the release on GitHub: Releases > Draft a new release > Choose a tag > type `v1.1.0` > Create new tag on publish > Publish release. The workflow checks the versions match the tag and attaches `medusa-accessibility-tools.zip` a minute or two later. Sites see the update within 12 hours.

Pushing a `v1.1.0` tag from a terminal does the same.

The release must be published, not left as a draft or marked as a pre-release, because sites only read the latest full release.

## Hooks

| Hook | Use |
| --- | --- |
| `medusa_a11y_show` | Return false to hide the button on a request |
| `medusa_a11y_release_url` | Point the updater at another release endpoint, for testing |

## Licence

GPL-2.0-or-later. OpenDyslexic by Abbie Gonzalez is used under the SIL Open Font License 1.1 (`medusa-accessibility-tools/assets/fonts/OFL.txt`).
