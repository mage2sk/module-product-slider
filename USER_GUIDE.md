# Panth Product Slider -- User Guide

This guide walks store administrators through installing, configuring,
and using the Panth Product Slider extension for Magento 2.

---

## Table of contents

1. [Installation](#1-installation)
2. [Verifying the module is active](#2-verifying-the-module-is-active)
3. [Global configuration](#3-global-configuration)
4. [Creating a slider (Admin CRUD)](#4-creating-a-slider-admin-crud)
5. [Embedding a slider](#5-embedding-a-slider)
6. [Widget insertion](#6-widget-insertion)
7. [Product selection options](#7-product-selection-options)
8. [Layout and styling](#8-layout-and-styling)
9. [Autoplay and animation](#9-autoplay-and-animation)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Installation

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

---

## 2. Verifying the module is active

```bash
bin/magento module:status Panth_ProductSlider
# Module is enabled
```

You should see **Product Slider** under the **Panth Infotech** admin
sidebar menu.

---

## 3. Global configuration

Navigate to **Stores > Configuration > Panth Extensions > Product
Slider**.

| Setting | Default | Description |
|---|---|---|
| Enable Module | Yes | Master on/off switch for all sliders |
| Default Columns (Mobile) | 1 | Columns visible on mobile |
| Default Columns (Tablet) | 2 | Columns visible on tablet |
| Default Columns (Desktop) | 4 | Columns visible on desktop |
| Default Products Per Slider | 8 | Max products shown |

---

## 4. Creating a slider (Admin CRUD)

1. Go to **Panth Infotech > Product Slider > Manage Sliders**
2. Click **Add New Slider**
3. Fill in the General section:
   - **Title** -- displayed above the slider
   - **Identifier** -- unique machine-readable key (e.g., `homepage-bestsellers`)
   - **Enable Slider** -- toggle on/off
   - **Description** -- optional HTML below the heading
4. Expand **Product Selection** to choose products by ID, category,
   SKU, price range, sale-only, or new-products filter
5. Expand **Sorting** to set sort field and direction
6. Expand **Layout** to set responsive column counts, pager, and
   display toggles
7. Expand **Styling** to pick a style preset or customize card shadow
   and hover effect
8. Expand **Animation** to enable autoplay
9. Click **Save** or **Save and Continue Edit**

After saving, the **Usage Guide** panel shows exact embed code for
CMS, Layout XML, and PHTML.

---

## 5. Embedding a slider

### CMS Page / Block

```
{{widget type="Panth\ProductSlider\Block\SliderById" identifier="homepage-bestsellers"}}
```

### Layout XML

```xml
<block class="Panth\ProductSlider\Block\SliderById">
    <arguments>
        <argument name="identifier" xsi:type="string">homepage-bestsellers</argument>
    </arguments>
</block>
```

### PHTML Template

```php
<?= $block->getLayout()
    ->createBlock(\Panth\ProductSlider\Block\SliderById::class)
    ->setData('identifier', 'homepage-bestsellers')
    ->toHtml() ?>
```

---

## 6. Widget insertion

The module also registers two widget types in the Magento Widget
chooser:

1. **Panth Product Slider (Advanced)** -- inline configuration of all
   slider parameters without needing a CRUD entity.
2. **Panth Product Slider (By Identifier)** -- references a CRUD-managed
   slider by its identifier.

Insert either via **Content > Widgets > Add Widget** or the CMS
Page/Block WYSIWYG editor's widget button.

---

## 7. Product selection options

| Option | Description |
|---|---|
| Category IDs | Comma-separated category IDs |
| Product IDs | Comma-separated product IDs (order preserved) |
| Product SKUs | Comma-separated SKUs |
| Sale Products Only | Only show products with active special price |
| New Products Days | Products created within the last N days |
| Price From / To | Min/max price filter |
| Exclude Out of Stock | Hide out-of-stock products |

The admin form includes a **Browse Products** and **Browse Categories**
popup for convenient selection.

---

## 8. Layout and styling

| Setting | Options |
|---|---|
| Columns (Mobile/Tablet/Desktop) | 1--6 |
| Show Pager | Yes / No |
| Show Add to Cart | Yes / No |
| Hide Details | Yes / No |
| Hide Rating | Yes / No |
| Style Preset | Default, Modern, Minimal, Bold, Custom |
| Card Shadow | None, Small, Medium, Large, Extra Large |
| Card Hover Effect | None, Lift, Scale, Both |
| Custom CSS Class | Any CSS class(es) |

---

## 9. Autoplay and animation

| Setting | Default | Description |
|---|---|---|
| Enable Autoplay | No | Auto-advance slides |
| Autoplay Interval | 3000 ms | Time between slide transitions |

Autoplay pauses on mouse hover and resumes on mouse leave.

---

## 10. Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| Slider not rendering | Module disabled | Check Stores > Configuration > Panth Extensions > Product Slider > Enable Module |
| Empty slider | No products match filters | Verify product selection criteria; check stock status |
| Swiper.js not working (Luma) | JS not loaded | Ensure Panth_Core is installed and `default.xml` loads Swiper assets |
| Snap-slider not working (Hyva) | Missing x-snap-slider | Ensure Hyva theme is active and Alpine.js is loaded |

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422
