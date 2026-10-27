---
title: The shop
order: 33
---

# The shop

With omnibase/marketplace, a paid resource is sold as a `Product\ResourceOffer`.

## Putting a resource on sale

The resource's **Mettre en vente** (`/admin/resources/{entityId}/sell`) finds its offer, or makes
one in the first store - titled and described as the resource, a draft - and sets the resource
`paid`; then opens the offer's screen (`ResourceOfferCrudController`, omnibase/marketplace's
product screen plus the resource) for its price and publication.

## Selling

`/boutique` (`classroom_shop`, loaded only with omnibase/marketplace's `QuickOrder`) lists the
offers for sale whose resource is published and has a file, newest first, sold with the
marketplace's **quick order**: an e-mail address, the payment, no account, no basket. A signed-in
member buys in their own name.

## Delivering

- `EventSubscriber\OrderPaidSubscriber` (on `OrderPaidEvent`): for each item of the order that is
  a resource offer, an `Entitlement` for the customer (one per resource and buyer: a webhook and
  a redirect confirming the same order write it once); then a mail (`@Classroom/email/files.html.twig`,
  from `classroom.from_email`) with the link of the order's page (`QuickOrder::doneUrl()`). An
  offer without resource is logged as an error; a mail that fails is logged, never a reason to
  undo the confirmation.
- `EventSubscriber\QuickOrderFilesListener` (on `QuickOrderDoneEvent`): the quick order's page,
  reached by its own signed link, renders `@Classroom/client/thanks.html.twig` with the files -
  once paid, each behind a signed download link valid a day.

Both are loaded only with omnibase/marketplace (and its quick order for the second).
