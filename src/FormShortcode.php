<?php
/**
 * ACF Form Shortcode.
 *
 * You may copy, distribute and modify the software as long as you track
 * changes/dates in source files. Any modifications to or software including
 * (via compiler) GPL-licensed code must also be made available under the GPL
 * along with build & install instructions.
 *
 * PHP Version 7.2
 *
 * @category   WPS\WP\Plugins\ACF
 * @package    WPS\WP\Plugins\ACF
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2021 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://wpsmith.net/
 * @since      0.0.1
 */

namespace WPS\WP\Plugins\ACF\Form;

use WPS\WP\Shortcodes\Shortcode;

if ( ! class_exists( __NAMESPACE__ . '\FormShortcode' ) ) {
	/**
	 * Class ACFFormShortcode
	 *
	 * @package \WPS\WP\Plugins
	 */
	class FormShortcode extends Shortcode {

		/**
		 * Shortcode name.
		 *
		 * @var string
		 */
		public string $name = 'acf_form';

		/**
		 * Gets default attributes.
		 *
		 * @return array Default attributes
		 */
		protected function get_defaults(): array {
			return array(
				'id'                 => 'edit-details',
				'field_group'        => '',
				'post_id'            => '',
				'post_title'         => false,
				'post_content'       => false,
				'return'             => '',
				'html_before_fields' => '',
				'html_after_fields'  => '',
				'submit_value'       => 'Save Changes',
			);
		}

		/**
		 * Registers scripts.
		 */
		public function register_scripts() {
			$suffix = \wp_scripts_get_suffix();
			\wp_register_script(
				'acf-password',
				\plugin_dir_url( __FILE__ ) . "assets/js/jquery.acf-password$suffix.js",
				array( 'jquery' ),
				filemtime( \plugin_dir_path( __FILE__ ) . "assets/js/jquery.acf-password$suffix.js" ),
				true
			);
		}

		public function enqueue_scripts() {
			\wp_enqueue_script( 'acf-password' );
			\acf_enqueue_scripts(); // in leiu of acf_form_head();
		}

		public function get_header() {
			if ( $this->is_active() ) {
				\acf()->form_front->check_submit_form(); // in leiu of acf_form_head();
				\wp_get_current_user();
			}
		}

		public function init() {
			\add_action( 'get_header', array( $this, 'get_header' ) );
			\add_filter( 'ajax_query_attachments_args', array( $this, 'ajax_query_attachments_args' ) );

			if ( ! \is_admin() ) {
				\wp_deregister_style( 'wp-admin' );
			}
		}

		/**
		 * Performs the shortcode.
		 *
		 * @param array $atts Array of user attributes.
		 * @param string $content Content of the shortcode.
		 *
		 * @return string Parsed output of the shortcode.
		 */
		public function shortcode( $atts, $content = null ) {
			$atts = $this->shortcode_atts( $atts );

			/**
			 * Depending on your setup, check if the user has permissions to edit_posts
			 */
			if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'retiree' ) ) {
				return __( 'You do not have permissions to edit this post.', 'bucket-lists' );
			}

			$field_group  = $atts['field_group'] ? $atts['field_group'] : $atts['id'];
			$field_groups = $this->get_field_groups( $field_group );

			// Get our form
			$options = array(
				'id'                 => $atts['id'],
				'field_groups'       => $field_groups,
				'html_before_fields' => $atts['html_before_fields'] . '<div class="acf-form-wrapper">',
				'html_after_fields'  => '</div>' . $atts['html_after_fields'],
				'uploader'           => 'basic',
				'context'            => 'acf-upload',
//				'return'             => $atts['return'],
				'submit_value'       => $atts['submit_value'],
				'updated_message'    => __( 'Updated successfully!', 'bucket-lists' ),
			);

			if ( '' !== $atts['return'] ) {
				$options['return'] = $atts['return'];
			}

			switch ( $atts['post_id'] ) {
				case 'user':
					$post_id = 'new_user';
					$uid     = \get_current_user_id();
					if ( $uid ) {
						$post_id = "user_$uid";
					}
					break;
				case 'post':
					$post_id = 'new_post';
					$pid     = \get_the_ID();
					if ( $pid ) {
						$post_id = "post_$pid";
					}
					break;
				default:
					$post_id = $atts['post_id'];
					break;
			}

			if ( $post_id ) {
				$options['post_id'] = $post_id;
			}

			if ( ! empty ( $field_groups ) ) {
				ob_start();
				\add_action( 'acf/form_data', array( $this, 'acf_form_data' ) );
				\acf_form( $options );
				\remove_action( 'acf/form_data', array( $this, 'acf_form_data' ) );
				$form = ob_get_contents();
				ob_end_clean();

				return $form;
			}

			return '';
		}

		public function acf_form_data( $data ) {
			\acf_hidden_input( array(
				'id'    => '_acf_form_id',
				'name'  => '_acf_form_id',
				'value' => $this->atts['id'],
			) );
		}

		public function ajax_query_attachments_args( $query ) {
			if (
				isset( $_SERVER['HTTP_REFERER'] ) &&
				false === strpos( $_SERVER['HTTP_REFERER'], 'wp-admin', 0 ) &&
				isset( $_POST['query']['_acfuploader'] )
			) {
				\add_filter( 'posts_where', array( $this, 'posts_where' ) );
				\add_filter( 'posts_join', array( $this, 'posts_join' ) );
				$query['author'] = get_current_user_id();
			}

			return $query;
		}

		public function posts_join( $join ) {
			\remove_filter( 'posts_join', array( $this, 'posts_join' ) );
			global $wpdb;
			if ( isset( $_POST['post_id'] ) ) {
				$join .= " LEFT JOIN {$wpdb->posts} as my_post_parent ON ({$wpdb->posts}.post_parent = my_post_parent.ID) ";
			}

			return $join;
		}

		public function posts_where( $where ) {
			\remove_filter( 'posts_where', array( $this, 'posts_where' ) );
			global $wpdb;
			$whitelist_post_type = array(
				'bucket_list',
				'bucket_list_item',
			);
			if ( isset( $_POST['post_id'] ) ) {
				$post_id = $_POST['post_id'];
				$post    = \get_post( $post_id );
				if ( $post && in_array( $post->post_type, $whitelist_post_type ) ) {
					$where = str_replace(
						' AND wp_posts.post_author IN (' . \get_current_user_id() . ') ',
						$wpdb->prepare( " AND (( wp_posts.post_author IN (%d) OR my_post_parent.post_type = %s )) ", get_current_user_id(), $post->post_type ),
						$where
					);
//					$where .= $wpdb->prepare( " AND my_post_parent.post_type = %s ", $post->post_type );
					//$where .= $wpdb->prepare(" AND my_post_parent.post_type = %s AND {$wpdb->posts}.post_parent = %d", $post->post_type, $_POST['post_id']);  //Use this if you want to restrict to selected post only
				}
			}

			return $where;
		}

		protected function get_field_groups( $field_group ) {
			if ( is_numeric( $field_group ) ) {
				return array( intval( $field_group ) );
			} elseif ( strpos( $field_group, ',' ) ) {
				$groups = array();
				$parts  = explode( ',', $field_group );
				foreach ( $parts as $part ) {
					$groups[] = $this->get_field_group( $part );
				}

				return $groups;
			} elseif ( str_starts_with( $field_group, 'group_' ) ) {
				return array( \esc_attr( $field_group ) );
			} else {
				return array( 'group_' . \esc_attr( $field_group ) );
			}
		}

	}
}
