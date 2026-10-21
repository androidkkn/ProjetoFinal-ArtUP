# Extractable components

## SiteHeader
- Source: include/header.php
- Category: layout
- Description: Logo, profile-aware navigation and CSRF-protected logout form.
- Extractable props: activeItem, isLoggedIn, isAdmin, homeHref, catalogueHref, ordersHref, cartHref, adminHref.
- Hardcoded: existing ArtUP logo, Portuguese menu labels, system typography, dark background.

## AdminNavigation
- Source: include/HeaderAdmin.php
- Category: layout
- Description: Statistics, orders and products tabs.
- Extractable props: activeItem, statisticsHref, ordersHref, productsHref.
- Hardcoded: labels and current-page underline.

## SiteFooter
- Source: include/footer.php
- Category: layout
- Description: Dark shared footer with ArtUP name and copyright; implemented by visual integration.
- Extractable props: isLoggedIn, homeHref, catalogueHref.
- Hardcoded: ArtUP identity and concise print-shop description.

## ProductForm
- Source: include/form_produto.php
- Category: basic
- Description: Server-rendered configuration, upload and price preview. Keep actual implementation in PHP.
- Extractable props: categoryId, hasArtwork, hasPreview.
- Hardcoded: field labels, cm unit, upload formats.
