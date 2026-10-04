# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.1.5] - 2026-10-05

### Fixed
- "Sale Products Only" lists only products whose final price is below the regular price, so a special price equal to the regular price no longer puts a product in a sale slider.
- The slider edit form shows Sort By, Sort Direction, Card Shadow and Card Hover Effect again. Card Shadow offers Extra Large and Card Hover Effect offers Lift + Grow and Scale, so values saved by widgets are kept when a slider is saved.
- Saving a slider with empty New Products Days, Price From or Price To stores an empty value instead of 0.
- The slider identifier must start with a letter and may contain letters, digits, "-", "_" and "/"; a duplicate identifier shows a clear message.
- Keyword search in the Manage Sliders grid filters by identifier and title.
- The "Browse Products..." popup lists products again, keeps every selected product in its order while searching, and the selected products table follows that order. The popup close and remove buttons have accessible labels.
- Slider arrows are 44 by 44 pixels on Hyva and Luma, and the Luma Add to Cart button is at least 44 pixels high.
- Luma sliders are announced as carousels with the slider title, and product names use the heading level below the slider heading.
- Product badges use 12 pixel text and darker colours with enough contrast for white text.
- Card corner radius and product name size are printed in pixels, so Luma cards no longer shrink them to 10 pixels.
