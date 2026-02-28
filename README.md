# REST Simple Extention

Extends public WordPress REST API post type responses with:

- A grouped `taxonomies` field containing terms for registered taxonomies
- `featured_img` metadata with image URLs by size

## Requirements

- WordPress 6.0+
- PHP 7.4+

## Installation

1. Copy the plugin folder to:
	 `wp-content/plugins/rest-simple-extention`
2. Activate **REST Simple Extention** in **Plugins**.

## What it adds to REST responses

For all public post types (except `attachment`), the plugin adds:

- A `taxonomies` field containing taxonomy term names grouped by taxonomy slug.
- A `featured_img` object with:
	- `alt`
	- `full`
	- `large`
	- `medium`
	- `thumbnail`

Example request:

`GET /wp-json/wp/v2/posts`

Example response fragment:

```json
{
	"id": 123,
	"taxonomies": {
		"category": ["News", "Updates"],
		"post_tag": ["Release", "API"]
	},
	"featured_img": {
		"alt": "Hero image",
		"full": "https://example.com/uploads/2026/02/hero.jpg",
		"large": "https://example.com/uploads/2026/02/hero-1024x576.jpg",
		"medium": "https://example.com/uploads/2026/02/hero-300x169.jpg",
		"thumbnail": "https://example.com/uploads/2026/02/hero-150x150.jpg"
	}
}
```

## Filters

Use these filters to customize behavior:

### `rest_extended_default_post_types`

Change the list of public post types that receive extra REST fields.

```php
add_filter( 'rest_extended_default_post_types', function( $post_types ) {
		return array( 'post', 'page' );
} );
```

### `rest_extended_default_taxonomies`

Change which registered taxonomies are included in the `taxonomies` REST field.

The second argument (`$post_types`) lets you conditionally return taxonomies by post type. In this plugin, it is passed as an array containing the current post type during response preparation.

```php
add_filter( 'rest_extended_default_taxonomies', function( $taxonomies, $post_types ) {
		$current_post_type = is_array( $post_types ) && ! empty( $post_types[0] ) ? $post_types[0] : '';

		if ( 'post' === $current_post_type ) {
				return array( 'category', 'post_tag' );
		}

		if ( 'product' === $current_post_type ) {
				return array( 'product_cat', 'product_tag' );
		}

		return array(); // Or return $taxonomies to keep defaults.
}, 10, 2 );
```

## Notes

- Fields are read-only (`update_callback` is `null`).
- If no taxonomy terms/image are present, empty arrays/empty strings are returned where applicable.

## Changelog

### 1.0.0

- Initial production-ready release.
- Added REST field registration for taxonomy terms and featured image metadata.
- Added safer handling for missing image sizes and invalid taxonomy/post data.
