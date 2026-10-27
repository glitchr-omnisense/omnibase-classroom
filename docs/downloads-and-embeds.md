---
title: Downloads and embeds
order: 32
---

# Downloads and embeds

## Signed downloads

A resource's file is downloaded through a signed link (omnisense/omnibase's
`Base\Service\DownloadLinks`): `ResourceController` signs
`classroom_download_file` for `classroom.download_ttl` seconds once
`ResourceVoter` let the visitor through, and checks the signature when the file
is asked for; after a quick order, `QuickOrderFilesListener` signs a link for a
day. The bundle no longer has a DownloadLinks of its own.

## Who may download

`Base\Classroom\Security\ResourceVoter` decides `CLASSROOM_DOWNLOAD` on a `Resource`: an
unpublished one only for `ROLE_ADMIN`; then a free one for anyone, a members' one for
`classroom.members_role` (`ROLE_USER`), a paid one for `ROLE_ADMIN` or whoever holds an
`Entitlement` to it. `classroom_may_download(resource)` asks it in Twig.

## The download, in two steps

1. `/ressources/{slug}/telecharger` (`classroom_download`): a resource without file is a 404;
   refused, an anonymous visitor goes to the sign-in (back here after), a signed-in one back to
   the resource's page with the flash "paid only" or "members only"; allowed, a redirect to
   `classroom_download_file` signed for `classroom.download_ttl` seconds (with the
   entitlement's id, when there is one).
2. `/ressources/fichier/{id}/{filename}` (`classroom_download_file`): an invalid or expired
   signature is a plain 403 (signing in would not help); a file missing from the storage a 404.
   A GET without `Range` counts one download on the resource and on the entitlement. The file
   is sent as an attachment under its saved name - by nginx when `classroom.accel_prefix` is
   set (`X-Accel-Redirect: <prefix>/<path under the storage>`), else by PHP
   (`BinaryFileResponse`, private).

## The spreadsheet preview

`Base\Classroom\Service\SpreadsheetPreview::sheets(Resource)`: for a spreadsheet (xlsx, xls,
xlsm, ods through PhpSpreadsheet, data only; csv read directly - semicolons when the first line
has more of them than commas), the first `classroom.preview_rows` (30) rows of each sheet, empty
rows dropped, with whether there are more; cached a day, until the resource changes. A file that
cannot be read gives nothing.

## What a sequence or a card frames

The "Intégrations" of a sequence or a card (one address per line, "Label |
https://…") are framed by omnisense/omnibase's Twig function `embed_url()`: Canva,
Padlet, YouTube, Vimeo, LearningApps, Genially and Google Slides are read from
the address; any other site is asked for its player (embed/embed, oEmbed) and
framed when it has one, a plain link otherwise. `classroom_embed()` and
`Base\Classroom\Service\Embeds` are gone; `@Classroom/client/_embeds.html.twig`
uses `embed_url()` (`embed.open.other` labels a provider not known by name).

### Behind the cookie answer

A frame brings its provider's cookies (Canva, Padlet, YouTube, Vimeo...). On a
site with omnibase/consent, it waits for the visitor's yes to the `EMBEDS`
feature - the name omnibase/video's platform frames use: the iframe carries
its address in `data-consent-src` and has none until then, and a sentence
(`embed.consent.text`, the provider's name) stands in its place with a button
that opens the detailed section of the cookie panel (`data-consent-open="details"`),
where `EMBEDS` is listed; the link to the provider's page stays under the
frame. Accepted, the frame loads; refused again, it is emptied.

`Compiler\EmbedsConsentPass` adds the feature to the ones every site starts
from (omnibase/consent's `consent.defaults`) - on demand: listed on the pages
that frame something, never a reason to ask a visitor again - with what these
providers were seen to keep on their own domain (Canva's `CDI` and `CL`,
Vimeo's `vuid`, the YouTube player's `yt-remote-*`); its words are
`translations/consent+intl-icu.*.yaml` (`features.embeds.*`,
`storage.embeds_*`). A site lays its own `consent.features.EMBEDS` over it,
field by field (declared there, it is listed on every page unless it says
`on_demand: true`), and its own words over the bundle's; `defaults: false`
leaves it out. It is not put in the site's `consent:` configuration, whose
features are the site's own list (`data-consent-groups`). Without
omnibase/consent the frames load with the page (`classroom_consent()` is null).
