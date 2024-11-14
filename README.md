# QB Coupons

A WordPress plugin that manages the generation and usage of coupons for annual subscriptions. When a user purchases an annual subscription, the plugin automatically generates 12 monthly coupons that can be used for products in the "Ecological Theme" category.

## Description

QB Coupons integrates with WooCommerce to provide automatic coupon generation and management for annual subscription purchases. Each coupon provides a fixed discount of $4.99 and can be used once for products in the specified category.

### Features

- Automatic generation of 12 monthly coupons upon annual subscription purchase
- User-specific coupon restrictions
- Easy-to-use coupon display interface
- One-click coupon code copying
- Multi-language support (English and Spanish included)
- Category-specific coupon restrictions

## Installation

1. Upload the `qb-coupons` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure WooCommerce is installed and activated
4. Create a product category named "Ecological Theme" ("Tema Ecológico")

## Usage

### Shortcode

Use the following shortcode to display available coupons to logged-in users:
```
[user_coupons]
```

This shortcode will:
- Display all unused coupons for the logged-in user
- Show a copy button for each coupon
- Provide visual feedback when a coupon is copied
- Include instructions for using the coupons

Example placement:
- Add to any page or post using the WordPress editor
- Add to your theme template using:
```html
  `<?php echo do_shortcode('[user_coupons]'); ?>`
```

### File Structure

```
qb-coupons/
├── languages/
│   ├── qb-coupons.pot
│   ├── qb-coupons-es_ES.po
│   └── qb-coupons-es_ES.mo
├── .gitignore
├── README.md
└── qb-coupons.php
```

### Translations

The plugin supports internationalization and includes:
- English (default)
- Spanish (es_ES)

To add new translations:
1. Use the provided POT file as a template
2. Create new PO files for your language
3. Generate corresponding MO files
4. Place them in the `languages` directory

## Support

For bug reports and feature requests, please use the [GitHub issues page](https://github.com/ahvega/qb-coupons/issues).

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## Author

Adalberto H. Vega
- GitHub: [@ahvega](https://github.com/ahvega)
- Email: bertovega@gmail.com

## Changelog

### 1.0.0
- Initial release
- Basic coupon generation functionality
- User interface for displaying available coupons
- Multi-language support (English and Spanish)
