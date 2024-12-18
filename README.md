# YITH Product Add-Ons GraphQL

**A WordPress plugin to extend GraphQL functionality by exposing YITH Product Add-Ons and Extra Options in your GraphQL API.**

## Features

- Fetch YITH Add-Ons associated with products or product categories via GraphQL.
- Provides detailed information about add-on blocks, including titles, descriptions, and options.
- Supports product-specific, category-specific, and global configurations.

## Installation

1. Download the plugin files and place them in the `wp-content/plugins/yith-product-addons-graphql` directory.
2. Log in to your WordPress admin dashboard.
3. Navigate to `Plugins > Installed Plugins` and activate the **YITH Product Add-Ons GraphQL** plugin.

## Usage

### Query Example

Use the following GraphQL query to fetch YITH Add-Ons for a specific product:

```graphql
{
  product(id: "<PRODUCT_ID>", idType: DATABASE_ID) {
    id
    name
    yithAddOns {
      name
      priority
      addons {
        title
        description
        options {
          label
          price
          image
          tooltip
        }
      }
    }
  }
}
```

### Query Response Example

```json
{
  "data": {
    "product": {
      "id": "cG9zdDoyMzQ1Ng==",
      "name": "Gaming Laptop Pro 15",
      "yithAddOns": [
        {
          "name": "Performance Upgrades",
          "priority": 1,
          "addons": [
            {
              "title": "RAM Options",
              "description": "Choose the amount of RAM",
              "options": [
                {
                  "label": "16GB DDR4",
                  "price": "50",
                  "image": "http://example.com/images/16gb-ddr4.jpg",
                  "tooltip": "Recommended for gaming"
                },
                {
                  "label": "32GB DDR4",
                  "price": "100",
                  "image": "http://example.com/images/32gb-ddr4.jpg",
                  "tooltip": "Great for multitasking"
                }
              ]
            },
            {
              "title": "Storage",
              "description": "Select your storage type",
              "options": [
                {
                  "label": "512GB SSD",
                  "price": "75",
                  "image": "http://example.com/images/512gb-ssd.jpg",
                  "tooltip": "Fast and efficient"
                },
                {
                  "label": "1TB SSD",
                  "price": "150",
                  "image": "http://example.com/images/1tb-ssd.jpg",
                  "tooltip": "Best value for performance"
                }
              ]
            }
          ]
        },
        {
          "name": "Warranty Options",
          "priority": 2,
          "addons": [
            {
              "title": "Warranty",
              "description": "Extend your product warranty",
              "options": [
                {
                  "label": "1 Year",
                  "price": "20",
                  "image": "",
                  "tooltip": ""
                },
                {
                  "label": "3 Years",
                  "price": "50",
                  "image": "",
                  "tooltip": "Best for long-term use"
                }
              ]
            }
          ]
        }
      ]
    }
  }
}
```

## How It Works

- The plugin integrates with GraphQL using the `graphql_register_types` action.
- It queries the `yith_wapo_blocks` and `yith_wapo_addons` tables in your WordPress database to retrieve YITH Add-On data.
- Supports logic for `all products`, `specific products`, and `categories` to determine valid add-on blocks for the requested product.

## Requirements

- WordPress 5.0 or higher
- WooCommerce installed and active
- YITH WooCommerce Product Add-Ons & Extra Options plugin
- WPGraphQL plugin installed and active

## Developer Notes

- The plugin resolves add-ons dynamically based on product and category associations.
- The `options` field in the response includes label, price, image, and tooltip for each add-on option.

## Contributing

We welcome contributions from the community! To contribute:

1. Fork this repository.
2. Create a new branch: `git checkout -b feature/your-feature-name`
3. Commit your changes: `git commit -m 'Add some feature'`
4. Push to the branch: `git push origin feature/your-feature-name`
5. Open a pull request.

## Support

For any issues or feature requests, please open an issue on the [GitHub Issues page](https://github.com/your-repo/issues).

## License

This plugin is open-source software licensed under the [MIT License](LICENSE).

---

**Author:** Andrei Rad  
**Email:** [andrei@age-one.com](mailto:andrei@age-one.com)
