<?php
/**
 * ACF Form Class
 *
 * Extends ACF.
 *
 * You may copy, distribute and modify the software as long as you track changes/dates in source files.
 * Any modifications to or software including (via compiler) GPL-licensed code must also be made
 * available under the GPL along with build & install instructions.
 *
 * @package    WPS\WP\Plugins\ACF
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2021 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/WPS
 * @version    1.0.0
 * @since      0.1.0
 */

namespace WPS\WP\Plugins\ACF\Form;

use WPS\Core\Singleton;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\ACFForm' ) ) {
	/**
	 * Class ACFForm
	 *
	 * @package WPS\WP\Plugins\ACF
	 */
	abstract class ACFForm extends Singleton {

		/**
		 * ACF ID.
		 *
		 * @var string
		 */
		protected string $id = '';

		/**
		 * Capability required to submit ACF form if any.
		 *
		 * For example, edit_posts, publish_posts, etc.
		 *
		 * @var string
		 */
		protected string $permissions = '';

		/**
		 * Whether to require the user to be logged into the site or not.
		 *
		 * @var bool
		 */
		protected bool $required_user_logged_in = true;

		/**
		 * ShowInformationForm constructor.
		 *
		 * @param null $args
		 */
		protected function __construct( $args = null ) {
			parent::__construct( $args );
			if ( method_exists( $this, 'plugins_loaded' ) ) {
				\add_action( 'init', array( $this, 'plugins_loaded' ), 2 );
			}

			\add_action( 'acf/pre_save_post', array( $this, 'pre_save_post' ), 5, 2 );
		}

		/**
		 * Preload values for these post fields.
		 *
		 * Supports: post_title, post_content, post_excerpt, post_author, _thumbnail_id
		 *
		 * @param string[] $fields Array of field names.
		 */
		public function preload_post_fields( $fields ) {
			foreach( $fields as $field ) {
				\add_action( "acf/load_value/name=$field", array( $this, 'load_post_value' ), 9, 3 );
			}
		}

		/**
		 * Preload values for these user fields.
		 *
		 * Supports: first_name, last_name, display_name, user_login/username, user_email
		 *
		 * @param string[] $fields Array of field names.
		 */
		public function preload_user_fields( $fields ) {
			foreach( $fields as $field ) {
				\add_action( "acf/load_value/name=$field", array( $this, 'load_user_value' ), 9, 3 );
			}
		}

		/**
		 * Pre-save post.
		 *
		 * @param int $post_id Post ID.
		 * @param array $form Form.
		 *
		 * @return false|int
		 */
		public function pre_save_post( $post_id, $form ) {
			//Check if user is logged in or can publish post
			if (
				! $this->can_save_post( $post_id ) ||
				! isset( $form['post_id'] ) ||
				$form['id'] !== $this->id
			) {
				return $post_id;
			}

			if ( 0 !== absint( $form['post_id'] ) ) {
				return absint( $form['post_id'] );
			}

			return $post_id;
		}

		/**
		 * Set the ACF field value for a post.
		 *
		 * @param string $value Meta value.
		 * @param int $post_id Post ID.
		 * @param array $field Field object.
		 *
		 * @return int
		 */
		public function load_post_value( $value, $post_id, $field ) {
			$post = get_post( $post_id );

			switch ( $field['name'] ) {
				case 'post_title':
					return $post->post_title;
				case 'post_content':
					return $post->post_content;
				case 'post_excerpt':
					return $post->post_excerpt;
				case 'post_author':
					return $post->post_author;
				case '_thumbnail_id':
					if ( false !== strpos( $value, 'http' ) ) {
						return \attachment_url_to_postid( $value );
					}
					break;
			}
			return $value;
		}

		/**
		 * Set the ACF field value for a user.
		 *
		 * @param string $value Meta value.
		 * @param int $post_id Post ID.
		 * @param array $field Field object.
		 *
		 * @return int
		 */
		public function load_user_value( $value, $post_id, $field ) {
			$current_user = get_current_user();
			if ( ! $current_user ) {
				return $value;
			}

			switch ( $field['name'] ) {
				case 'firstname':
				case 'first_name':
					return $current_user->first_name;
				case 'lastname':
				case 'last_name':
					return $current_user->last_name;
				case 'display_name':
					return $current_user->display_name;
				case 'username':
				case 'login':
				case 'user_login':
					return $current_user->user_login;
				case 'email':
				case 'user_email':
					return $current_user->user_email;
			}

			return $value;
		}

		/**
		 * Gets field group.
		 *
		 * @return string
		 */
		public function get_field_group(): string {
			return $this->id;
		}

		/**
		 * Unsets a key from $_POST for acf.
		 *
		 * @param string $key Key under $_POST['acf'].
		 */
		protected function unset_key( string $key ): void {
			if ( 'acf' === $key && isset( $_POST['acf'] ) ) {
				unset( $_POST['acf'] );
			} else {
				$key = $this->get_field_key( $key );

				if ( isset( $_POST['acf'] ) && isset( $_POST['acf'][ $key ] ) ) {
					unset( $_POST['acf'][ $key ] );
				}
			}

		}

		/**
		 * Gets a normalized key name for $_POST['acf'].
		 * Adds the prefix if one is available.
		 * Adds the field froup if one is available.
		 *
		 * @param string $key Key under $_POST['acf'].
		 * @param string $field_group
		 *
		 * @return string
		 */
		protected function get_field_key( string $key, string $field_group = '' ): string {
			if ( $field_group ) {
				return "field_{$field_group}_{$key}";
			} elseif ( $this->get_field_group() ) {
				return "field_{$this->get_field_group()}_{$key}";
			} elseif ( $this->id ) {
				return "field_{$this->id}_{$key}";
			}

			return "field_{$key}";
		}

		/**
		 * Gets a value from $_POST for acf key.
		 *
		 * @param string $key Key under $_POST['acf'].
		 *
		 * @return string
		 */
		public function get_acf_post_value( string $key ): string {
			return sanitize_text_field( $this->get_acf_post_raw_value( $key ) );
		}

		/**
		 * Gets a value from $_POST for acf key.
		 *
		 * @param string $key Key under $_POST['acf'].
		 *
		 * @return string
		 */
		public function get_acf_post_raw_value( string $key ): string {
			$key = $this->get_field_key( $key );

			if ( isset( $_POST['acf'] ) && isset( $_POST['acf'][ $key ] ) ) {
				return $_POST['acf'][ $key ];
			}

			return '';
		}

		/**
		 * Get the ACF sanitized data from $_POST for _acf_ prefixed key.
		 *
		 * @param string $key ACF Key under $_POST.
		 *
		 * @return string
		 */
		public function get_acf_data_post_value( string $key ): string {
			if ( 0 !== strpos( $key, '_acf_' ) ) {
				$key = "_acf_$key";
			}
			if ( isset( $_POST[ $key ] ) ) {
				return sanitize_text_field( $_POST[ $key ] );
			}
		}

		/**
		 * Whether user can save the post.
		 *
		 * @param int $post_id Post ID.
		 *
		 * @return bool
		 */
		public function can_save_post( int $post_id ): bool {
			// Does the user have the right permissions if permissions are required?
			if ( $this->permissions && ! current_user_can( $this->permissions ) ) {
				return false;
			}

			// Does the user have to be logged in?
			if ( $this->required_user_logged_in && ! is_user_logged_in() ) {
				return false;
			}

			// Are we on the right form?
			return (
				isset( $_POST['_acf_form_id'] ) && $_POST['_acf_form_id'] === $this->id
			);
		}
	}
}
