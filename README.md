# REST Simple Extention

Extends public WordPress REST API post type responses with:

- Taxonomy term arrays (defaults to `category`)
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

- A field per configured taxonomy containing an array of term names.
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
	"category": ["News", "Updates"],
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

Change the taxonomies that are added as REST fields.

```php
add_filter( 'rest_extended_default_taxonomies', function( $taxonomies, $post_types ) {
		return array( 'category', 'post_tag' );
}, 10, 2 );
```

## Notes

- Fields are read-only (`update_callback` is `null`).
- If no terms/image are present, empty arrays/empty strings are returned where applicable.

## Changelog

### 1.0.0

- Initial production-ready release.
- Added REST field registration for taxonomy terms and featured image metadata.
- Added safer handling for missing image sizes and invalid taxonomy/post data.
