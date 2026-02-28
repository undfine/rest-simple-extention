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

			$taxonomies = apply_filters( 'rest_extended_default_taxonomies', array( 'category' ), $post_types );
			if ( ! is_array( $taxonomies ) ) {
				$taxonomies = array( 'category' );
			}

			foreach ( $taxonomies as $taxonomy ) {
				if ( ! is_string( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}

				register_rest_field(
					$post_types,
					$taxonomy,
					array(
						'get_callback'    => array( __CLASS__, 'get_rest_terms' ),
						'update_callback' => null,
						'schema'          => self::terms_schema(),
					)
				);
			}

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
		 * Returns taxonomy term names for a REST object.
		 *
		 * @param array           $object     Prepared object data.
		 * @param string          $field_name Requested field name.
		 * @param WP_REST_Request $request    REST request object.
		 *
		 * @return array
		 */
		public static function get_rest_terms( $object, $field_name, $request ) {
			unset( $request );

			$taxonomy = is_string( $field_name ) && $field_name ? $field_name : 'category';
			$post_id  = isset( $object['id'] ) ? absint( $object['id'] ) : 0;

			if ( ! $post_id || ! taxonomy_exists( $taxonomy ) ) {
				return array();
			}

			$terms = get_the_terms( $post_id, $taxonomy );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				return array();
			}

			return array_values( wp_list_pluck( $terms, 'name' ) );
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
			if ( ! $image_id ) {
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
		 * Schema for taxonomy terms REST fields.
		 *
		 * @return array
		 */
		private static function terms_schema() {
			return array(
				'description' => __( 'Assigned term names for this taxonomy.', 'rest-simple-extention' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type' => 'string',
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
