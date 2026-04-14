# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] -- Initial release

### Added
- **Admin CRUD** for product sliders -- create, edit, and delete
  sliders from a dedicated admin grid under Panth Infotech > Product
  Slider > Manage Sliders.
- **Flexible product selection** -- filter by category IDs, product
  IDs, SKUs, price range, sale-only, new products (last N days), and
  exclude out-of-stock.
- **Responsive columns** -- separate mobile / tablet / desktop column
  counts with Tailwind snap-slider breakpoints (Hyva) or Swiper.js
  breakpoints (Luma).
- **Style presets** -- Default, Modern, Minimal, Bold, Custom. Each
  slider can override card shadow, hover effect, and custom CSS class.
- **Product badges** -- sale percentage, "New", "Low Stock", and
  "Featured" badges with configurable position (top-left, top-right,
  bottom-left, bottom-right).
- **Autoplay** -- optional auto-advance with configurable interval
  and pause-on-hover.
- **Dual-theme rendering** -- Hyva template uses native snap-slider
  with ProductListItem view model; Luma template uses Swiper.js
  carousel bundled via Panth_Core.
- **Three embed methods** -- CMS widget directive, Layout XML block,
  and direct PHTML block call.
- **Widget registration** -- two widget types in Magento Widget
  chooser: Advanced (inline config) and By Identifier (CRUD entity).
- **Admin product/category chooser** -- modal popups with search,
  select-all, and pill-based selection display.
- **System configuration** under Stores > Configuration > Panth
  Extensions > Product Slider for global defaults.
- **Arrow navigation** -- configurable colors, size, and position.
- **Usage guide panel** -- after saving a slider, the edit form shows
  exact embed code for CMS, Layout XML, and PHTML.

### Compatibility
- Magento Open Source / Commerce / Cloud 2.4.4 -- 2.4.8
- PHP 8.1, 8.2, 8.3, 8.4

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422
