<?php
/**
 * Plugin Name: REST Simple Extention
 * Plugin URI:  https://dustinwight.com
 * Description: Extends public REST post type responses with taxonomy terms and featured image metadata.
 * Version:     1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:      Dustin Wight
 * License:     GPL-2.0-or-later
 * Text Domain: rest-simple-extention
 *
 * @package RestSimpleExtention
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rest_Simple_Extention_Plugin' ) ) {
	/**
	 * Main plugin bootstrap class.
	 */
	final class Rest_Simple_Extention_Plugin {
		/**
		 * Bootstraps plugin hooks.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( 'rest_api_init', array( __CLASS__, 'register_rest_fields' ) );
		}

		/**
		 * Registers custom REST fields for configured post types.
		 *
		 * @return void
		 */
		public static function register_rest_fields() {
			$post_types = get_post_types( array( 'public' => true ), 'names' );
			$post_types = array_values( array_diff( $post_types, array( 'attachment' ) ) );
			$post_types = apply_filters( 'rest_extended_default_post_types', $post_types );

			if ( empty( $post_types ) || ! is_array( $post_types ) ) {
				return;
			}

			register_rest_field(
				$post_types,
				'taxonomies',
				array(
					'get_callback'    => array( __CLASS__, 'get_rest_taxonomies' ),
					'update_callback' => null,
					'schema'          => self::taxonomies_schema(),
				)
			);

			register_rest_field(
				$post_types,
				'featured_img',
				array(
					'get_callback'    => array( __CLASS__, 'get_rest_featured_image' ),
					'update_callback' => null,
					'schema'          => self::featured_image_schema(),
				)
			);
		}

		/**
		 * Returns taxonomy terms grouped by taxonomy for a REST object.
		 *
		 * @param array           $object     Prepared object data.
		 * @param string          $field_name Requested field name.
		 * @param WP_REST_Request $request    REST request object.
		 *
		 * @return array<string, array>
		 */
		public static function get_rest_taxonomies( $object, $field_name, $request ) {
			unset( $field_name, $request );

			$post_id  = isset( $object['id'] ) ? absint( $object['id'] ) : 0;
			$post_type = $post_id ? get_post_type( $post_id ) : '';

			if ( ! $post_id || ! is_string( $post_type ) || '' === $post_type ) {
				return array();
			}

			$taxonomies = get_object_taxonomies( $post_type, 'names' );
			if ( empty( $taxonomies ) || ! is_array( $taxonomies ) ) {
				return array();
			}

			$allowed_taxonomies = apply_filters( 'rest_extended_default_taxonomies', $taxonomies, array( $post_type ) );
			if ( ! is_array( $allowed_taxonomies ) ) {
				$allowed_taxonomies = $taxonomies;
			}

			$allowed_lookup = array_fill_keys( array_filter( $allowed_taxonomies, 'is_string' ), true );
			$results        = array();

			foreach ( $taxonomies as $taxonomy ) {
				if ( ! isset( $allowed_lookup[ $taxonomy ] ) || ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}

				$terms = get_the_terms( $post_id, $taxonomy );
				if ( empty( $terms ) || is_wp_error( $terms ) ) {
					$results[ $taxonomy ] = array();
					continue;
				}

				$results[ $taxonomy ] = array_values( wp_list_pluck( $terms, 'name' ) );
			}

			return $results;
		}

		/**
		 * Returns featured image metadata for a REST object.
		 *
		 * @param array           $object     Prepared object data.
		 * @param string          $field_name Requested field name.
		 * @param WP_REST_Request $request    REST request object.
		 *
		 * @return array
		 */
		public static function get_rest_featured_image( $object, $field_name, $request ) {
			unset( $field_name, $request );

			$image_id = isset( $object['featured_media'] ) ? absint( $object['featured_media'] ) : 0;
			if ( ! $image_id || ! wp_attachment_is_image( $image_id ) ) {
				return array();
			}

			return array(
				'alt'       => (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
				'full'      => self::get_image_url_by_size( $image_id, 'full' ),
				'large'     => self::get_image_url_by_size( $image_id, 'large' ),
				'medium'    => self::get_image_url_by_size( $image_id, 'medium' ),
				'thumbnail' => self::get_image_url_by_size( $image_id, 'thumbnail' ),
			);
		}

		/**
		 * Safely resolves attachment URL by image size.
		 *
		 * @param int    $image_id Attachment ID.
		 * @param string $size     Image size slug.
		 *
		 * @return string
		 */
		private static function get_image_url_by_size( $image_id, $size ) {
			$image = wp_get_attachment_image_src( $image_id, $size );

			if ( ! is_array( $image ) || empty( $image[0] ) ) {
				return '';
			}

			return (string) $image[0];
		}

		/**
		 * Schema for grouped taxonomies REST field.
		 *
		 * @return array
		 */
		private static function taxonomies_schema() {
			return array(
				'description' => __( 'Assigned taxonomy term names grouped by taxonomy slug.', 'rest-simple-extention' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'additionalProperties' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
			);
		}

		/**
		 * Schema for featured image REST field.
		 *
		 * @return array
		 */
		private static function featured_image_schema() {
			return array(
				'description' => __( 'Featured image metadata by image size.', 'rest-simple-extention' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'properties'  => array(
					'alt'       => array( 'type' => 'string' ),
					'full'      => array( 'type' => 'string' ),
					'large'     => array( 'type' => 'string' ),
					'medium'    => array( 'type' => 'string' ),
					'thumbnail' => array( 'type' => 'string' ),
				),
			);
		}
	}
}

Rest_Simple_Extention_Plugin::init();