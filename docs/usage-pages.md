---
title: The pages
order: 30
---

# The pages

| Route | Path | Sitemap | Page |
|---|---|---|---|
| `classroom_index` | `/classe` | 0.8, weekly | the cycles, subjects and purposes, six latest sequences, six cards of the fan |
| `classroom_level` | `/classe/{level}` or `/classe/{level}/{subject}` | | a level, and a subject |
| `classroom_subject` | `/matiere/{subject}` | | a subject |
| `classroom_purpose` | `/pour-la-classe/{purpose}` | | a purpose |
| `classroom_sequences` | `/sequences` | 0.7, weekly | the sequences alone |
| `classroom_sequence` | `/sequences/{slug}` | | a sequence, its sessions, four related ones of its subjects |
| `classroom_cards` | `/cartes` | 0.7, weekly | the cards alone |
| `classroom_card` | `/cartes/{slug}` | | a card, the previous and the next of the fan |
| `classroom_resources` | `/ressources` | 0.7, weekly | the resources alone |
| `classroom_resource` | `/ressources/{slug}` | | a resource, its preview, its offer when for sale |
| `classroom_download` | `/ressources/{slug}/telecharger` | | the download's first step |
| `classroom_download_file` | `/ressources/fichier/{id}/{filename}` | | the signed file |
| `classroom_search` | `/rechercher?q=` | | sequences, cards and resources holding the term (3 characters at least) |
| `classroom_shop` | `/boutique` | | the resources for sale ([The shop](usage-shop.md)) |

An unknown slug is a 404.

## The listings

The level, subject, purpose, sequences, cards and resources pages are one listing
(`@Classroom/client/listing.html.twig`), filtered by the taxa in the path (all required):

| Query | |
|---|---|
| `?periode=p1` ... `p5` | the sequences of that period |
| `?voir=all`, `sequences`, `cards`, `resources` | what is shown; `all` (the taxa pages) shows the sequences of the page, 8 cards and 8 resources |
| `?page=` | the sequences' page, `classroom.per_page` (24) a page |

The page gets the level, subject and purpose chosen, every level, subject and purpose (the
pills), the periods and the counts for the pager.
