# ArtUP visual direction

Extend the existing statistics dashboard design to the entire PHP print-shop application. The user explicitly selected that reference and authorized usability improvements. Keep the exact existing logo; use generated generic product photographs from img/. Never fabricate prices, discounts, delivery promises, reviews, addresses or contact channels.

## Foundations

- Typeface: system-ui, -apple-system, Segoe UI, sans-serif; no external fonts.
- Colors: text #1d2535; muted #586477; background #f4f5f9; border #dce1eb; brand #a81759; pale brand #fff0f6; dark header #171820; keyboard focus #177bba.
- White cards with fine borders, restrained shadows, radius 8-12px. Container 1180px. Typical gaps 16, 20, 24, 32px. Large section spacing 48px.
- Preserve readable table typography and tabular numerals. Main headings 28-38px, section headings 18-24px, body 15-16px.
- Responsive at 850px and 540px; every field usable at 320px. Respect reduced motion and keyboard navigation.

## Scope and desired improvements

Home: useful product introduction with one illustrative hero image, direct catalogue action and three real steps (configure, review price/artwork, confirm and track).
Catalogue: four product cards, images, concise descriptions, working configuration links.
Authentication: labelled fields, autocomplete and clear errors, consistent branded shell.
Products: accessible grouped fields, cm units, optional upload guidance, explicit calculate and add actions, separate price summary when present.
Cart/orders: clear actions, empty states, status labels, table regions with keyboard-accessible horizontal scrolling. Keep deletion confirmation and cancellation workflows.
Admin products: separate sections for types, categories and items; tidy responsive fieldsets.
Statistics: retain its existing metrics, filters, aggregates and semantics; share common typography/header/footer.

## Implementation boundaries

PHP templates and vanilla CSS; no framework migration or dependencies. Keep all server-side validation, permissions, CSRF, field names, actions, links, monetary formulas and database behavior. Use url_app() for assets and navigation. No scripts needed for core workflows. Do not display technical design tooling in customer flows. Generated photographs are illustrative, not a portfolio of completed jobs.
