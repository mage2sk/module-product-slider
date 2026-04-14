# Panth Product Slider

[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange)]()
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue)]()
[![License: Proprietary](https://img.shields.io/badge/License-Proprietary-red)]()

**Advanced Product Slider widget** for Magento 2 with full CRUD admin,
responsive column controls, style presets, product badges, autoplay,
and dual-theme support (Hyva snap-slider + Luma Swiper.js).

Create unlimited product sliders from the admin panel and embed them
anywhere via CMS widget, Layout XML, or PHP block call.

---

## Features

- **Admin CRUD** -- create, edit, duplicate, and delete sliders from
  a dedicated admin grid (Panth Infotech > Product Slider > Manage
  Sliders).
- **Flexible product selection** -- filter by category IDs, product
  IDs, SKUs, price range, sale products only, new products (last N
  days), and exclude out-of-stock items.
- **Responsive columns** -- separate mobile / tablet / desktop column
  counts with Tailwind-based snap-slider breakpoints (Hyva) or
  Swiper.js breakpoints (Luma).
- **Style presets** -- Default, Modern, Minimal, Bold, or Custom.
  Per-slider card shadow, hover effect, and custom CSS class.
- **Product badges** -- sale percentage, "New", "Low Stock", and
  "Featured" badges with configurable position.
- **Autoplay** -- optional auto-advance with configurable interval
  and pause-on-hover.
- **Dual-theme rendering** -- Hyva uses native `snap-slider` with
  `ProductListItem` view model; Luma uses Swiper.js (bundled via
  Panth_Core).
- **Widget + Layout XML + PHTML** -- three ways to embed any slider.
- **Arrow navigation** -- configurable arrow colors, size, and
  position via system configuration.

---

## Installation

### Composer (recommended)

```bash
composer require mage2kishan/module-product-slider
bin/magento module:enable Panth_ProductSlider
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Manual zip

1. Download the extension package zip
2. Extract to `app/code/Panth/ProductSlider`
3. Run the same `module:enable ... cache:flush` commands above

> **Note:** This module requires `Panth_Core` (mage2kishan/module-core).
> Composer will install it automatically.

---

## Requirements

| | Required |
|---|---|
| Magento | 2.4.4 -- 2.4.8 (Open Source / Commerce / Cloud) |
| PHP | 8.1 / 8.2 / 8.3 / 8.4 |
| Panth_Core | ^1.0 |

---

## Configuration

Open **Stores > Configuration > Panth Extensions > Product Slider**.

| Setting | Default | Description |
|---|---|---|
| Enable Module | Yes | Enable or disable all product sliders globally |
| Default Columns (Mobile) | 1 | Default visible columns on mobile |
| Default Columns (Tablet) | 2 | Default visible columns on tablet |
| Default Columns (Desktop) | 4 | Default visible columns on desktop |
| Default Products Per Slider | 8 | Default number of products |

Arrow colors and product card styling are managed via the theme
configuration file at
`app/design/frontend/Panth/Infotech/web/tailwind/theme-config.json`.

---

## Support

| Channel | Contact |
|---|---|
| Email | kishansavaliyakb@gmail.com |
| Website | https://kishansavaliya.com |
| WhatsApp | +91 84012 70422 |

---

## License

Proprietary -- see `LICENSE.txt`. Distribution is restricted to the
Adobe Commerce Marketplace.

---

## About the developer

Built and maintained by **Kishan Savaliya** at **Panth Infotech** --
https://kishansavaliya.com. High-quality, security-focused Magento 2
extensions and themes for both Hyva and Luma storefronts.
