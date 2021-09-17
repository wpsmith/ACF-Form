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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\ACFPostTypeForm' ) ) {
	/**
	 * Class ACFPostTypeForm
	 *
	 * @package WPS\WP\Plugins\ACF
	 */
	abstract class ACFPostTypeForm extends ACFForm {

		/**
		 * @var string
		 */
		protected string $post_type = 'post';

		/**
		 * Default status of ACF form submission
		 *
		 * @var string
		 */
		protected string $default_status = 'draft';

		/**
		 * ShowInformationForm constructor.
		 *
		 * @param null $args
		 */
		protected function __construct( $args = null ) {
			parent::__construct( $args );
			\add_action( 'acf/pre_submit_form', array( $this, 'pre_submit_form' ), 5 );
		}

		/**
		 * Before form does anything, update the form to use the correct post type and status for new posts.
		 *
		 * @param array $form ACF Form array.
		 *
		 * @return array
		 */
		public function pre_submit_form( array $form ): array {
			if( $this->post_type && $form['post_id'] === 'new_post' && $form['id'] === $this->id ) {
				$form['new_post'] = wp_parse_args( array(
					'post_type' 	=> $this->post_type,
					'post_status'	=> $this->default_status,
				), $form['new_post'] );
			}

			return $form;
		}

	}
}
