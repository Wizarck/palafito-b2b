<?php
/**
 * Shortcodes
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

namespace WHOLESALEX;

/**
 * WholesaleX Shortcodes Class
 *
 * @since 1.0.0
 */
class WHOLESALEX_Shortcodes {
	/**
	 * Stores the names of fields used in the registration form.
	 *
	 * This array is typically used to check for specific field names.
	 * when rendering or validating the registration form dynamically.
	 *
	 * @var string[]
	 */

	public $registration_form_felds_name = array();


	/**
	 * Shortcodes Constructor
	 */
	public function __construct() {
		add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'add_custom_fields_on_checkout_page' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_custom_checkout_fields' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'add_custom_fields_on_order_meta' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'show_custom_fields_value' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'show_custom_fields_on_order_page' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'show_custom_fields_on_order_page' ) );
		/**
		 * Shortcode For WholesaleX Login and Registration Form (Combined)
		 *
		 * @since 1.0.1
		 */

		add_shortcode( 'wholesalex_registration', array( $this, 'registration_shortcode' ), 10 );
		add_shortcode( 'wholesalex_login_registration', array( $this, 'login_registration_shortcode' ), 10 );
		add_shortcode( 'wholesalex_login', array( $this, 'login_shortcode' ), 10 );
		add_action( 'init', array( $this, 'wholesalex_handle_password_reset' ) );
		/**
		 * Filters the list of CSS class names for the current post.
		 *
		 * @param string[] $classes An array of post class names.
		 * @param string[] $class   An array of additional class names added to the post.
		 * @param int      $post_id The post ID.
		 * @return string[] An array of post class names.
		 */
		add_filter(
			'post_class',
			function ( array $classes, array $class, int $post_id ): array {
				array_push( $classes, '_wholesalex wsx-wholesalex-product' );
				return $classes;
			},
			10,
			3
		);

		add_action( 'wholesalex_before_registration_form_render', array( $this, 'enqueue_password_meter' ) );

		add_action( 'wp_ajax_nopriv_wholesalex_process_login', array( $this, 'process_login' ) );
		add_action( 'wp_ajax_wholesalex_process_login', array( $this, 'process_login' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action(
			'wholesalex_before_registration_form_render',
			function () {
				if ( ! is_admin() && function_exists( 'wc_print_notices' ) ) {
					woocommerce_output_all_notices();
				}
			}
		);

		$is_whitelabel_enable = wholesalex()->get_setting( 'wsx_addon_whitelabel' );
		if ( 'yes' === $is_whitelabel_enable ) {
			$registration_page_slug = wholesalex()->get_setting( 'registration_form_buidler_submenu_slug' );
			if ( '' != $registration_page_slug ) {
				add_shortcode( $registration_page_slug . '_login_registration', array( $this, 'login_registration_shortcode' ) );
				add_shortcode( $registration_page_slug . '_registration', array( $this, 'registration_shortcode' ) );
			}
		}
	}

	/**
	 * Display the forgot password form
	 *
	 * @return string
	 */
	public function wholesalex_forgot_password_form() {
		if ( isset( $_GET['reset'] ) && 'true' === $_GET['reset'] && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wholesalex_reset_password' ) ) {
			echo '<div class="woocommerce-message">' . esc_html__( 'A password reset email has been sent. Please check your inbox.', 'wholesalex' ) . '</div>';
		}

		ob_start();
		?>
			<form method="post" class="wsx-lost-password-form">
				<p><?php esc_html_e( 'Lost your password? Please enter your email address. You will receive a link to create a new password via email.', 'wholesalex' ); ?></p>
				<p>
					<input class="wsx-input" type="email" name="user_email" id="user_email" placeholder="<?php esc_attr_e( 'Your email address', 'wholesalex' ); ?>" required>
				</p>
				<p>
					<input type="submit" class="wsx-input wsx-password-reset-btn" name="wholesalex_reset_password" value="<?php esc_attr_e( 'Reset Password', 'wholesalex' ); ?>">
				</p>
			</form>
		<?php
		return ob_get_clean();
	}


	/**
	 * Handle form submission and trigger password reset email
	 *
	 * @return void
	 */
	public function wholesalex_handle_password_reset() {
		if ( isset( $_POST['wholesalex_reset_password'] ) && isset( $_POST['wholesalex_reset_password_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wholesalex_reset_password_nonce'] ) ), 'wholesalex_reset_password_action' ) ) {
			if ( isset( $_POST['user_email'] ) ) {
				$email = sanitize_email( wp_unslash( $_POST['user_email'] ) );
			} else {
				$email = '';
			}

			if ( empty( $email ) ) {
				wc_add_notice( 'Please enter a valid email address.', 'error' );
				return;
			}

			// Check if user exists.
			$user = get_user_by( 'email', $email );

			if ( ! $user ) {
				wc_add_notice( 'No user found with this email address.', 'error' );
				return;
			}

			// Generate password reset key.
			$key = get_password_reset_key( $user );
			if ( is_wp_error( $key ) ) {
				wc_add_notice( $key->get_error_message(), 'error' );
				return;
			}
			$reset_link = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' );
			$this->wholesalex_send_password_reset_email( $user->user_login, $user->user_email, $reset_link );
			wp_safe_redirect( add_query_arg( 'reset', 'true', get_permalink() ) );
			exit;
		}
	}

	/**
	 * Send password reset email
	 *
	 * @param string $user_login User login.
	 * @param string $user_email User email.
	 * @param string $reset_link Reset link.
	 * @return void
	 */
	public function wholesalex_send_password_reset_email( $user_login, $user_email, $reset_link ) {
		$mailer        = WC()->mailer();
		$email_heading = esc_html__( 'Password Reset Request', 'wholesalex' );
		ob_start();
		?>
		<p><?php printf( /* translators: %s: User login name */ esc_html__( 'Hi %s,', 'wholesalex' ), esc_html( $user_login ) ); ?></p>
		<p>
		<?php
		printf(
			/* translators: %s: User login name */
			esc_html__( 'Someone has requested a new password for the following account on %s:', 'wholesalex' ),
			esc_html( get_bloginfo( 'name' ) )
		);
		?>
		</p>
		<p><strong><?php esc_html_e( 'Username:', 'wholesalex' ); ?></strong> <?php echo esc_html( $user_login ); ?></p>
		<p><?php esc_html_e( 'If you didn’t make this request, just ignore this email. If you’d like to proceed:', 'wholesalex' ); ?></p>
		<p><a class="wsx-link" href="<?php echo esc_url( $reset_link ); ?>"><?php esc_html_e( 'Click here to reset your password', 'wholesalex' ); ?></a></p>
		<p><?php esc_html_e( 'Thanks for reading.', 'wholesalex' ); ?></p>
		<?php
		$message = ob_get_clean();
		$mailer->send(
			sanitize_email( $user_email ),
			esc_html__( 'Password Reset Request', 'wholesalex' ),
			$mailer->wrap_message( $email_heading, $message )
		);
	}


	/**
	 * Enqueue Form Scripts
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! is_singular() ) {
			return;
		}

		global $post;

		$registration_page_slug = wholesalex()->get_setting( 'registration_form_buidler_submenu_slug' );

		$is_elementor_builder =isset($_GET['elementor-preview']) && sanitize_text_field($_GET['elementor-preview']); // @codingStandardsIgnoreLine.
		$is_breakdance_builder =  isset($_GET['breakdance_iframe']) && true==sanitize_text_field($_GET['breakdance_iframe']); // @codingStandardsIgnoreLine.

		if ( has_shortcode( $post->post_content, 'wholesalex_registration' ) || has_shortcode( $post->post_content, 'wholesalex_login_registration' ) || has_shortcode( $post->post_content, 'wholesalex_login' ) || ( function_exists( 'has_block' ) && has_block( 'wholesalex/forms' ) ) || has_shortcode( $post->post_content, $registration_page_slug . '_login_registration' ) || has_shortcode( $post->post_content, $registration_page_slug . '_registration' ) || $is_breakdance_builder || $is_elementor_builder ) {
			wp_enqueue_style( 'whx_form', WHOLESALEX_URL . 'assets/css/whx_form.css', array(), WHOLESALEX_VER );
		}
	}

	/**
	 * Current Page Password Lost Check
	 *
	 * @return bool
	 */
	public function check_current_page_is_lost_password() {
		$current_url = ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http' ) . '://' . ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' ) . ( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );
		if ( strpos( $current_url, 'lost-password' ) !== false || strpos( $current_url, '?reset=true' ) !== false ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * From Validation
	 *
	 * @param mixed $columns columns.
	 * @return mixed
	 */
	private function is_form_row_valid( $columns ) {
		$status = false;
		foreach ( $columns as $field ) {
			$status = isset( $field['status'] ) ? $field['status'] : true;
			if ( $status ) {
				break;
			}
		}

		return $status;
	}

	/**
	 * Get Select Role Field
	 *
	 * @param bool $is_only_b2b is only b2b.
	 * @return mixed
	 */
	private function get_select_role_field( $is_only_b2b = false ) {
		$__roles        = wholesalex()->get_roles( 'roles_option' );
		$__roles_option = array(
			array(
				'name'  => __( 'Select Role', 'wholesalex' ),
				'value' => '',
			),
		);
		foreach ( $__roles as $id => $role ) {
			if ( $is_only_b2b && isset( $role['value'] ) && 'wholesalex_b2c_users' === $role['value'] ) {
				continue;
			}
			if ( isset( $role['value'] ) && 'wholesalex_guest' !== $role['value'] ) {
				array_push( $__roles_option, $role );
			}
		}
		$__select_role_dropdown = array(
			'id'       => 9999999,
			'type'     => 'select',
			'label'    => apply_filters( 'wholesalex_global_registration_form_select_roles_title', __( 'Select Registration Roles', 'wholesalex' ) ),
			'name'     => 'wholesalex_registration_role',
			'option'   => $__roles_option,
			'empty'    => true,
			'required' => true,
		);

		$field = array(
			'id'            => 'wsx-select-role',
			'type'          => 'row',
			'columns'       => array( $__select_role_dropdown ),
			'isMultiColumn' => false,
		);

		return $field;
	}

	/**
	 * Generate Form Field
	 *
	 * @param mixed $row row.
	 * @param mixed $is_role_wise is rolewise.
	 * @param mixed $input_variation input variation.
	 * @param mixed $is_only_b2b is only b2b.
	 * @return void
	 */
	private function render_columns( $row, $is_role_wise, $input_variation, $is_only_b2b = false ) {
		$columns            = $row['columns'];
		$multi_column_class = isset( $row['isMultiColumn'] ) && $row['isMultiColumn'] ? 'double-column' : '';

		if ( $this->is_form_row_valid( $columns ) ) {

			$row_class = "wsx-reg-form-row {$multi_column_class}";
			?>
			<div class="<?php echo esc_attr( $row_class ); ?>">
				<?php
				foreach ( $columns as $field ) {
					$exclude = $this->check_depends( $field );
					if ( $is_role_wise ) {
						$exclude_roles = explode( ' ', $exclude );
						if ( in_array( $is_role_wise, $exclude_roles, true ) ) {
							continue;
						}
					}
					$required_class = isset( $field['required'] ) && $field['required'] ? 'wsx-field-required' : '';
					$display_none   = ( $exclude && ! $is_role_wise ) ? 'display:none' : '';
					$field_name     = $field['name'];
					$field_position = isset( $field['columnPosition'] ) ? $field['columnPosition'] : 'left';
					$field_class    = "wholesalex-registration-form-column {$field_position} wsx-field {$required_class} wsx-field-{$field_name}";
					?>
						<div data-wsx-exclude="<?php echo esc_attr( $exclude ); ?>" class="<?php echo esc_attr( $field_class ); ?>" style="<?php echo esc_attr( $display_none ); ?>"> 
						
						<?php
						$this->registration_form_felds_name[] = $field_name;
						$this->generate_form_field( $field, $is_role_wise, $input_variation, $is_only_b2b );
						?>
						</div>
						<?php
				}
				?>
			</div>
			<?php

		}
	}

	/**
	 * Render the form based on the type and provided data.
	 *
	 * @param string $type The type of form (registration or login).
	 * @param array  $form_data The form data.
	 * @param array  $input_variation The input variation.
	 * @param bool   $is_rolewise Whether the form is role-specific.
	 * @param bool   $is_only_b2b Whether the form is only for B2B.
	 * @param string $role The role for the form.
	 */
	private function render_form( $type, $form_data, $input_variation, $is_rolewise = false, $is_only_b2b = false, $role = '' ) {
		$default_form      = wholesalex()->get_empty_form();
		$initial_form_data = wholesalex()->get_default_registration_form_fields();
		if ( 'registration' === $type ) {
			$enctype             = 'multipart/form-data';
			$wrapper_class       = 'wsx-reg-fields';
			$heading_class       = 'wsx-reg-form-heading';
			$heading_title_class = 'wsx-reg-form-heading-text';
			$heading_desc_class  = 'wholesalex-registration-form-subtitle-text';
			$button_class        = 'wsx-register-btn';
			$header              = $form_data['registrationFormHeader'];
			$button              = $form_data['registrationFormButton'];
			$fields              = ( isset( $form_data['registrationFields'] ) ? $form_data['registrationFields'] : $initial_form_data );
		} else {
			$enctype             = 'application/x-www-form-urlencoded';
			$wrapper_class       = 'wsx-login-fields';
			$heading_class       = 'wholesalex-login-form-title';
			$heading_title_class = 'wsx-login-form-title-text';
			$heading_desc_class  = 'wholesalex-login-form-subtitle-text';
			$button_class        = 'wsx-login-btn';
			$header              = $form_data['loginFormHeader'];
			$fields              = $default_form['loginFields'];
			$button              = $form_data['loginFormButton'];
		}

		$allowed_html = array(
			'div'    => array(
				'class' => array(),
			),
			'span'   => array(
				'class' => array(),
			),
			'button' => array(
				'class' => array(),
			),
		);

		?>
	<form class="wholesalex-<?php echo esc_attr( $type ); ?>-form" enctype="<?php echo esc_attr( $enctype ); ?>">
		<?php
		if ( isset( $form_data['settings']['isShowFormTitle'] ) && $form_data['settings']['isShowFormTitle'] ) {
			$output  = '<div class="%s">';
			$output .= '<div class="%s">%s</div>';

			if ( isset( $header['isHideDescription'] ) && ! $header['isHideDescription'] ) {
				$output .= '<div class="%s">%s</div>';
			}

			$output .= '</div>';

			echo wp_kses(
				sprintf(
					$output,
					esc_attr( $heading_class ),
					esc_attr( $heading_title_class ),
					isset( $header['title'] ) ? esc_html( $header['title'] ) : '',
					isset( $header['description'] ) ? esc_attr( $heading_desc_class ) : '',
					isset( $header['description'] ) ? esc_html( $header['description'] ) : ''
				),
				$allowed_html
			);
		}
		?>
		<div class="wholesalex-fields-wrapper <?php echo esc_attr( $wrapper_class ); ?> wsx-fields-container">
		<?php
		foreach ( $fields as $row ) {
			$this->render_columns( $row, $is_rolewise, $input_variation, $is_only_b2b );
		}
		if ( 'registration' === $type ) {
			if ( $is_rolewise ) {
				?>
				<input type="hidden" name="wholesalex_registration_role" value="<?php echo esc_attr( $role ); ?>">
				<?php
			} elseif ( ! in_array( 'wholesalex_registration_role', $this->registration_form_felds_name, true ) ) {
				$this->render_columns( $this->get_select_role_field( $is_only_b2b ), $is_rolewise, $input_variation );
			}
		}
		?>
		</div>

		<input type="hidden" name="action" value="wholesalex_process_<?php echo esc_attr( $type ); ?>" />

		<?php
		do_action( 'wholesalex_' . $type . '_form' );
		wp_nonce_field( 'wholesalex-' . $type, 'wholesalex-' . $type . '-nonce' );
		$align_class   = isset( $form_data['styles']['layout']['button']['align'] ) ? sanitize_html_class( $form_data['styles']['layout']['button']['align'] ) : '';
		$button_class .= ' ' . $align_class;

		$output = sprintf(
			'<div class="%s"><button class="%s">%s</button></div>',
			esc_attr( 'wsx-form-btn-wrapper' ),
			esc_attr( $button_class ),
			isset( $button['title'] ) ? esc_html( $button['title'] ) : ''
		);

		echo wp_kses( $output, $allowed_html );

		if ( 'login' === $type ) {
			?>
			<div class="wsx-reg-form-row ">
				<div class="wholesalex-registration-form-column left wsx-field woocommerce-LostPassword lost_password"> 
					<a class="wsx-link" href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></a>
				</div>
			</div>
			<?php
		}
		?>
	</form>
		<?php
	}


	/**
	 * Get Form Style
	 *
	 * @param mixed $atts form array.
	 * @return array
	 */
	private function render_registration_shortcode( $atts = array() ) {
		$atts            = array_change_key_case( (array) $atts, CASE_LOWER );
		$form_data       = wholesalex()->get_new_form_builder_data();
		$input_variation = $form_data['settings']['inputVariation'];
		$is_role_wise    = isset( $atts['registration_role'] ) && ! empty( $atts['registration_role'] ) && 'all_b2b' != $atts['registration_role'] && 'global' != $atts['registration_role'] ? $atts['registration_role'] : false;
		$is_only_b2b     = isset( $atts['registration_role'] ) && ! empty( $atts['registration_role'] ) && 'all_b2b' == $atts['registration_role'] ? $atts['registration_role'] : false;
		$wrapper         = wp_unique_id( 'whx_wrapper' );

		ob_start();
		if ( ! wp_style_is( 'whx_form' ) ) {
			wp_enqueue_style( 'whx_form', WHOLESALEX_URL . 'assets/css/whx_form.css', array(), WHOLESALEX_VER );
		}
		$this->load_form_js( $wrapper );
		printf( '<style id="%1$s"> %2$s { %3$s } </style>', 'whx_form_css', ':root', wp_strip_all_tags( $this->get_vars_css( $this->get_form_style( $form_data['style'], $form_data['loginFormHeader']['styles'], $form_data['registrationFormHeader']['styles'] ) ) ) ); // @codingStandardsIgnoreLine.

		do_action( 'wholesalex_before_registration_form_render' );

		?>
			<div id="<?php echo esc_attr( $wrapper ); ?>" class="wholesalex-form-wrapper wsx-form-wrapper_frontend wsx-without-login wsx_<?php echo esc_attr( $input_variation ); ?>">
			<div class="wholesalex_circular_loading__wrapper">
				<div class="wholesalex_loading_spinner">
					<svg viewBox="25 25 50 50" class="move_circular">
						<circle
							cx="50"
							cy="50"
							r="20"
							fill="none"
							class="wholesalex_circular_loading_icon"
						></circle>
					</svg>
				</div>
			</div>
			<?php $this->render_form( 'registration', $form_data, $input_variation, $is_role_wise, $is_only_b2b, isset( $atts['registration_role'] ) ? $atts['registration_role'] : '' ); ?>
			</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Login Registration Shortcode
	 *
	 * @param mixed $atts form array.
	 * @return array
	 */
	private function render_login_registration_shortcode( $atts = array() ) {
		$form_data = wholesalex()->get_new_form_builder_data();

		$input_variation = $form_data['settings']['inputVariation'];

		$is_role_wise = isset( $atts['registration_role'] ) && ! empty( $atts['registration_role'] ) && 'all_b2b' != $atts['registration_role'] && 'global' != $atts['registration_role'] ? $atts['registration_role'] : false;
		$is_only_b2b  = isset( $atts['registration_role'] ) && ! empty( $atts['registration_role'] ) && 'all_b2b' == $atts['registration_role'] ? $atts['registration_role'] : false;
		$wrapper      = wp_unique_id( 'whx_wrapper' );

		ob_start();
		if ( ! wp_style_is( 'whx_form' ) ) {
			wp_enqueue_style( 'whx_form', WHOLESALEX_URL . 'assets/css/whx_form.css', array(), WHOLESALEX_VER );
		}
		$this->load_form_js( $wrapper );

		printf( '<style id="%1$s"> %2$s { %3$s } </style>', 'whx_form_css', ':root', esc_html( $this->get_vars_css( $this->get_form_style( $form_data['style'], $form_data['loginFormHeader']['styles'], $form_data['registrationFormHeader']['styles'] ) ) ) );

		do_action( 'wholesalex_before_registration_form_render' );

		?>
			
			<div id="<?php echo esc_attr( $wrapper ); ?>" class="wholesalex-form-wrapper wsx-form-wrapper_frontend wsx_<?php echo esc_attr( $input_variation ); ?>">
			<div class="wholesalex_circular_loading__wrapper">
				<div class="wholesalex_loading_spinner">
					<svg viewBox="25 25 50 50" class="move_circular">
						<circle
							cx="50"
							cy="50"
							r="20"
							fill="none"
							class="wholesalex_circular_loading_icon"
						></circle>
					</svg>
				</div>
			</div>
			<?php $this->render_form( 'login', $form_data, $input_variation ); ?>
			<span class='wsx-form-separator'></span>
			<?php $this->render_form( 'registration', $form_data, $input_variation, $is_role_wise, $is_only_b2b, isset( $atts['registration_role'] ) ? $atts['registration_role'] : '' ); ?>

		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Login Shortcode
	 *
	 * @return array
	 */
	private function render_login_shortcode() {
		$form_data       = wholesalex()->get_new_form_builder_data();
		$input_variation = $form_data['settings']['inputVariation'];
		// $is_role_wise     = isset( $atts['registration_role'] ) && ! empty( $atts['registration_role'] ) && 'all_b2b' != $atts['registration_role'] && 'global' != $atts['registration_role'] ? $atts['registration_role'] : false;
		// $is_only_b2b    = isset( $atts['registration_role'] ) && ! empty( $atts['registration_role'] ) && 'all_b2b' == $atts['registration_role'] ? $atts['registration_role'] : false;
		$wrapper = wp_unique_id( 'whx_wrapper' );

		ob_start();
		if ( ! wp_style_is( 'whx_form' ) ) {
			wp_enqueue_style( 'whx_form', WHOLESALEX_URL . 'assets/css/whx_form.css', array(), WHOLESALEX_VER );
		}
		$this->load_form_js( $wrapper );
		printf( '<style id="%1$s"> %2$s { %3$s } </style>', 'whx_form_css', ':root', esc_html( $this->get_vars_css( $this->get_form_style( $form_data['style'], $form_data['loginFormHeader']['styles'], $form_data['registrationFormHeader']['styles'] ) ) ) );
		do_action( 'wholesalex_before_registration_form_render' );
		?>
			<div id="<?php echo esc_attr( $wrapper ); ?>" class="wholesalex-form-wrapper wsx-form-wrapper_frontend wsx_<?php echo esc_attr( $input_variation ); ?>">
			<div class="wholesalex_circular_loading__wrapper">
				<div class="wholesalex_loading_spinner">
					<svg viewBox="25 25 50 50" class="move_circular">
						<circle
							cx="50"
							cy="50"
							r="20"
							fill="none"
							class="wholesalex_circular_loading_icon"
						></circle>
					</svg>
				</div>
			</div>
			<?php $this->render_form( 'login', $form_data, $input_variation ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Registration Form
	 *
	 * @param array $atts    Shortcode attributes. Default empty.
	 * @return string Shortcode output.
	 * @since 1.0.0
	 */
	public function registration_shortcode( $atts = array() ) {
		if ( is_user_logged_in() && is_singular() ) {
			$__form_view_for_logged_in_user = wholesalex()->get_setting( '_settings_show_form_for_logged_in' );

			$__message_for_logged_in_user = wholesalex()->get_setting( '_settings_message_for_logged_in_user' );
			if ( 'yes' !== $__form_view_for_logged_in_user ) {
				if ( is_admin() || ! function_exists( 'wc_add_notice' ) || ! function_exists( 'wc_print_notices' ) ) {
					return;
				}
				?>
				<div>
				<?php
				wc_add_notice( $__message_for_logged_in_user, 'error' );
				wc_print_notices();
				?>
					<a class="wsx-link" href="<?php echo esc_url_raw( wp_logout_url( get_permalink() ) ); ?>"> <?php echo esc_html( wholesalex()->get_language_n_text( '_language_logout_to_see_this_form', __( 'Logout to See this form', 'wholesalex' ) ) ); ?></a>
					</div>
				<?php
				return;
			}
		}
		if ( $this->check_current_page_is_lost_password() ) {
			return $this->render_registration_shortcode( $atts );
		} else {
			return '';
		}
	}
	/**
	 * Login And Registration Form
	 *
	 * @param  array $atts Shortcode Attributes. Default Empty.
	 * @return string Shortcode output.
	 * @since 1.0.1
	 * @since 1.2.4 _settings_redirect_url_login Field Deafult Settings Param Added
	 */
	public function login_registration_shortcode( $atts = array() ) {

		$atts = array_merge(
			array(
				'lost_password' => 'false',
			),
			$atts
		);

		if ( is_user_logged_in() && is_singular() ) {
			$__form_view_for_logged_in_user = wholesalex()->get_setting( '_settings_show_form_for_logged_in' );
			$__message_for_logged_in_user   = wholesalex()->get_setting( '_settings_message_for_logged_in_user' );
			if ( 'yes' !== $__form_view_for_logged_in_user ) {
				if ( is_admin() || ! function_exists( 'wc_add_notice' ) || ! function_exists( 'wc_print_notices' ) ) {
					return;
				}
				?>
				<div>
				<?php
				wc_add_notice( $__message_for_logged_in_user, 'error' );
				wc_print_notices();
				?>
					<a class="wsx-link" href="<?php echo esc_url_raw( wp_logout_url( get_permalink() ) ); ?>"> <?php echo esc_html( wholesalex()->get_language_n_text( '_language_logout_to_see_this_form', __( 'Logout to See this form', 'wholesalex' ) ) ); ?></a>
					</div>
				<?php
				return;
			}
		}
		if ( $this->check_current_page_is_lost_password() ) {
			return $this->render_login_registration_shortcode( $atts );
		} elseif ( 'true' === $atts['lost_password'] ) {
				return $this->wholesalex_forgot_password_form();
		} elseif ( 'true' === $atts['lost_password'] ) {
				return $this->wholesalex_forgot_password_form();
		} else {
			return '';
		}
	}

	/**
	 * Login Form
	 *
	 * @param  array $atts Shortcode Attributes. Default Empty.
	 * @return string Shortcode output.
	 * @since 1.4.9
	 * @since 1.4.9 _settings_redirect_url_login Field Default Settings Param Added
	 */
	public function login_shortcode( $atts = array() ) {
		if ( is_user_logged_in() && is_singular() ) {
			$__form_view_for_logged_in_user = wholesalex()->get_setting( '_settings_show_form_for_logged_in' );
			$__message_for_logged_in_user   = wholesalex()->get_setting( '_settings_message_for_logged_in_user' );
			if ( 'yes' !== $__form_view_for_logged_in_user ) {
				if ( is_admin() || ! function_exists( 'wc_add_notice' ) || ! function_exists( 'wc_print_notices' ) ) {
					return;
				}
				?>
				<div>
				<?php
				wc_add_notice( $__message_for_logged_in_user, 'error' );
				wc_print_notices();
				?>
					<a class="wsx-link" href="<?php echo esc_url_raw( wp_logout_url( get_permalink() ) ); ?>"> <?php echo esc_html( wholesalex()->get_language_n_text( '_language_logout_to_see_this_form', __( 'Logout to See this form', 'wholesalex' ) ) ); ?></a>
					</div>
				<?php
				return;
			}
		}
		if ( $this->check_current_page_is_lost_password() ) {
			return $this->render_login_shortcode( $atts );
		} elseif ( 'true' === $atts['lost_password'] ) {
				return $this->wholesalex_forgot_password_form();
		} elseif ( 'true' === $atts['lost_password'] ) {
				return $this->wholesalex_forgot_password_form();
		} else {
			return '';
		}
	}

	/**
	 * Load Form JS
	 *
	 * @param string $wrapper is the wrapper id.
	 * @return void
	 * @since 1.0.0
	 */
	public function load_form_js( $wrapper = '' ) {
		add_action(
			'wp_footer',
			function () use( $wrapper ) {
				$this->form_js( $wrapper );
			}
		);
	}

	/**
	 * Form JS
	 *
	 * @param string $wrapper is the wrapper id.
	 * @return void
	 * @since 1.0.0
	 */
	public function form_js( $wrapper ) {
		$form_data           = wholesalex()->get_new_form_builder_data();
		$initial_form_data   = wholesalex()->get_default_registration_form_fields();
		$registration_fields = ( isset( $form_data['registrationFields'] ) ? $form_data['registrationFields'] : $initial_form_data );

		$conditions         = array();
		$password_condition = array();
		$password_message   = '';
		foreach ( $registration_fields as $row ) {
			$columns = $row['columns'];
			foreach ( $columns as $field ) {
				if ( isset( $field['status'] ) && $field['status'] ) {
					if ( 'user_pass' === $field['name'] && isset( $field['passwordStrength'] ) ) {
						foreach ( $field['passwordStrength'] as $value ) {
							$password_condition[] = $value['value'];
						}
						$password_message = isset( $field['password_strength_message'] ) ? $field['password_strength_message'] : '';
					}
					if ( isset( $field['conditions'] ) && $field['conditions'] ) {
						$conditions[ $field['name'] ] = $field['conditions'];
					}
				}
			}
		}
		$conditions_js_array = '["' . implode( '", "', $password_condition ) . '"]';

		?>
		<script type="text/javascript">
			(function ($) {
				'use strict';
				/**
				 * All of the code for your public-facing JavaScript source
				 * should reside in this file.
				 */
				const password_message = <?php echo wp_json_encode( $password_message ); ?>;
				const wrapper = $(`#<?php echo esc_attr( $wrapper ); ?>`);

				const controlRegistrationForm = ()=>{
					// Check User Role Selection Field
					let selectedRole = wrapper.find('#wholesalex_registration_role').val();
					if(selectedRole) {
						let whxCustomFields = wrapper.find('.wsx-field');
						whxCustomFields.each(function (i) {
							let excludeRoles = this.getAttribute('data-wsx-exclude');
							if(excludeRoles) {
								excludeRoles = excludeRoles.split(' ');
								if(!excludeRoles.includes(selectedRole)) {
									$(this).show();
									$(this).find('.wsx-field-required').prop('required','true');
								} else {
									$(this).hide();
									$(this).find('.wsx-field-required').removeAttr('required');
								}
							}
						});
					} else {
						$(".wsx-field[style*='display: none'] > .wsx-field-required").removeAttr("required");
					}
				}

				const checkConfirmPassword = ()=>{
					wrapper.find("#user_confirm_pass").prop('required',true);
					let confirmPassword = wrapper.find("#user_confirm_pass").val();
					let password = wrapper.find("#reg_password").val(); //woocommerce password
					let whxFormPassword = wrapper.find("#user_pass").val();

					if(password && password.length) {
						wrapper.find('.woocommerce-form-register__submit').prop('disabled',true); // Disable Register button
					}

					if(whxFormPassword && whxFormPassword.length) {
						wrapper.find('.wsx-register-btn').prop('disabled',true); // Disable Register button
					}


					if(!confirmPassword) {
						confirmPassword = wrapper.find("#user_confirm_password").val();
					}
					

					if( confirmPassword ) {
						// For WC
						if( confirmPassword!==password) {
							wrapper.find(".whx-field-error.user_confirm_pass").empty();
							wrapper.find(".whx-field-error.user_confirm_pass").append("Password and Confirm Password Does not match!");
						} else {
							wrapper.find(".whx-field-error.user_confirm_pass").empty();
							wrapper.find('.woocommerce-form-register__submit').prop('disabled',false);
						}

						// For WholesaleX Form
						if(confirmPassword !=whxFormPassword) {
							wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_pass`).empty();
							wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_pass`).empty();

							wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_password`).empty();
							wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_pass`).empty();

							wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_pass`).append('Password and Confirm Password Does not match!');
							wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_password`).append('Password and Confirm Password Does not match!');
							wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_pass`).append('Password and Confirm Password Does not match!');

						} else {
							wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_pass`).empty();
							wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_pass`).empty();
							wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_password`).empty();
							wrapper.find('.wsx-register-btn').prop('disabled',false); // Disable Register button

						}
					} else {
						wrapper.find('.wsx-register-btn').prop('disabled',false); // Disable Register button
					}
				}

				const checkConfirmEmail = ()=>{
					
					wrapper.find("#user_confirm_email").prop('required',true);
					let confirmEmail = wrapper.find("#user_confirm_email").val();
					let email = wrapper.find("#reg_email").val(); //woocommerce password
					let whxFormEmail = wrapper.find("#user_email").val();


					if(email && email.length) {
						wrapper.find('.woocommerce-form-register__submit').prop('disabled',true); // Disable Register button
					}

					if(whxFormEmail && whxFormEmail.length) {
						wrapper.find('.wsx-register-btn').prop('disabled',true); // Disable Register button
					}
					
					if( confirmEmail ) {

						// For WC
						if( confirmEmail!==email) {
							wrapper.find(".whx-field-error.user_confirm_email").empty();
							wrapper.find(".whx-field-error.user_confirm_email").append("Email and Confirm Email Does not match!");
						} else {
							wrapper.find(".whx-field-error.user_confirm_email").empty();
							wrapper.find('.woocommerce-form-register__submit').prop('disabled',false);
						}

						// For WholesaleX Form
						if(confirmEmail !=whxFormEmail) {
							wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_email`).empty();
							wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_email`).empty();

							wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_email`).append('Email and Confirm Email Does not match!');
							wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_email`).append('Email and Confirm Email Does not match!');
							// $('.wsx-register-btn').prop('disabled',true); // Disable Register button


						} else {
							wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_email`).empty();
							wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_email`).empty();
							wrapper.find('.wsx-register-btn').prop('disabled',false); // Disable Register button

						}
					} else {
						wrapper.find('.wsx-register-btn').prop('disabled',false); // Disable Register button
					}
				}

				const checkRequiredField = ()=> {
					let isValid=true;
					wrapper.find(".wsx-field-required input, .wsx-field-required select, .wsx-field-required textarea").on('focusout input', function() {
						// Check the validity of the current field
						let fieldValue = $(this).val().trim();
						let fieldName = $(this).attr("name").replace(/\[\]/g, '');

						if (!fieldValue) {
							isValid = false;
							wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).text(`${fieldName.replace('_', ' ')} is required!`);
							wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').addClass('wsx-field-warning');

						} else {
							wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).text("");
							wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').removeClass('wsx-field-warning');

						}
					});

					// Validate at least one checkbox is checked in each checkbox group
					wrapper.find(".wsx-field-required .wsx-form-checkbox").each(function () {
						let checkboxes = $(this).find("input[type='checkbox']");
						let checkboxGroupName = $(this).find("input[type='checkbox']").attr("name").replace(/\[\]/g, '');

						if (checkboxes.length > 0 && checkboxes.filter(":checked").length === 0) {
							isValid = false;
						} else {
							// Clear warning message for the checkbox group
							wrapper.find(".wsx-form-field-warning-message." + checkboxGroupName).text("");
						}
					});


				}
				// Function to validate the password
				function validatePassword(password, conditions) {
					var messages = [];
					if (conditions.includes('uppercase_condition')) {
						if (!/[A-Z]/.test(password)) {
							messages.push('At least one uppercase letter <br>');
						}
					}
					if (conditions.includes('lowercase_condition')) {
						if (!/[a-z]/.test(password)) {
							messages.push('At least one lowercase letter <br>');
						}
					}
					if (conditions.includes('special_character_condition')) {
						if (!/[!@#$%^&*()_+=\\-]/.test(password)) {
							messages.push('At least one special character  <br>');
						}
					}
					if (conditions.includes('min_length_condition')) {
						if (password.length < 8) {
							messages.push('Minimum 8 characters <br>');
						}
					}
					return messages;
				}
				//Check Password Validation
				const checkPassWordRequiredFields = ()=> {
					let isPasswordValid = true;
					wrapper.find(".wsx-field-required input, .wsx-field-required select, .wsx-field-required textarea, .wsx-field-required radio").each(function() {
					let fieldName = $(this).attr("name");
							if(fieldName) {
								fieldName = fieldName.replace(/\[\]/g, '');
							}
					if ( $(this).attr("name") === 'user_pass') {
						let passwordConditions = <?php echo wp_json_encode( $conditions_js_array ); ?>;
							// Validate button click event
							let password = $(this).val();
							let validationMessages = validatePassword( password, passwordConditions );
							if (validationMessages.length > 0) {
								isPasswordValid = false;
								if(wrapper.find(`.wsx-field-${fieldName}`).css('display') !== 'none') {
									const $warningMessage = wrapper.find(`.wsx-form-field-warning-message.${fieldName}`);
									(password_message && password_message.length > 0) ?  $warningMessage.text(password_message) : $warningMessage.html(validationMessages);
									wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').addClass('wsx-field-warning');
							}
							} else {
								wrapper.find('#message').html('Password is valid!').css('color', 'green');
							}
					}
				});
					return isPasswordValid;
				}

				const checkRequiredFields = ()=> {

					// Validate required fields
					let isValid = true;


					wrapper.find(".wsx-field-required input, .wsx-field-required select, .wsx-field-required textarea, .wsx-field-required radio").each(function() {
						if ($(this).val().trim() === "") {
							let fieldName = $(this).attr("name");
							if(fieldName) {
								fieldName = fieldName.replace(/\[\]/g, '');
							}
							
							if(wrapper.find(`.wsx-field-${fieldName}`).css('display') !== 'none') {
									isValid = false;
									wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).text(`${fieldName.replace('_', ' ')} is required!`);
									wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').addClass('wsx-field-warning');
							}
						} else {
							// $(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').removeClass('wsx-field-warning');

						}
					});
						
					// Validate at least one checkbox is checked in each checkbox group
					wrapper.find(".wsx-field-required .wsx-form-checkbox").each(function () {
						let checkboxes = $(this).find("input[type='checkbox']");
						let fieldName = '';
						if(checkboxes.length) {
							fieldName = checkboxes[0].name;
							fieldName = fieldName.replace(/\[\]/g, '');
						}
						
						if(wrapper.find(`.wsx-field-${fieldName}`).css('display') !== 'none') { 

							if (checkboxes.length > 0 && checkboxes.filter(":checked").length === 0) {
								isValid = false;
								
								wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).text(`${fieldName.replace('_', ' ')} is required!`);
								wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').addClass('wsx-field-warning');

							} else {
								wrapper.find(this).closest('.wsx-form-field').find('.wsx-form-field-warning-message').text("");
								wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').removeClass('wsx-field-warning');

							}

						}
						 
					});
					// Validate at least one checkbox is checked in each checkbox group
					wrapper.find(".wsx-field-required .wsx-field-radio").each(function () {
						let checkboxes = $(this).find("input[type='radio']");
						let fieldName = '';
						if(checkboxes.length) {
							fieldName = checkboxes[0].name;
							fieldName = fieldName.replace(/\[\]/g, '');
						}
						
						if(wrapper.find(`.wsx-field-${fieldName}`).css('display') !== 'none') { 

							if (checkboxes.length > 0 && checkboxes.filter(":checked").length === 0) {
								isValid = false;
								
								$(`.wsx-form-field-warning-message.${fieldName}`).text(`${fieldName.replace('_', ' ')} is required!`);
								$(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').addClass('wsx-field-warning');

							} else {
								$(this).closest('.wsx-form-field').find('.wsx-form-field-warning-message').text("");
								$(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').removeClass('wsx-field-warning');

							}

						}
						 
					});

					return isValid;

				}
				const checkLoginRequiredFields = ()=> {

					// Validate required fields
					let isValid = true;


					wrapper.find(".wholesalex-login-form .wsx-field-required input").each(function() {
						if ($(this).val().trim() === "") {
							let fieldName = $(this).attr("name");
							if(fieldName) {
								fieldName = fieldName.replace(/\[\]/g, '');
							}
							
							if($(`.wsx-field-${fieldName}`).css('display') !== 'none') {
								isValid = false;
								$(`.wsx-form-field-warning-message.${fieldName}`).text(`${fieldName.replace('_', ' ')} is required!`);
								$(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').addClass('wsx-field-warning');
							}
						} else {
							// $(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').removeClass('wsx-field-warning');

						}
					});



					return isValid;

				}

				function toggleRequiredAttribute() {
					wrapper.find(".wsx-field-required").each(function() {
					let fieldWrapper = $(this);
					let fieldInput = fieldWrapper.find("input, select, textarea");

					// Check for specific field types and handle accordingly
					if (fieldInput.is(":file")) {
						// For file inputs, consider them required if the wrapper is visible
						if (fieldWrapper.css("display") === "none") {
						fieldInput.removeAttr("required");
						} else {
						fieldInput.attr("required", "required");
						}
					} else {
						// For other input types, toggle based on display style
						if (fieldWrapper.css("display") === "none") {
						fieldInput.removeAttr("required");
						} else {
						fieldInput.attr("required", "required");
						}
					}
					});
				}


				const handleHiddenRow = ()=>{
					wrapper.find('.wsx-reg-form-row').each(function () {
						const $row = $(this);
						const allChildrenHidden = $row.children().toArray().every(function (child) {
							return $(child).css('display') === 'none';
						});

						if (allChildrenHidden) {
							$row.css('display', 'none');
						} else {
							if ($row.hasClass('double-column')) {
								$row.css('display', 'flex');
							} else {
								$row.css('display', 'block');
							}
						}
					});
				}

				

				const handleCondition = ()=>{
					const conditions = <?php echo wp_json_encode( $conditions ); // phpcs:ignore ?>

					Object.keys(conditions).forEach(name => {
							if(conditions[name] && (conditions[name]['tiers'] &&conditions[name]['tiers'][0]&& conditions[name]['tiers'][0]['field'] && conditions[name]['tiers'][0]['condition'])) {
								let condition = conditions[name];
								let tiers = condition['tiers'];
								let status = condition['status'];
								let relation = condition['relation'];


								if(relation=='any') {
									let _status = false;
									for (let index = 0; index < tiers.length; index++) {
										const element = tiers[index];
										
										if(element['condition']=='is') {
											let val = $(".wsx-field *[name="+element['field']+"]").val();
											if(val==element['value']) {
												_status = true;
											}
										}
										else if(element['condition']=='not_is') {
											let val = $(".wsx-field *[name="+element['field']+"]").val();
											
											if(val!=element['value']) {
												_status = true;
											}
										}
										
									}
									if(_status) {
										if(status=='hide') {
											$(".wsx-field.wsx-field-"+name).hide();
											// $(this).find('.wsx-field-required').prop('required','true');

										} else {
											$(".wsx-field.wsx-field-"+name).show();
										}
									} else {
										if(status=='hide') {
											$(".wsx-field.wsx-field-"+name).show();
										} else {
											$(".wsx-field.wsx-field-"+name).hide();
										}
									}

								} else if(relation=='all') {
									let _status = false;
									tiers.forEach(element => {
										if(element['condition']=='is') {
											let val = $(".wsx-field *[name="+element['field']+"]").val();
											if(val!=element['value']) {
												_status = true;
												return;
											}
										}
										else if(element['condition']=='not_is') {
											let val = $(".wsx-field *[name="+element['field']+"]").val();
											if(val==element['value']) {
												_status = true;
												return;
											}
										}
									});
									if(_status) {
										if(status=='hide') {
											$(".wsx-field.wsx-field-"+name).show();
										} else {
											$(".wsx-field.wsx-field-"+name).hide();
										}
									} else {
										if(status=='hide') {
											$(".wsx-field.wsx-field-"+name).hide();
										} else {
											$(".wsx-field.wsx-field-"+name).show();
										}	
									}
								}
							}
						});
				}
				checkConfirmPassword();
				checkConfirmEmail();
				
				
				$(document).ready(function(){
					wrapper.find('#wholesalex_registration_role').change(controlRegistrationForm);

					handleCondition();
					handleHiddenRow();

					wrapper.find('.wsx-field input, .wsx-field textarea, .wsx-field radio, .wsx-field select').on('change input',function(e){
						checkConfirmPassword();
						checkConfirmEmail();
						controlRegistrationForm();
						handleCondition();
						checkRequiredField();
						// toggleRequiredAttribute();
						handleHiddenRow();
					});

					wrapper.find('.wsx-form-field input[type=file]').on('change',function(e){
						const element = $(e.target);
						const fileName = element.val().replace(/C:\\fakepath\\/i, '');
						const fileLabel = element.parent().find('.wsx-file-name');
						fileLabel.empty(); // Make Label Empty
						fileLabel.append(fileName);
					});
				});

				const processRegistration = (formObject)=>{
					const entries = Object.fromEntries(formObject.entries());

					// Check if reCAPTCHA is empty
					const recaptchaValue = entries['g-recaptcha-response'];

					if (typeof recaptchaValue === 'string' && recaptchaValue.trim() === '') {
						alert('Please complete the reCAPTCHA checkbox.');
						return;
					}
					wrapper.find('.wholesalex_circular_loading__wrapper').show();
					$.ajax({ 
						url: wholesalex.ajax, 
						type: 'POST', 
						data: formObject, 
						contentType: false,
						processData: false,
						success: function (response) { 
							if(Object.keys(response['data']['error_messages']).length) {
								const wc_notice = $('.woocommerce-notices-wrapper');
								if(wc_notice) {
									wc_notice.empty();
								}
								wrapper.find('.wholesalex-registration-form .wsx-form-field-warning-message').empty();
								Object.keys(response['data']['error_messages']).map((err)=>{
									wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.${err}`).append(response['data']['error_messages'][err]);
									wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.${err}`).parent().find('.wsx-form-field').addClass('wsx-field-warning');

									if(err=='recaptcha') {
											if(wc_notice) {
												wc_notice.append(response['data']['error_messages'][err]);
											}
										}
								})
							} else {
								if(response['data']['redirect']) {
									window.location.href = response['data']['redirect'];
								}
							}
							wrapper.find('.wholesalex_circular_loading__wrapper').hide();
						}, 
						error: function (jqXHR, textStatus, errorThrown) { 
							wrapper.find('.wholesalex_circular_loading__wrapper').hide();
						} 
					}); 
				}

				// Process Registration
				wrapper.find('.wsx-register-btn').on('click',function(e){
					// e.preventDefault();

					if($(this).closest('form')[0].checkValidity()){
						e.preventDefault();
					}

					if(!checkRequiredFields()) {
						return;
					}				
					if(!checkPassWordRequiredFields()) {
						return;
					}				
									
					const formObject = new FormData(wrapper.find('.wholesalex-registration-form')[0]);

					
					// process_registration
					if (wholesalex.recaptcha_status === 'yes' && typeof grecaptcha !== 'undefined' ) {
							let site_key      = "<?php echo esc_attr( wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' ) ); ?>";
							grecaptcha.ready(function () {
								try {
									grecaptcha.execute(site_key, { action: 'submit' }).then(function (token) {
										formObject.append('token',token);
										processRegistration(formObject);
									});
								} catch (error) {
									processRegistration(formObject);
								}
								
							});
							
					} else {
						processRegistration(formObject);
					}
				});

				wrapper.find('.wholesalex-login-form').find('input, select').on('change input',function(e){
					wrapper.find('.wholesalex-login-form .wsx-form-field').removeClass('wsx-field-warning');
					wrapper.find('.wholesalex-login-form .wsx-form-field-warning-message').empty();
				});

				

				// Process Login
				wrapper.find('.wsx-login-btn').on('click',function(e){
					if($(this).closest('form')[0].checkValidity()){
						e.preventDefault();
					}

					if(!checkLoginRequiredFields()) {
						return;
					}
					const processLogin  = ()=>{
						$.ajax({ 
							url: wholesalex.ajax, 
							type: 'POST', 
							data: formObject, 
							contentType: false,
							processData: false,
							success: function (response) { 
								if(Object.keys(response['data']['error_messages']).length) {
									const wc_notice = $('.woocommerce-notices-wrapper');
									if(wc_notice) {
										wc_notice.empty();
									}
									wrapper.find('.wholesalex-login-form .wsx-form-field-warning-message').empty();
									Object.keys(response['data']['error_messages']).map((err)=>{
										wrapper.find(`.wholesalex-login-form .wsx-form-field-warning-message.${err}`).append(response['data']['error_messages'][err]);
										wrapper.find(`.wholesalex-login-form .wsx-form-field-warning-message.${err}`).parent().find('.wsx-form-field').addClass('wsx-field-warning');

										if(err=='recaptcha') {
											if(wc_notice) {
												wc_notice.append(response['data']['error_messages'][err]);
											}
										}
									})
								} else {
									if(response['data']['redirect']) {
										window.location.href = response['data']['redirect'];
									}
								}
							}, 
							error: function (jqXHR, textStatus, errorThrown) { 
							} 
						});
					}

					const formObject = new FormData(wrapper.find('.wholesalex-login-form')[0]);
						if (wholesalex.recaptcha_status === 'yes' && wholesalex.settings.recaptcha_version!="recaptcha_v2" && typeof grecaptcha !== 'undefined' ) {
							let site_key      = "<?php echo esc_attr( wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' ) ); ?>";
							grecaptcha.ready(function () {
								try {
									grecaptcha.execute(site_key, { action: 'submit' }).then(function (token) {
										formObject.append('token',token);
										processLogin();
									});
								} catch (error) {
								}
								
							});
							processLogin();
							
					} else {
						processLogin();
					}
				});

				

			})(jQuery);
		</script>
		<?php
	}



	/**
	 * Get Form Style
	 *
	 * @param  array $style Style Array.
	 * @param  array $login_header_style Login Header Style Array.
	 * @param  array $registration_header_style Registration Header Style Array.
	 * @return array Style Array.
	 * @since 1.0.0
	 */
	public function get_form_style( $style, $login_header_style, $registration_header_style ) {

		$_style = array(

			// Color
			// Field Sign Up Normal.

			'--wsx-input-color'                          => isset( $style['color']['field']['signUp']['normal']['text'] ) ? $style['color']['field']['signUp']['normal']['text'] : null,
			'--wsx-input-bg'                             => isset( $style['color']['field']['signUp']['normal']['background'] ) ? $style['color']['field']['signUp']['normal']['background'] : null,
			'--wsx-input-border-color'                   => isset( $style['color']['field']['signUp']['normal']['border'] ) ? $style['color']['field']['signUp']['normal']['border'] : null,
			'--wsx-input-placeholder-color'              => isset( $style['color']['field']['signUp']['normal']['placeholder'] ) ? $style['color']['field']['signUp']['normal']['placeholder'] : null,
			'--wsx-form-label-color'                     => isset( $style['color']['field']['signUp']['normal']['label'] ) ? $style['color']['field']['signUp']['normal']['label'] : null,

			// Field Sign Up Active.
			'--wsx-input-focus-color'                    => isset( $style['color']['field']['signUp']['active']['text'] ) ? $style['color']['field']['signUp']['active']['text'] : null,
			'--wsx-input-focus-bg'                       => isset( $style['color']['field']['signUp']['active']['background'] ) ? $style['color']['field']['signUp']['active']['background'] : null,
			'--wsx-input-focus-border-color'             => isset( $style['color']['field']['signUp']['active']['border'] ) ? $style['color']['field']['signUp']['active']['border'] : null,
			'--wsx-form-label-color-active'              => isset( $style['color']['field']['signUp']['active']['label'] ) ? $style['color']['field']['signUp']['active']['label'] : null,

			// Field Sign Up Warning.

			'--wsx-input-warning-color'                  => isset( $style['color']['field']['signUp']['warning']['text'] ) ? $style['color']['field']['signUp']['warning']['text'] : null,
			'--wsx-input-warning-bg'                     => isset( $style['color']['field']['signUp']['warning']['background'] ) ? $style['color']['field']['signUp']['warning']['background'] : null,
			'--wsx-input-warning-border-color'           => isset( $style['color']['field']['signUp']['warning']['border'] ) ? $style['color']['field']['signUp']['warning']['border'] : null,
			'--wsx-form-label-color-warning'             => isset( $style['color']['field']['signUp']['warning']['label'] ) ? $style['color']['field']['signUp']['warning']['label'] : null,

			// Field Sign In Normal.

			'--wsx-login-input-color'                    => isset( $style['color']['field']['signIn']['normal']['text'] ) ? $style['color']['field']['signIn']['normal']['text'] : null,
			'--wsx-login-input-bg'                       => isset( $style['color']['field']['signIn']['normal']['background'] ) ? $style['color']['field']['signIn']['normal']['background'] : null,
			'--wsx-login-input-border-color'             => isset( $style['color']['field']['signIn']['normal']['border'] ) ? $style['color']['field']['signIn']['normal']['border'] : null,
			'--wsx-login-input-placeholder-color'        => isset( $style['color']['field']['signIn']['normal']['placeholder'] ) ? $style['color']['field']['signIn']['normal']['placeholder'] : null,
			'--wsx-login-form-label-color'               => isset( $style['color']['field']['signIn']['normal']['label'] ) ? $style['color']['field']['signIn']['normal']['label'] : null,

			// Field Sign In Active.
			'--wsx-login-input-focus-color'              => isset( $style['color']['field']['signIn']['active']['text'] ) ? $style['color']['field']['signIn']['active']['text'] : null,
			'--wsx-login-input-focus-bg'                 => isset( $style['color']['field']['signIn']['active']['background'] ) ? $style['color']['field']['signIn']['active']['background'] : null,
			'--wsx-login-input-focus-border-color'       => isset( $style['color']['field']['signIn']['active']['border'] ) ? $style['color']['field']['signIn']['active']['border'] : null,
			'--wsx-login-form-label-color-active'        => isset( $style['color']['field']['signIn']['active']['label'] ) ? $style['color']['field']['signIn']['active']['label'] : null,

			// Field Sign In Warning.
			'--wsx-login-input-warning-color'            => isset( $style['color']['field']['signIn']['warning']['text'] ) ? $style['color']['field']['signIn']['warning']['text'] : null,
			'--wsx-login-input-warning-bg'               => isset( $style['color']['field']['signIn']['warning']['background'] ) ? $style['color']['field']['signIn']['warning']['background'] : null,
			'--wsx-login-input-warning-border-color'     => isset( $style['color']['field']['signIn']['warning']['border'] ) ? $style['color']['field']['signIn']['warning']['border'] : null,
			'--wsx-login-form-label-color-warning'       => isset( $style['color']['field']['signIn']['warning']['label'] ) ? $style['color']['field']['signIn']['warning']['label'] : null,

			// Button Sign UP Normal.
			'--wsx-form-button-color'                    => isset( $style['color']['button']['signUp']['normal']['text'] ) ? $style['color']['button']['signUp']['normal']['text'] : null,
			'--wsx-form-button-bg'                       => isset( $style['color']['button']['signUp']['normal']['background'] ) ? $style['color']['button']['signUp']['normal']['background'] : null,
			'--wsx-form-button-border-color'             => isset( $style['color']['button']['signUp']['normal']['border'] ) ? $style['color']['button']['signUp']['normal']['border'] : null,

			// Button Sign UP Hover.
			'--wsx-form-button-hover-color'              => isset( $style['color']['button']['signUp']['hover']['text'] ) ? $style['color']['button']['signUp']['hover']['text'] : null,
			'--wsx-form-button-hover-bg'                 => isset( $style['color']['button']['signUp']['hover']['background'] ) ? $style['color']['button']['signUp']['hover']['background'] : null,
			'--wsx-form-button-hover-border-color'       => isset( $style['color']['button']['signUp']['hover']['border'] ) ? $style['color']['button']['signUp']['hover']['border'] : null,

			// Button Sign In Normal.
			'--wsx-login-form-button-color'              => isset( $style['color']['button']['signIn']['normal']['text'] ) ? $style['color']['button']['signIn']['normal']['text'] : null,
			'--wsx-login-form-button-bg'                 => isset( $style['color']['button']['signIn']['normal']['background'] ) ? $style['color']['button']['signIn']['normal']['background'] : null,
			'--wsx-login-form-button-border-color'       => isset( $style['color']['button']['signIn']['normal']['border'] ) ? $style['color']['button']['signIn']['normal']['border'] : null,

			// Button Sign In Hover.
			'--wsx-login-form-button-hover-color'        => isset( $style['color']['button']['signIn']['hover']['text'] ) ? $style['color']['button']['signIn']['hover']['text'] : null,
			'--wsx-login-form-button-hover-bg'           => isset( $style['color']['button']['signIn']['hover']['background'] ) ? $style['color']['button']['signIn']['hover']['background'] : null,
			'--wsx-login-form-button-hover-border-color' => isset( $style['color']['button']['signIn']['hover']['border'] ) ? $style['color']['button']['signIn']['hover']['border'] : null,

			// Container Main.
			'--wsx-form-container-bg'                    => isset( $style['color']['container']['main']['background'] ) ? $style['color']['container']['main']['background'] : null,
			'--wsx-form-container-border-color'          => isset( $style['color']['container']['main']['border'] ) ? $style['color']['container']['main']['border'] : null,

			// Container Sign UP.
			'--wsx-form-reg-bg'                          => isset( $style['color']['container']['signUp']['background'] ) ? $style['color']['container']['signUp']['background'] : null,
			'--wsx-form-reg-border-color'                => isset( $style['color']['container']['signUp']['border'] ) ? $style['color']['container']['signUp']['border'] : null,

			// Container Sign IN.
			'--wsx-login-bg'                             => isset( $style['color']['container']['signIn']['background'] ) ? $style['color']['container']['signIn']['background'] : null,
			'--wsx-login-border-color'                   => isset( $style['color']['container']['signIn']['border'] ) ? $style['color']['container']['signIn']['border'] : null,

			// Typography.
			// Field - Label.
			'--wsx-form-label-font-size'                 => isset( $style['typography']['field']['label']['size'] ) ? $style['typography']['field']['label']['size'] . 'px' : null,
			'--wsx-form-label-weight'                    => isset( $style['typography']['field']['label']['weight'] ) ? $style['typography']['field']['label']['weight'] : null,
			'--wsx-form-label-case-transform'            => isset( $style['typography']['field']['label']['transform'] ) ? $style['typography']['field']['label']['transform'] . 'px' : null,
			// Field - Input.
			'--wsx-input-font-size'                      => isset( $style['typography']['field']['input']['size'] ) ? $style['typography']['field']['input']['size'] . 'px' : null,
			'--wsx-input-weight'                         => isset( $style['typography']['field']['input']['weight'] ) ? $style['typography']['field']['input']['weight'] : null,
			'--wsx-input-case-transform'                 => isset( $style['typography']['field']['input']['transform'] ) ? $style['typography']['field']['input']['transform'] : null,

			// Button.

			'--wsx-form-button-font-size'                => isset( $style['typography']['button']['size'] ) ? $style['typography']['button']['size'] . 'px' : null,
			'--wsx-form-button-weight'                   => isset( $style['typography']['button']['weight'] ) ? $style['typography']['button']['weight'] : null,
			'--wsx-form-button-case-transform'           => isset( $style['typography']['button']['transform'] ) ? $style['typography']['button']['transform'] : null,

			// Size and Spacing
			// Input.
			'--wsx-input-padding'                        => isset( $style['sizeSpacing']['input']['padding'] ) ? $style['sizeSpacing']['input']['padding'] . 'px' : null,
			'--wsx-input-width'                          => isset( $style['sizeSpacing']['input']['width'] ) ? $style['sizeSpacing']['input']['width'] . 'px' : null,
			'--wsx-input-border-width'                   => isset( $style['sizeSpacing']['input']['border'] ) ? $style['sizeSpacing']['input']['border'] . 'px' : null,
			'--wsx-input-border-radius'                  => isset( $style['sizeSpacing']['input']['borderRadius'] ) ? $style['sizeSpacing']['input']['borderRadius'] . 'px' : null,

			// Button.
			'--wsx-form-button-padding'                  => isset( $style['sizeSpacing']['button']['padding'] ) ? $style['sizeSpacing']['button']['padding'] . 'px' : null,
			'--wsx-form-button-width'                    => isset( $style['sizeSpacing']['button']['width'] ) ? $style['sizeSpacing']['button']['width'] . '%' : null,
			'--wsx-form-button-border-width'             => isset( $style['sizeSpacing']['button']['border'] ) ? $style['sizeSpacing']['button']['border'] . 'px' : null,
			'--wsx-form-button-border-radius'            => isset( $style['sizeSpacing']['button']['borderRadius'] ) ? $style['sizeSpacing']['button']['borderRadius'] . 'px' : null,
			'--wsx-form-button-align'                    => isset( $style['sizeSpacing']['button']['align'] ) ? $style['sizeSpacing']['button']['align'] : null,

			// Container - Main.
			'--wsx-form-container-width'                 => isset( $style['sizeSpacing']['container']['main']['width'] ) ? $style['sizeSpacing']['container']['main']['width'] . 'px' : null,
			'--wsx-form-container-border-width'          => isset( $style['sizeSpacing']['container']['main']['border'] ) ? $style['sizeSpacing']['container']['main']['border'] . 'px' : null,
			'--wsx-form-container-border-radius'         => isset( $style['sizeSpacing']['container']['main']['borderRadius'] ) ? $style['sizeSpacing']['container']['main']['borderRadius'] . 'px' : null,
			'--wsx-form-container-padding'               => isset( $style['sizeSpacing']['container']['main']['padding'] ) ? $style['sizeSpacing']['container']['main']['padding'] . 'px' : null,
			'--wsx-form-container-separator'             => isset( $style['sizeSpacing']['container']['main']['separator'] ) ? $style['sizeSpacing']['container']['main']['separator'] . 'px' : null,

			// Container - Sign In.
			'--wsx-login-width'                          => isset( $style['sizeSpacing']['container']['signIn']['width'] ) ? $style['sizeSpacing']['container']['signIn']['width'] . 'px' : null,
			'--wsx-login-border-width'                   => isset( $style['sizeSpacing']['container']['signIn']['border'] ) ? $style['sizeSpacing']['container']['signIn']['border'] . 'px' : null,
			'--wsx-login-padding'                        => isset( $style['sizeSpacing']['container']['signIn']['padding'] ) ? $style['sizeSpacing']['container']['signIn']['padding'] . 'px' : null,
			'--wsx-login-border-radius'                  => isset( $style['sizeSpacing']['container']['signIn']['borderRadius'] ) ? $style['sizeSpacing']['container']['signIn']['borderRadius'] . 'px' : null,

			// Container - Sign Up.
			'--wsx-form-reg-width'                       => isset( $style['sizeSpacing']['container']['signUp']['width'] ) ? $style['sizeSpacing']['container']['signUp']['width'] . 'px' : null,
			'--wsx-form-reg-border-width'                => isset( $style['sizeSpacing']['container']['signUp']['border'] ) ? $style['sizeSpacing']['container']['signUp']['border'] . 'px' : null,
			'--wsx-form-reg-padding'                     => isset( $style['sizeSpacing']['container']['signUp']['padding'] ) ? $style['sizeSpacing']['container']['signUp']['padding'] . 'px' : null,
			'--wsx-form-reg-border-radius'               => isset( $style['sizeSpacing']['container']['signUp']['borderRadius'] ) ? $style['sizeSpacing']['container']['signUp']['borderRadius'] . 'px' : null,

			'--wsx-login-title-font-size'                => isset( $login_header_style['title']['size'] ) ? $login_header_style['title']['size'] . 'px' : null,
			'--wsx-login-title-case-transform'           => isset( $login_header_style['title']['transform'] ) ? $login_header_style['title']['transform'] : null,
			'--wsx-login-title-font-weight'              => isset( $login_header_style['title']['weight'] ) ? $login_header_style['title']['weight'] : null,
			'--wsx-login-title-color'                    => isset( $login_header_style['title']['color'] ) ? $login_header_style['title']['color'] : null,

			'--wsx-login-description-font-size'          => isset( $login_header_style['description']['size'] ) ? $login_header_style['description']['size'] . 'px' : null,
			'--wsx-login-description-case-transform'     => isset( $login_header_style['description']['transform'] ) ? $login_header_style['description']['transform'] : null,
			'--wsx-login-description-font-weight'        => isset( $login_header_style['description']['weight'] ) ? $login_header_style['description']['weight'] : null,
			'--wsx-login-description-color'              => isset( $login_header_style['description']['color'] ) ? $login_header_style['description']['color'] : null,

			'--wsx-reg-title-font-size'                  => isset( $registration_header_style['title']['size'] ) ? $registration_header_style['title']['size'] . 'px' : null,
			'--wsx-reg-title-case-transform'             => isset( $registration_header_style['title']['transform'] ) ? $registration_header_style['title']['transform'] : null,
			'--wsx-reg-title-font-weight'                => isset( $registration_header_style['title']['weight'] ) ? $registration_header_style['title']['weight'] : null,
			'--wsx-reg-title-color'                      => isset( $registration_header_style['title']['color'] ) ? $registration_header_style['title']['color'] : null,

			'--wsx-reg-description-font-size'            => isset( $registration_header_style['description']['size'] ) ? $registration_header_style['description']['size'] . 'px' : null,
			'--wsx-reg-description-case-transform'       => isset( $registration_header_style['description']['transform'] ) ? $registration_header_style['description']['transform'] : null,
			'--wsx-reg-description-font-weight'          => isset( $registration_header_style['description']['weight'] ) ? $registration_header_style['description']['weight'] : null,
			'--wsx-reg-description-color'                => isset( $registration_header_style['description']['color'] ) ? $registration_header_style['description']['color'] : null,
		);

		return $_style;
	}

	/**
	 * Get CSS
	 *
	 * @param  mixed $vars Variables.
	 * @return string CSS.
	 * @since 1.0.0
	 */
	private function get_vars_css( $vars ) {

		$result = '';

		foreach ( $vars as $name => $value ) {
			$result .= "{$name}: {$value};\n";
		}

		return $result;
	}

	/**
	 * Check Depends
	 *
	 * @param  mixed $field Variables.
	 * @return string CSS.
	 * @since 1.0.0
	 */
	public function check_depends( $field ) {
		$exclude_string = '';
		if ( isset( $field['excludeRoles'] ) && ! empty( $field['excludeRoles'] ) && is_array( $field['excludeRoles'] ) ) {
			foreach ( $field['excludeRoles'] as $role ) {
				$exclude_string .= $role['value'] . ' ';
			}
		}
		return $exclude_string;
	}

	/**
	 * Render Term & Condition Field
	 *
	 * @param array $field Field Array.
	 * @param bool  $is_label_hide Is Label Hide.
	 * @return void
	 */
	public function render_term_condition_field( $field, $is_label_hide ) {
		?>
		<div class="wsx-form-field wsx-form-checkbox">
			<?php
			$term_link        = '';
			$term_link_markup = '';
			if ( isset( $field['term_link'] ) && $field['term_link'] ) {
				$term_link = $field['term_link'];
			}
			if ( ! empty( $term_link ) && isset( $field['default_text'] ) && $field['default_text'] ) {
				preg_match_all( '/\{([^}]*)\}/', $field['default_text'], $matches );
				if ( ! empty( $matches[1] ) ) {
					$term_link        = sanitize_url( $term_link );
					$found            = false;
					$term_link_markup = preg_replace_callback(
						'/\{([^}]*)\}/',
						function ( $match ) use ( $term_link, &$found ) {
							if ( ! $found ) {
								$found = true;
								return '<a class="wsx-link" href="' . $term_link . '">' . $match[1] . '</a>';
							}
							return $match[0];
						},
						$field['default_text']
					);
				} else {
					$term_link_markup = str_replace( array( '{', '}' ), '', $field['default_text'] );
				}
			} else {
				$term_link_markup = str_replace( array( '{', '}' ), '', $field['default_text'] );
			}

			if ( ! $is_label_hide ) {
				?>
				<div class="wsx-field-heading">
					<?php
					if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
						?>
						<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
							<?php echo esc_html( $field['label'] ); ?>
							<?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
																<span aria-label="required">*</span>
								<?php
							}
							?>
						</div>
					<?php endif; ?>
				</div>
				<?php
			}
			?>
			<div class="wsx-field-content">
				<div class="wholesalex-field-wrap">
					<input type="checkbox" class="input-checkbox" id="<?php echo esc_attr( $field['name'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( $field['name'] ); ?>" />
					<label class="wsx-label wsx-label" for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo wp_kses_post( $term_link_markup ); ?></label>
				</div>
			</div>
			<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
				<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
			<?php endif; ?>
		</div>
			<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
		<?php
	}

	/**
	 * Gemnerate Form Field
	 *
	 * @param  array  $field Field Array.
	 * @param  bool   $is_role_wise Is Rolewise.
	 * @param  string $input_variation Input Variation.
	 * @param  bool   $is_only_b2b Is Only B2B.
	 * @return array Role Field.
	 */
	public function generate_form_field( $field, $is_role_wise = false, $input_variation = '', $is_only_b2b = false ) {
		if ( ! isset( $field['name'] ) ) {
			return;
		}
		if ( $is_role_wise && 'wholesalex_registration_role' === $field['name'] ) {
			return;
		}
		$exclude = $this->check_depends( $field );

		if ( $is_role_wise ) {
			$exclude_roles = explode( ' ', $exclude );
			if ( in_array( $is_role_wise, $exclude_roles, true ) ) {
				return;
			}
		}
		if ( ( ! $is_role_wise || $is_only_b2b ) && 'select' === $field['type'] && 'wholesalex_registration_role' === $field['name'] ) {
			$field['option'] = $this->get_select_role_field( $is_only_b2b )['columns'][0]['option'];
		}
		$is_label_hide = isset( $field['isLabelHide'] ) && $field['isLabelHide'];

		ob_start();
		switch ( $input_variation ) {
			case 'variation_1':
			case 'variation_3':
				switch ( $field['type'] ) {
					case 'text':
					case 'email':
					case 'number':
					case 'date':
						?>
						<div  class="wsx-form-field">
							<?php
							if ( ! $is_label_hide ) {
								?>
								<div class="wsx-field-heading">
								<?php
								if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
									?>
									<label class='wsx-label wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
										<?php echo esc_html( $field['label'] ); ?>
										<?php
										if ( isset( $field['required'] ) && $field['required'] ) {
											?>
												<span aria-label="required">*</span>
											<?php
										}
										?>
									</label>
								<?php endif; ?>
								</div>
								<?php
							}
							?>
							
							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>"  placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>" />
						</div>
						
						<?php
						if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) :
							?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'select':
						?>
						<div class="wsx-form-field">
							<?php
							if ( ! $is_label_hide ) {
								?>
									<div class="wsx-field-heading">
									<?php
									if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
										?>
										<label class='wsx-label wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
											<?php echo esc_html( $field['label'] ); ?>
											<?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
													<span aria-label="required">*</span>
												<?php
											}
											?>
										</label>
									<?php endif; ?>
								</div>
								<?php
							}
							?>
							
							<select class="wsx-select" name="<?php echo esc_attr( $field['name'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>">
								<?php foreach ( $field['option'] as $option ) : ?>
									<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_html( $option['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<?php
						if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) :
							?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'checkbox':
						?>
						<div class="wsx-form-field wsx-form-checkbox">
							<?php
							if ( ! $is_label_hide ) {
								?>
								<div class="wsx-field-heading">
									<?php
									if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
										?>
										<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
											<?php echo esc_html( $field['label'] ); ?>
											<?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
													<span aria-label="required">*</span>
												<?php
											}
											?>
										</div>
									<?php endif; ?>
								</div>

								<?php
							}
							?>
							
							<div class="wsx-field-content">
								<?php foreach ( $field['option'] as $option ) : ?>
									<div class="wholesalex-field-wrap">
										<input class="wsx-checkbox" type="checkbox" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>[]" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<label class="wsx-label wsx-label" for="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></label>
									</div>
								<?php endforeach; ?>
							</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php endif; ?>
						</div>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;
					case 'termCondition':
						$this->render_term_condition_field( $field, $is_label_hide );
						break;

					case 'radio':
						?>
						<div class="wsx-form-field wsx-field-radio">
							<?php
							if ( ! $is_label_hide ) {
								?>
								<div class="wsx-field-heading">
								<?php
								if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
									?>
									<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
										<?php echo esc_html( $field['label'] ); ?>
										<?php
										if ( isset( $field['required'] ) && $field['required'] ) {
											?>
												<span aria-label="required">*</span>
											<?php
										}
										?>
									</div>
								<?php endif; ?>
								</div>

								<?php
							}

							?>
							
							<div class="wsx-field-content">
								<?php foreach ( $field['option'] as $option ) : ?>
									<div class="wholesalex-field-wrap">
										<input class="wsx-radio" type="radio" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<label class="wsx-label wsx-label" for="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></label>
									</div>
								<?php endforeach; ?>
							</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
									<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php endif; ?>
						</div>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'file':
						?>
						<div class="wsx-form-field wsx-form-file">
							<?php
							if ( ! $is_label_hide ) {
								?>
									<div class="wsx-field-heading">
									<?php
									if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
										?>
											<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
											<?php echo esc_html( $field['label'] ); ?>
											<?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
													<span aria-label="required">*</span>
												<?php
											}
											?>
											</div>
										<?php endif; ?>
									</div>
									<?php
							}
							?>
							
							<label class="wsx-label wsx-field-content">
								<input class="wsx-input" type="<?php echo esc_attr( $field['type'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>"  name="<?php echo esc_attr( $field['name'] ); ?>" />
								<div class="wsx-file-label" for="<?php echo esc_attr( $field['name'] ); ?>">
									<span>
										<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
											<path d="M2.25 11.25V14.25C2.25 15.075 2.925 15.75 3.75 15.75H14.25C14.6478 15.75 15.0294 15.592 15.3107 15.3107C15.592 15.0294 15.75 14.6478 15.75 14.25V11.25M12.75 6L9 2.25L5.25 6M9 3.15V10.875" stroke="#6C6CFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
										Upload File
									</span>
									<div class="wsx-file-name">No File Chosen</div>
								</div> 
							</label>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php endif; ?>
						</div>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'tel':
						?>
						<div class="wsx-form-field">
							<?php
							if ( ! $is_label_hide ) {
								?>
									<div class="wsx-field-heading">
										<?php
										if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
											?>
											<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
												<?php echo esc_html( $field['label'] ); ?>
												<?php
												if ( isset( $field['required'] ) && $field['required'] ) {
													?>
														<span aria-label="required">*</span>
													<?php
												}
												?>
											</label>
										<?php endif; ?>
									</div>
								<?php
							}

							?>
							
							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type='tel' name="<?php echo esc_attr( $field['name'] ); ?>"   placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>" />
						</div>
						<?php
						if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) :
							?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'url':
						?>
						<div class="wsx-form-field">
							<?php
							if ( ! $is_label_hide ) {
								?>
								<div class="wsx-field-heading">
									<?php
									if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
										?>
										<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
											<?php echo esc_html( $field['label'] ); ?>
											<?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
														<span aria-label="required">*</span>
												<?php
											}
											?>
										</label>
									<?php endif; ?>
								</div>
								<?php
							}
							?>
							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type='url' name="<?php echo esc_attr( $field['name'] ); ?>"   placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>" />
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
						<?php endif; ?>
								<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'password':
						?>
						<div class="wsx-form-field">
							<?php
							if ( ! $is_label_hide ) {
								?>
									<div class="wsx-field-heading">
									<?php
									if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
										?>
											<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
											<?php echo esc_html( $field['label'] ); ?>
											<?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
													<span aria-label="required">*</span>
												<?php
											}
											?>
											</label>
										<?php endif; ?>
									</div>
									<?php
							}

							?>
							
							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type='password' name="<?php echo esc_attr( $field['name'] ); ?>"  minLength="<?php echo isset( $field['minLength'] ) ? esc_attr( $field['minLength'] ) : ''; ?>" maxLength="<?php echo isset( $field['maxLength'] ) ? esc_attr( $field['maxLength'] ) : ''; ?>" size="<?php echo isset( $field['size'] ) ? esc_attr( $field['size'] ) : ''; ?>"  placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>" />
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
						<?php endif; ?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'textarea':
						?>
						<div class="wsx-form-field">
							<?php
							if ( ! $is_label_hide ) {
								?>
									<div class="wsx-field-heading">
									<?php
									if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
										?>
											<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
											<?php echo esc_html( $field['label'] ); ?>
											<?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
													<span aria-label="required">*</span>
												<?php
											}
											?>
											</label>
										<?php endif; ?>
									</div>
									<?php
							}
							?>
							
							<textarea class="wsx-textarea" id="<?php echo esc_attr( $field['name'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>"  rows="<?php echo isset( $field['rows'] ) ? esc_attr( $field['rows'] ) : ''; ?>" cols="<?php echo isset( $field['cols'] ) ? esc_attr( $field['cols'] ) : ''; ?>" placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>"></textarea>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
						<?php endif; ?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					default:
						break;
				}
				break;
			case 'variation_2':
				switch ( $field['type'] ) {
					case 'text':
					case 'email':
					case 'number':
					case 'date':
					case 'url':
					case 'tel':
						?>
						<div class="wsx-form-field wsx-outline-focus">
							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>"  placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>" />
							<div class='wsx-form-label wsx-clone-label'><?php echo esc_html( $field['label'] ); ?> <?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
									<span aria-label="required">*</span>
								<?php
							}
							?>
							</div>
							<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
								<?php echo esc_html( $field['label'] ); ?> <?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
							</label>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'password':
						?>
						<div class="wsx-form-field wsx-outline-focus">
							<input type="<?php echo esc_attr( $field['type'] ); ?>" class="wsx-input wsx-form-field__input" id="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>"  name="<?php echo esc_attr( $field['name'] ); ?>" minLength="<?php echo isset( $field['minLength'] ) ? esc_attr( $field['minLength'] ) : ''; ?>" maxLength="<?php echo isset( $field['maxLength'] ) ? esc_attr( $field['maxLength'] ) : ''; ?>" size="<?php echo isset( $field['size'] ) ? esc_attr( $field['size'] ) : ''; ?>" />
							<div class='wsx-form-label wsx-clone-label'><?php echo esc_html( $field['label'] ); ?> <?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
									<span aria-label="required">*</span>
								<?php
							}
							?>
							</div>
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label class="wsx-label wsx-form-label" for="<?php echo esc_attr( $field['name'] ); ?>">
									<?php echo esc_html( $field['label'] ); ?> <?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
											<span aria-label="required">*</span>
										<?php
									}
									?>
								</label>
							<?php endif; ?>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'textarea':
						?>
						<div class="wsx-form-field wsx-outline-focus wsx-form-textarea">
							<textarea class="wsx-textarea" id="<?php echo esc_attr( $field['name'] ); ?>" class="wsx-form-field__textarea" name="<?php echo esc_attr( $field['name'] ); ?>"  rows="<?php echo isset( $field['rows'] ) ? esc_attr( $field['rows'] ) : ''; ?>" cols="<?php echo isset( $field['cols'] ) ? esc_attr( $field['cols'] ) : ''; ?>" placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>"></textarea>
							<div class='wsx-form-label wsx-clone-label'><?php echo esc_html( $field['label'] ); ?> <?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
									<span aria-label="required">*</span>
								<?php
							}
							?>
							</div>
							<label class="wsx-label wsx-form-label" for="<?php echo esc_attr( $field['name'] ); ?>">
								<?php echo esc_html( $field['label'] ); ?> <?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
							</label>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'file':
						?>
						<div class="wsx-form-field wsx-form-file wsx-file-outline">
							<label class="wsx-label wsx-field-content" for="<?php echo esc_attr( $field['name'] ); ?>">
								<input class="wsx-input" type="<?php echo esc_attr( $field['type'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>"  name="<?php echo esc_attr( $field['name'] ); ?>" />
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
										<div class="wsx-form-label wsx-clone-label"><?php echo esc_html( $field['label'] ); ?> <?php
										if ( isset( $field['required'] ) && $field['required'] ) {
											?>
												<span aria-label="required">*</span>
											<?php
										}
										?>
												</div>
									<?php endif; ?>
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
										<div class="wsx-form-label"><?php echo esc_html( $field['label'] ); ?> <?php
										if ( isset( $field['required'] ) && $field['required'] ) {
											?>
												<span aria-label="required">*</span>
											<?php
										}
										?>
												</div>
									<?php endif; ?>
									<div class="wsx-file-label">
										<span>
											<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
												<path d="M2.25 11.25V14.25C2.25 15.075 2.925 15.75 3.75 15.75H14.25C14.6478 15.75 15.0294 15.592 15.3107 15.3107C15.592 15.0294 15.75 14.6478 15.75 14.25V11.25M12.75 6L9 2.25L5.25 6M9 3.15V10.875" stroke="#6C6CFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
											</svg>
											Upload File
										</span>
										<div class="wsx-file-name">No File Chosen</div>    
									</div>
							</label>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'select':
						?>
						<!-- wsx-form-field--focused -->
						
						<div class="wsx-form-field wsx-outline-focus wsx-form-select">
							<select class="wsx-select" name="<?php echo esc_attr( $field['name'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>">
								<?php foreach ( $field['option'] as $option ) : ?>
									<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_html( $option['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<div class='wsx-form-label wsx-clone-label'><?php echo esc_html( $field['label'] ); ?> <?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
									<span aria-label="required">*</span>
								<?php
							}
							?>
							</div>
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label class="wsx-label wsx-form-label"><?php echo esc_html( $field['label'] ); ?> <?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
								</label>
							<?php endif; ?>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'checkbox':
						?>
						<!-- wsx-form-field--focused -->
						<div class="wsx-form-field wsx-form-checkbox">
							<?php
							if ( ! $is_label_hide ) {
								?>
									<div class="wsx-field-heading">
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
											<div class='wsx-form-label'><?php echo esc_html( $field['label'] ); ?> <?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
													<span aria-label="required">*</span>
												<?php
											}
											?>
												</div>
										<?php endif; ?>
										
									</div>
									<?php
							}
							?>
							<div class="wsx-field-content">
								<?php foreach ( $field['option'] as $option ) : ?>
									<label class="wsx-label wholesalex-field-wrap" for="<?php echo esc_attr( $field['name'] ); ?>">
										<input type="checkbox" class="wsx-checkbox" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>[]" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<div><?php echo esc_html( $option['name'] ); ?></div>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;
					case 'termCondition':
						$this->render_term_condition_field( $field, $is_label_hide );
						break;
					case 'radio':
						?>
						<!-- wsx-form-field--focused -->
						<div class="wsx-form-field wsx-field-radio">
							<?php
							if ( ! $is_label_hide ) {
								?>
									<div class="wsx-field-heading">
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
											<div class='wsx-form-label'><?php echo esc_html( $field['label'] ); ?> <?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
													<span aria-label="required">*</span>
												<?php
											}
											?>
												</div>
										<?php endif; ?>
										
									</div>
									<?php
							}
							?>
							
							<div class="wsx-field-content">
								<?php foreach ( $field['option'] as $option ) : ?>
									<div class="wholesalex-field-wrap">
										<input class="wsx-radio" type="radio" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<label class="wsx-label" for="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></label>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					default:
						break;
				}
				break;
			case 'variation_4':
				switch ( $field['type'] ) {
					case 'text':
					case 'email':
					case 'number':
					case 'date':
					case 'url':
					case 'tel':
						?>
						<!-- wsx-form-field--focused -->
						
						<div class="wsx-form-field wsx-outline-focus">
							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>"  placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>" />
							<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
								<?php echo esc_html( $field['label'] ); ?> <?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
																								<span aria-label="required">*</span>
												<?php
								}
								?>
							</label>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'password':
						?>
						<!-- wsx-form-field--focused -->
						
						<label class="wsx-label wsx-form-field wsx-outline-focus" for="<?php echo esc_attr( $field['name'] ); ?>">
							<input type="<?php echo esc_attr( $field['type'] ); ?>" class="wsx-input wsx-form-field__input" id="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>"  name="<?php echo esc_attr( $field['name'] ); ?>" minLength="<?php echo isset( $field['minLength'] ) ? esc_attr( $field['minLength'] ) : ''; ?>" maxLength="<?php echo isset( $field['maxLength'] ) ? esc_attr( $field['maxLength'] ) : ''; ?>" size="<?php echo isset( $field['size'] ) ? esc_attr( $field['size'] ) : ''; ?>" />
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<div  class="wsx-form-label">
									<?php echo esc_html( $field['label'] ); ?> <?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
																								<span aria-label="required">*</span>
												<?php
									}
									?>
								</div>
							<?php endif; ?>
						</label>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'textarea':
						?>
						<div class="wsx-form-field wsx-outline-focus wsx-form-textarea">
							<textarea class="wsx-textarea" id="<?php echo esc_attr( $field['name'] ); ?>" class="wsx-form-field__textarea"  name="<?php echo esc_attr( $field['name'] ); ?>"  rows="<?php echo isset( $field['rows'] ) ? esc_attr( $field['rows'] ) : ''; ?>" cols="<?php echo isset( $field['cols'] ) ? esc_attr( $field['cols'] ) : ''; ?>" placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>"></textarea>
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label class="wsx-label wsx-form-label" for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?> <?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
								</label>
							<?php endif; ?>
						</div>    
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'file':
						?>
						<!-- wsx-form-field--focused -->
						<div class="wsx-form-field wsx-form-file">
						<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<div  class="wsx-form-label">
									<?php echo esc_html( $field['label'] ); ?> <?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
											<span aria-label="required">*</span>
										<?php
									}
									?>
								</div>
							<?php endif; ?>
						</label>
							<label class="wsx-label wsx-field-content" for="<?php echo esc_attr( $field['name'] ); ?>">
								<input class="wsx-input" type="<?php echo esc_attr( $field['type'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>"  name="<?php echo esc_attr( $field['name'] ); ?>" />
								<div class="wsx-file-label" for="<?php echo esc_attr( $field['name'] ); ?>">
									<span>
										<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
											<path d="M2.25 11.25V14.25C2.25 15.075 2.925 15.75 3.75 15.75H14.25C14.6478 15.75 15.0294 15.592 15.3107 15.3107C15.592 15.0294 15.75 14.6478 15.75 14.25V11.25M12.75 6L9 2.25L5.25 6M9 3.15V10.875" stroke="#6C6CFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
										</svg>
										Upload File
									</span>
									<div class="wsx-file-name">No File Chosen</div>
								</div> 
							</label>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'select':
						?>
						<!-- wsx-form-field--focused -->
						
						<div class="wsx-form-field wsx-outline-focus">
							<select class="wsx-select" name="<?php echo esc_attr( $field['name'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>">
								<?php foreach ( $field['option'] as $option ) : ?>
									<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_html( $option['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label class="wsx-label wsx-form-label"><?php echo esc_html( $field['label'] ); ?> <?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
																							<span aria-label="required">*</span>
											<?php
								}
								?>
												</label>
							<?php endif; ?>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'checkbox':
						?>
						<!-- wsx-form-field--focused -->
						<div class="wsx-form-field wsx-form-checkbox">
							<?php
							if ( ! $is_label_hide ) {
								?>
									<div class="wsx-field-heading">
								<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
										<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
											<?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
													<span aria-label="required">*</span>
												<?php
											}
											?>
										</div>
									<?php endif; ?>
								
									</div>
									<?php
							}
							?>
							
							<div class="wsx-field-content">
								<?php foreach ( $field['option'] as $option ) : ?>
									<label class="wsx-label wholesalex-field-wrap" for="<?php echo esc_attr( $field['name'] ); ?>">
										<input class="wsx-checkbox" type="checkbox" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>[]" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<label class="wsx-label" for="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></label>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'termCondition':
						$this->render_term_condition_field( $field, $is_label_hide );
						break;

					case 'radio':
						?>
						<!-- wsx-form-field--focused -->
						<div class="wsx-form-field wsx-field-radio">
							<?php
							if ( ! $is_label_hide ) {
								?>
								<div class="wsx-field-heading">
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
										<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
											<?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
													<span aria-label="required">*</span>
												<?php
											}
											?>
										</div>
									<?php endif; ?>
									
								</div>
								<?php
							}

							?>
							
							<div class="wsx-field-content">
								<?php foreach ( $field['option'] as $option ) : ?>
									<div class="wholesalex-field-wrap">
										<input class="wsx-radio" type="radio" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<label class="wsx-label" for="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></label>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					default:
						break;
				}
				break;
			case 'variation_5':
				switch ( $field['type'] ) {
					case 'text':
					case 'email':
					case 'number':
					case 'date':
					case 'url':
					case 'tel':
						?>
						<!-- wsx-form-field--focused -->
						
						<div class="wsx-form-field wsx-outline-focus">
							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>"  placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>" />
							<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
								<?php echo esc_html( $field['label'] ); ?> <?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
							</label>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'password':
						?>
						<!-- wsx-form-field--focused -->
						
						<label class="wsx-label wsx-form-field wsx-outline-focus" for="<?php echo esc_attr( $field['name'] ); ?>">
							<input type="<?php echo esc_attr( $field['type'] ); ?>" class="wsx-input wsx-form-field__input" id="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>"  name="<?php echo esc_attr( $field['name'] ); ?>" minLength="<?php echo isset( $field['minLength'] ) ? esc_attr( $field['minLength'] ) : ''; ?>" maxLength="<?php echo isset( $field['maxLength'] ) ? esc_attr( $field['maxLength'] ) : ''; ?>" size="<?php echo isset( $field['size'] ) ? esc_attr( $field['size'] ) : ''; ?>" />
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<div  class="wsx-form-label">
									<?php echo esc_html( $field['label'] ); ?> <?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
											<span aria-label="required">*</span>
										<?php
									}
									?>
								</div>
							<?php endif; ?>
						</label>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'textarea':
						?>
						<!-- wsx-form-field--focused -->
						
						<div class="wsx-form-field wsx-outline-focus wsx-form-textarea">
							<textarea class="wsx-textarea" id="<?php echo esc_attr( $field['name'] ); ?>" class="wsx-form-field__textarea"  name="<?php echo esc_attr( $field['name'] ); ?>"  rows="<?php echo isset( $field['rows'] ) ? esc_attr( $field['rows'] ) : ''; ?>" cols="<?php echo isset( $field['cols'] ) ? esc_attr( $field['cols'] ) : ''; ?>" placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>"></textarea>
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label class="wsx-label wsx-form-label" for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?> <?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
								</label>
							<?php endif; ?>
						</div>    
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'file':
						?>
						<!-- wsx-form-field--focused -->
						<div class="wsx-form-field wsx-form-file">
							<?php
							if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
								?>
									<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
									<?php echo esc_html( $field['label'] ); ?>
									<?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
											<span aria-label="required">*</span>
										<?php
									}
									?>
									</div>
							<?php endif; ?>
							<label class="wsx-label wsx-field-content" for="<?php echo esc_attr( $field['name'] ); ?>">
								<input class="wsx-input" type="<?php echo esc_attr( $field['type'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>"  name="<?php echo esc_attr( $field['name'] ); ?>" />
								<div class="wsx-file-label" for="<?php echo esc_attr( $field['name'] ); ?>">
									<span>
										<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
											<path d="M2.25 11.25V14.25C2.25 15.075 2.925 15.75 3.75 15.75H14.25C14.6478 15.75 15.0294 15.592 15.3107 15.3107C15.592 15.0294 15.75 14.6478 15.75 14.25V11.25M12.75 6L9 2.25L5.25 6M9 3.15V10.875" stroke="#6C6CFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
										</svg>
										Upload File
									</span>
									<div class="wsx-file-name">No File Chosen</div>
								</div> 
							</label>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'select':
						?>
						<!-- wsx-form-field--focused -->
						
						<div class="wsx-form-field wsx-outline-focus">
							<select class="wsx-select" name="<?php echo esc_attr( $field['name'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>">
								<?php foreach ( $field['option'] as $option ) : ?>
									<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_html( $option['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label class="wsx-label" for="<?php echo esc_attr( $field['name'] ); ?>" class="wsx-form-label"><?php echo esc_html( $field['label'] ); ?>
									<?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
														<span aria-label="required">*</span>
										<?php
									}
									?>
								</label>
							<?php endif; ?>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'checkbox':
						?>
						<!-- wsx-form-field--focused -->
						<div class="wsx-form-field wsx-form-checkbox">
							<?php
							if ( ! $is_label_hide ) {
								?>
									<div class="wsx-field-heading">
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
											<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
												<?php
												if ( isset( $field['required'] ) && $field['required'] ) {
													?>
													<span aria-label="required">*</span>
													<?php
												}
												?>
											</div>
										<?php endif; ?>
										
									</div>
									<?php
							}

							?>
							
							<div class="wsx-field-content">
								<?php foreach ( $field['option'] as $option ) : ?>
									<label class="wsx-label wholesalex-field-wrap" for="<?php echo esc_attr( $field['name'] ); ?>">
										<input class="wsx-checkbox" type="checkbox" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>[]" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<div for="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></div>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'termCondition':
						$this->render_term_condition_field( $field, $is_label_hide );
						break;

					case 'radio':
						?>
						<!-- wsx-form-field--focused -->
						<div class="wsx-form-field wsx-field-radio">
							<?php
							if ( ! $is_label_hide ) {
								?>
								<div class="wsx-field-heading">
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
										<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
											<?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
													<span aria-label="required">*</span>
												<?php
											}
											?>
										</div>
									<?php endif; ?>
									
								</div>
								<?php
							}

							?>
							
							<div class="wsx-field-content">
								<?php foreach ( $field['option'] as $option ) : ?>
									<div class="wholesalex-field-wrap">
										<input class="wsx-radio" type="radio" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<div for="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					default:
						break;
				}
				break;
			case 'variation_6':
				switch ( $field['type'] ) {
					case 'text':
					case 'email':
					case 'number':
					case 'url':
					case 'tel':
						?>
						<!-- wsx-form-field--focused -->
						
						<div class="wsx-form-field wsx-outline-focus">
							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" placeholder="
								<?php
								echo esc_attr( $field['label'] );
								echo isset( $field['required'] ) && $field['required'] ? '*' : '';
								?>
							"  />
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?>  </span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'date':
						?>
						<!-- wsx-form-field--focused -->
						<?php
						if ( ! $is_label_hide ) {
							?>
								<div class="wsx-field-heading">
								<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
										<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
											<?php echo esc_html( $field['label'] ); ?> <?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
													<span aria-label="required">*</span>
												<?php
											}
											?>
										</label>
									<?php endif; ?>
									
								</div>
								<?php
						}
						?>
						<div class="wsx-form-field wsx-outline-focus wsx-form-date">
							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>"  />
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'password':
						?>
						<!-- wsx-form-field--focused -->
						
						<div class="wsx-form-field wsx-outline-focus">
							<input type="<?php echo esc_attr( $field['type'] ); ?>" class="wsx-input wsx-form-field__input" id="<?php echo esc_attr( $field['name'] ); ?>" placeholder="
								<?php
								echo esc_attr( $field['label'] );
								echo isset( $field['required'] ) && $field['required'] ? '*' : '';
								?>
							"  name="<?php echo esc_attr( $field['name'] ); ?>" minLength="<?php echo isset( $field['minLength'] ) ? esc_attr( $field['minLength'] ) : ''; ?>" maxLength="<?php echo isset( $field['maxLength'] ) ? esc_attr( $field['maxLength'] ) : ''; ?>" size="<?php echo isset( $field['size'] ) ? esc_attr( $field['size'] ) : ''; ?>" />
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'textarea':
						?>
						<!-- wsx-form-field--focused -->
						
						<div class="wsx-form-field wsx-outline-focus wsx-form-textarea">
							<textarea class="wsx-textarea" id="<?php echo esc_attr( $field['name'] ); ?>" class="wsx-form-field__textarea" name="<?php echo esc_attr( $field['name'] ); ?>"  rows="<?php echo isset( $field['rows'] ) ? esc_attr( $field['rows'] ) : ''; ?>" cols="<?php echo isset( $field['cols'] ) ? esc_attr( $field['cols'] ) : ''; ?>" placeholder="
								<?php
									echo esc_attr( $field['label'] );
									echo isset( $field['required'] ) && $field['required'] ? '*' : '';
								?>
							"></textarea>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'file':
						?>
						<!-- wsx-form-field--focused -->
						<div class="wsx-form-field wsx-form-file">
								<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<div class="wsx-field-heading">
									<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
										<?php echo esc_html( $field['label'] ); ?> <?php
										if ( isset( $field['required'] ) && $field['required'] ) {
											?>
												<span aria-label="required">*</span>
											<?php
										}
										?>
									</label>
								</div>
							<?php endif; ?>
							<label class="wsx-label wsx-field-content" for="<?php echo esc_attr( $field['name'] ); ?>">
								<input class="wsx-input" type="<?php echo esc_attr( $field['type'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>"  name="<?php echo esc_attr( $field['name'] ); ?>" />
								<div class="wsx-file-label" for="<?php echo esc_attr( $field['name'] ); ?>">
									<span>
										<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
											<path d="M2.25 11.25V14.25C2.25 15.075 2.925 15.75 3.75 15.75H14.25C14.6478 15.75 15.0294 15.592 15.3107 15.3107C15.592 15.0294 15.75 14.6478 15.75 14.25V11.25M12.75 6L9 2.25L5.25 6M9 3.15V10.875" stroke="#6C6CFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
										</svg>
										Upload File
									</span>
									<div class="wsx-file-name">No File Chosen</div>
								</div>
							</label>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'select':
						?>
						<!-- wsx-form-field--focused -->
						<?php
						if ( ! $is_label_hide ) {
							?>
							<div class="wsx-field-heading">
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<div class="wsx-form-label">
									<?php echo esc_html( $field['label'] ); ?> <?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
											<span aria-label="required">*</span>
										<?php
									}
									?>
								</div>
							<?php endif; ?>
							
							</div>
							<?php
						}

						?>
						
						<div class="wsx-form-field wsx-outline-focus wsx-form-select">
							<select class="wsx-select" name="<?php echo esc_attr( $field['name'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>">
								<?php foreach ( $field['option'] as $option ) : ?>
									<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_html( $option['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'checkbox':
						?>
						<!-- wsx-form-field--focused -->
						<div class="wsx-form-field wsx-form-checkbox">
							<?php
							if ( ! $is_label_hide ) {
								?>
									<div class="wsx-field-heading">
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
											<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
												<?php
												if ( isset( $field['required'] ) && $field['required'] ) {
													?>
														<span aria-label="required">*</span>
													<?php
												}
												?>
											</div>
										<?php endif; ?>
										
									</div>
									<?php
							}

							?>
							
							<div class="wsx-field-content">
								<?php foreach ( $field['option'] as $option ) : ?>
									<label class="wsx-label wholesalex-field-wrap" for="<?php echo esc_attr( $field['name'] ); ?>">
										<input class="wsx-checkbox" type="checkbox" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>[]" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<div for="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></div>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'termCondition':
						$this->render_term_condition_field( $field, $is_label_hide );
						break;

					case 'radio':
						?>
						<!-- wsx-form-field--focused -->
						<div class="wsx-form-field wsx-field-radio">
							<?php
							if ( ! $is_label_hide ) {
								?>
								<div class="wsx-field-heading">
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
										<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
											<?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
														<span aria-label="required">*</span>
												<?php
											}
											?>
										</div>
									<?php endif; ?>
									
								</div>
								<?php
							}
							?>
							
							<div class="wsx-field-content">
								<?php foreach ( $field['option'] as $option ) : ?>
									<div class="wholesalex-field-wrap">
										<input class="wsx-radio" type="radio" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<label class="wsx-label" for="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></label>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					default:
						break;
				}
				break;
			case 'variation_7':
				switch ( $field['type'] ) {
					case 'text':
					case 'email':
					case 'number':
					case 'date':
					case 'url':
					case 'tel':
						?>
						<div class="wsx-form-field wsx-outline-focus wsx-formBuilder-input-width">
							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>"  name="<?php echo esc_attr( $field['name'] ); ?>" placeholder=" " />
							<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
								<?php echo esc_html( $field['label'] ); ?> <?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
							</label>
							<div class="wsx-clone-label wsx-form-label"><?php echo esc_html( $field['label'] ); ?> <?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
									<span aria-label="required">*</span>
								<?php
							}
							?>
							</div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'password':
						?>
						<div class="wsx-form-field wsx-outline-focus wsx-formBuilder-input-width">
							<input type="<?php echo esc_attr( $field['type'] ); ?>" class="wsx-input wsx-form-field__input"  id="<?php echo esc_attr( $field['name'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" minLength="<?php echo isset( $field['minLength'] ) ? esc_attr( $field['minLength'] ) : ''; ?>" maxLength="<?php echo isset( $field['maxLength'] ) ? esc_attr( $field['maxLength'] ) : ''; ?>" size="<?php echo isset( $field['size'] ) ? esc_attr( $field['size'] ) : ''; ?>" placeholder=" " />
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label  class="wsx-form-label" for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?> <?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
								</label>
							<?php endif; ?>
							<div class="wsx-clone-label wsx-form-label"><?php echo esc_html( $field['label'] ); ?> <?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
									<span aria-label="required">*</span>
								<?php
							}
							?>
							</div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'textarea':
						?>
						<div class="wsx-form-field wsx-outline-focus wsx-form-textarea wsx-formBuilder-input-width">
							<textarea class="wsx-textarea" id="<?php echo esc_attr( $field['name'] ); ?>" class="wsx-form-field__textarea" name="<?php echo esc_attr( $field['name'] ); ?>"  rows="<?php echo isset( $field['rows'] ) ? esc_attr( $field['rows'] ) : ''; ?>" cols="<?php echo isset( $field['cols'] ) ? esc_attr( $field['cols'] ) : ''; ?>" placeholder=" "></textarea>
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label class="wsx-label wsx-form-label" for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?> <?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
								</label>
							<?php endif; ?>
							<div class="wsx-clone-label wsx-form-label"><?php echo esc_html( $field['label'] ); ?></div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;
					case 'file':
						?>
						<div class="wsx-form-field wsx-form-file wsx-file-outline">
							<label class="wsx-label wsx-field-content" for="<?php echo esc_attr( $field['name'] ); ?>">
								<input class="wsx-input" type="<?php echo esc_attr( $field['type'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>"  name="<?php echo esc_attr( $field['name'] ); ?>" />
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
										<div class="wsx-form-label wsx-clone-label"><?php echo esc_html( $field['label'] ); ?> <?php
										if ( isset( $field['required'] ) && $field['required'] ) {
											?>
																							<span aria-label="required">*</span>
												<?php
										}
										?>
												</div>
									<?php endif; ?>
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
										<div class="wsx-form-label"><?php echo esc_html( $field['label'] ); ?> <?php
										if ( isset( $field['required'] ) && $field['required'] ) {
											?>
																							<span aria-label="required">*</span>
												<?php
										}
										?>
												</div>
									<?php endif; ?>
								<div class="wsx-file-label">
										<span>
											<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
												<path d="M2.25 11.25V14.25C2.25 15.075 2.925 15.75 3.75 15.75H14.25C14.6478 15.75 15.0294 15.592 15.3107 15.3107C15.592 15.0294 15.75 14.6478 15.75 14.25V11.25M12.75 6L9 2.25L5.25 6M9 3.15V10.875" stroke="#6C6CFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
											</svg>
											Upload File
										</span>
										<div class="wsx-file-name">No File Chosen</div>    
									</div>
							</label>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;
					case 'select':
						?>
						<!-- wsx-form-field--focused -->
						
						<div class="wsx-form-field wsx-outline-focus wsx-form-select wsx-formBuilder-input-width">
							<select class="wsx-select" name="<?php echo esc_attr( $field['name'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>">
								<?php foreach ( $field['option'] as $option ) : ?>
									<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_html( $option['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label class="wsx-label wsx-form-label"><?php echo esc_html( $field['label'] ); ?>
									<?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
											<span aria-label="required">*</span>
										<?php
									}
									?>
								</label>
							<?php endif; ?>
							<div class="wsx-clone-label wsx-form-label"><?php echo esc_html( $field['label'] ); ?>
								<?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
											<span aria-label="required">*</span>
									<?php
								}
								?>
							</div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'checkbox':
						?>
						<!-- wsx-form-field--focused -->
						<div class="wsx-form-field wsx-form-checkbox">
							<?php
							if ( ! $is_label_hide ) {
								?>
								<div class="wsx-field-heading">
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
										<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?></div>
									<?php endif; ?>
								</div>

								<?php
							}

							?>
							<div class="wsx-field-content">
								<?php foreach ( $field['option'] as $option ) : ?>
									<label class="wsx-label wholesalex-field-wrap" for="<?php echo esc_attr( $field['name'] ); ?>">
										<input class="wsx-checkbox" type="checkbox" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>[]" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<div for="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></div>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'termCondition':
						$this->render_term_condition_field( $field, $is_label_hide );
						break;

					case 'radio':
						?>
						<div class="wsx-form-field wsx-field-radio">
							<?php
							if ( ! $is_label_hide ) {
								?>
									<div class="wsx-field-heading">
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
											<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
												<?php
												if ( isset( $field['required'] ) && $field['required'] ) {
													?>
													<span aria-label="required">*</span>
													<?php
												}
												?>
											</div>
										<?php endif; ?>
										
									</div>
									<?php
							}
							?>
							<div class="wsx-field-content">
								<?php foreach ( $field['option'] as $option ) : ?>
									<div class="wholesalex-field-wrap">
										<input class="wsx-radio" type="radio" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<label class="wsx-label" for="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></label>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					default:
						break;
				}
				break;
			case 'variation_8':
				switch ( $field['type'] ) {
					case 'text':
					case 'email':
					case 'number':
					case 'date':
						?>
							<!-- wsx-form-field--focused -->
							
							<label class="wsx-label wsx-form-field wsx-outline-focus wsx-formBuilder-input-width">
								<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
										<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
										<?php echo esc_html( $field['label'] ); ?> <?php
										if ( isset( $field['required'] ) && $field['required'] ) {
											?>
												<span aria-label="required">*</span>
											<?php
										}
										?>
									</div>
								<?php endif; ?>
								<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>"  placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" />
							</label>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
							?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
							<?php
						break;

					case 'select':
						?>
							<div class="wsx-form-field wsx-outline-focus wsx-formBuilder-input-width">
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
									<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
										<?php
										if ( isset( $field['required'] ) && $field['required'] ) {
											?>
										<span aria-label="required">*</span>
											<?php
										}
										?>
									</label>
								<?php endif; ?>
								<select class="wsx-select" name="<?php echo esc_attr( $field['name'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>">
								<?php foreach ( $field['option'] as $option ) : ?>
										<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_html( $option['name'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
							?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
							<?php
						break;

					case 'checkbox':
						?>
							<div class="wsx-form-field wsx-form-checkbox">
								<?php
								if ( ! $is_label_hide ) {
									?>
										<div class="wsx-field-heading">
										<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
												<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
													<?php
													if ( isset( $field['required'] ) && $field['required'] ) {
														?>
															<span aria-label="required">*</span>
														<?php
													}
													?>
												</div>
											<?php endif; ?>
											
										</div>
										<?php
								}
								?>
								<div class="wsx-field-content">
								<?php foreach ( $field['option'] as $option ) : ?>
										<div class="wholesalex-field-wrap">
											<input class="wsx-checkbox" type="checkbox" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $option['name'] ); ?>[]" value="<?php echo esc_attr( $option['value'] ); ?>" />
											<label class="wsx-label" for="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></label>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
							?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
							<?php
						break;

					case 'termCondition':
						$this->render_term_condition_field( $field, $is_label_hide );
						break;

					case 'radio':
						?>
							<!-- wsx-form-field--focused -->
							<div class="wsx-form-field wsx-field-radio">
								<?php
								if ( ! $is_label_hide ) {
									?>

										<div class="wsx-field-heading">
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
												<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
													<?php
													if ( isset( $field['required'] ) && $field['required'] ) {
														?>
															<span aria-label="required">*</span>
														<?php
													}
													?>
												</div>
											<?php endif; ?>
										
										</div>
										<?php
								}

								?>
								
								<div class="wsx-field-content">
								<?php foreach ( $field['option'] as $option ) : ?>
										<div class="wholesalex-field-wrap">
											<input class="wsx-radio" type="radio" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( $option['value'] ); ?>" />
											<label class="wsx-label" for="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_html( $option['name'] ); ?></label>
										</div>
									<?php endforeach; ?>
								</div>
							</div> 
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
							?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
							<?php
						break;

					case 'file':
						?>
							<!-- wsx-form-field--focused -->
							<div class="wsx-form-field wsx-form-file">
								<label class="wsx-label wsx-field-content">
									<input class="wsx-input" type="<?php echo esc_attr( $field['type'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>"  name="<?php echo esc_attr( $field['name'] ); ?>" />
									<div class="wsx-file-label" for="<?php echo esc_attr( $field['name'] ); ?>">
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
											<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?> <?php echo isset( $field['required'] ) ? '<span aria-label="required">*</span>' : ''; ?></div>
										<?php endif; ?>
										<div class="wsx-file-label_wrap">
											<span>
												<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
												<path d="M2.25 11.25V14.25C2.25 15.075 2.925 15.75 3.75 15.75H14.25C14.6478 15.75 15.0294 15.592 15.3107 15.3107C15.592 15.0294 15.75 14.6478 15.75 14.25V11.25M12.75 6L9 2.25L5.25 6M9 3.15V10.875" stroke="#6C6CFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
												</svg>
												Upload File
											</span>
											<div class="wsx-file-name">No File Chosen</div>
										</div>
									</div> 
								</label>
							</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
							?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
							<?php
						break;

					case 'tel':
						?>
							<!-- wsx-form-field--focused -->
							<div class="wsx-form-field">
								<?php
								if ( ! $is_label_hide ) {
									?>
										<div class="wsx-field-heading">
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
												<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?> <?php
												if ( isset( $field['required'] ) && $field['required'] ) {
													?>
														<span aria-label="required">*</span>
													<?php
												}
												?>
												</label>
											<?php endif; ?>
										
										</div>
										<?php
								}
								?>
								<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type='tel' name="<?php echo esc_attr( $field['name'] ); ?>"   />
							</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
							?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
							<?php
						break;

					case 'url':
						?>
							<!-- wsx-form-field--focused -->
							<div class="wsx-form-field wsx-outline-focus wsx-formBuilder-input-width">
								<div class="wsx-field-heading wsx-outline-focus wsx-formBuilder-input-width">
								<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
										<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?> <?php
										if ( isset( $field['required'] ) && $field['required'] ) {
											?>
												<span aria-label="required">*</span>
												<?php
										}
										?>
												</label>
									<?php endif; ?>
								
								</div>
								<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type='url' name="<?php echo esc_attr( $field['name'] ); ?>"  />
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
									<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php endif; ?>
							</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
							?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
							<?php
						break;

					case 'password':
						?>
							<!-- wsx-form-field--focused -->
							
							<div class="wsx-form-field wsx-outline-focus wsx-formBuilder-input-width">
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
									<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?> <?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
											<span aria-label="required">*</span>
										<?php
									}
									?>
									</label>
								<?php endif; ?>
								<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type='password' name="<?php echo esc_attr( $field['name'] ); ?>"  minLength="<?php echo isset( $field['minLength'] ) ? esc_attr( $field['minLength'] ) : ''; ?>" maxLength="<?php echo isset( $field['maxLength'] ) ? esc_attr( $field['maxLength'] ) : ''; ?>" size="<?php echo isset( $field['size'] ) ? esc_attr( $field['size'] ) : ''; ?>"  placeholder="Type Password" />
							</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
							?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
							<?php
						break;
					case 'textarea':
						?>
							<!-- wsx-form-field--focused -->
							<div class="wsx-form-field wsx-outline-focus wsx-formBuilder-input-width">
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
									<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?> <?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
											<span aria-label="required">*</span>
										<?php
									}
									?>
												</label>
								<?php endif; ?>
								<textarea class="wsx-textarea" id="<?php echo esc_attr( $field['name'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>"  rows="<?php echo isset( $field['rows'] ) ? esc_attr( $field['rows'] ) : ''; ?>" cols="<?php echo isset( $field['cols'] ) ? esc_attr( $field['cols'] ) : ''; ?>" placeholder="Write Message..."></textarea>
							</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
							?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
							<?php
						break;
					default:
							// code...
						break;
				}
				break;

			default:
				// code...
				break;
		}

		$output = ob_get_clean();

		echo apply_filters( 'wholesalex_registration_form_field', $output, $field['type'], $field['name'], $input_variation, $field );
	}

	/**
	 * Add Custom Fields on Checkout Page
	 *
	 * @param object $checkout Checkout Object.
	 * @return void
	 */
	public function add_custom_fields_on_checkout_page( $checkout ) {
		$__role = wholesalex()->get_current_user_role();

		$custom_billing_fields = isset( $GLOBALS['wholesalex_registration_fields']['billing_fields'] ) ? $GLOBALS['wholesalex_registration_fields']['billing_fields'] : array();

		$__fields = $checkout->get_checkout_fields( 'billing' );
		$__keys   = array_keys( $__fields );

		$__custom_fields    = array();
		$__all_exclude_role = array();
		if ( is_array( $custom_billing_fields ) ) {
			foreach ( $custom_billing_fields as $value ) {
				foreach ( $value['excludeRoles'] as $exclude_role ) {
					$__all_exclude_role[] = $exclude_role['value'];
				}
				if ( isset( $value['excludeRoles'] ) && is_array( $value['excludeRoles'] ) && in_array( $__role, $__all_exclude_role ) ) {
					continue; // Exclude For this user.
				}

				if ( isset( $value['name'] ) && ! in_array( 'billing_' . $value['name'], $__keys, true ) ) {
					$__default = '';

					if ( isset( $value['migratedFromOldBuilder'] ) && $value['migratedFromOldBuilder'] && ( ! isset( $value['custom_field'] ) || ! $value['custom_field'] ) ) {
						$__default = get_user_meta( get_current_user_id(), $value['name'], true );
					}
					if ( isset( $value['custom_field'] ) && $value['custom_field'] ) {
						$__default = get_user_meta( get_current_user_id(), 'wholesalex_cf_' . $value['name'], true );
					}

					$__options = array();

					if ( 'select' === $value['type'] || 'radio' === $value['type'] ) {
						if ( is_array( $value['option'] ) ) {

							foreach ( $value['option'] as $option ) {
								$__options[ $option['value'] ] = $option['name'];
							}
						}
						woocommerce_form_field(
							$value['name'],
							array(
								'type'        => $value['type'],
								'class'       => array( 'form-row-wide' ),
								'label'       => isset( $value['label'] ) ? $value['label'] : '',
								'placeholder' => isset( $value['placeholder'] ) ? $value['placeholder'] : '',
								'required'    => isset( $value['isRequiredInBilling'] ) ? $value['isRequiredInBilling'] : '',
								'options'     => $__options,
								'default'     => $__default,
							),
							$checkout->get_value( $value['name'] )
						);
					} elseif ( 'file' !== $value['type'] && 'checkbox' !== $value['type'] ) {
							woocommerce_form_field(
								$value['name'],
								array(
									'type'        => $value['type'],
									'class'       => array( 'form-row-wide' ),
									'label'       => isset( $value['label'] ) ? $value['label'] : '',
									'placeholder' => isset( $value['placeholder'] ) ? $value['placeholder'] : '',
									'required'    => isset( $value['isRequiredInBilling'] ) ? $value['isRequiredInBilling'] : '',
									'default'     => $__default,
								),
								$checkout->get_value( $value['name'] )
							);
					} elseif ( 'checkbox' === $value['type'] ) {
						if ( ! is_array( $__default ) ) {
							$__default = array();
						}

						?>
							<p class="form-row form-row-wise" id="<?php echo esc_attr( $value['name'] ); ?>"> 
								<label class="wsx-label">
								<?php echo esc_html( $value['label'] ); ?>
								<?php
								if ( isset( $value['isRequiredInBilling'] ) && $value['isRequiredInBilling'] ) {
									?>
										<span class="optional"><?php echo esc_html__( 'optional', 'woocommerce' ); ?></span>
										<?php
								}
								?>
								</label>
								<span class="woocommerce-input-wrapper">
								<?php
								foreach ( $value['option'] as $option ) :
									?>
										<span>
											<label class="wsx-label checkbox" for=<?php echo esc_attr( $option['value'] ); ?> >
                                            <input type="checkbox" class="input-checkbox" name="<?php echo esc_attr($option['name']); ?>" id="<?php echo esc_attr($option['name']); ?>" <?php checked( in_array( $option['value'], $__default ), 1, true ); //phpcs:ignore ?>>  <?php echo esc_html( $option['name'] ); ?> </label>
										</span>

										<?php
									endforeach;
								?>
								</span>
							</p>
							<?php

					}

					$__custom_fields[ $value['name'] ] = $value;

				}
			}
		}

		?>
		<?php

		set_transient( 'wholesalex_custom_chekcout_fields_' . get_current_user_id(), $__custom_fields );
	}



	/**
	 * Validate Custom Fields on Checkout Page
	 */
	public function validate_custom_checkout_fields() {
		// Verify nonce for security.
		$nonce_value = wc_get_var( $_REQUEST['woocommerce-process-checkout-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // phpcs:ignore
		$nonce_value = sanitize_key( $nonce_value );

		if ( empty( $nonce_value ) || ! wp_verify_nonce( $nonce_value, 'woocommerce-process_checkout' ) ) {
			return;
		}

		// Sanitize post data.
		$post_data = wholesalex()->sanitize( $_POST ); // phpcs:ignore
		$__user_id = get_current_user_id();

		// Fetch custom checkout fields.
		$__custom_fields = get_transient( 'wholesalex_custom_checkout_fields_' . $__user_id ); // Fixed typo in the transient key.
		if ( is_array( $__custom_fields ) && ! empty( $__custom_fields ) ) {
			foreach ( $__custom_fields as $field ) {
				// Skip file fields.
				if ( 'file' === $field['type'] ) {
					continue;
				} elseif ( isset( $field['isRequiredInBilling'] ) && $field['isRequiredInBilling'] && ( ! isset( $post_data[ $field['name'] ] ) || empty( $post_data[ $field['name'] ] ) ) ) {
					// Check if the field is required and missing.
					/* translators: %s: Field Title. */
					wc_add_notice( sprintf( '%s is Missing!', sanitize_text_field( $field['title'] ) ), 'error' ); // Changed 'title' to 'label'.
				}
			}
		}
	}


	/**
	 * Sanitize Field Data
	 *
	 * @param array      $field Field Data.
	 * @param string|int $value Field Value.
	 * @return string|int Sanitized Value.
	 */
	public function sanitize_field_data( $field, $value ) {
		switch ( $field['type'] ) {
			case 'textarea':
				return sanitize_textarea_field( $value );
			case 'url':
				return sanitize_url( $value ); // phpcs:ignore
			case 'email':
				return sanitize_email( $value );
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Save Custom Fields Data
	 *
	 * @param string|int $order_id Order ID.
	 * @return void
	 */
	public function add_custom_fields_on_order_meta( $order_id ) {

		// Retrieve custom fields from global variables or other sources.
		$__custom_fields = isset( $GLOBALS['wholesalex_registration_fields']['billing_fields'] ) ? $GLOBALS['wholesalex_registration_fields']['billing_fields'] : array();

		// Check if custom fields exist and are an array.
		if ( ! empty( $__custom_fields ) && is_array( $__custom_fields ) ) {
			foreach ( $__custom_fields as $field ) {
				// Skip file type fields.
				if ( 'file' === $field['type'] ) {
					continue;
				}
				// Check if the field data is posted and not empty, then sanitize and update post meta.
				if ( isset( $_POST[ $field['name'] ] ) && ! empty( $_POST[ $field['name'] ] ) ) { // phpcs:ignore
					$sanitized_value = $this->sanitize_field_data( $field, sanitize_text_field( $_POST[ $field['name'] ] ) );
					update_post_meta( $order_id, 'wholesalex_cf_' . $field['name'], $sanitized_value );
				}
			}
		}
	}

	/**
	 * Show Custom Fields Value
	 *
	 * @param string|int $order_id Order ID.
	 * @return void
	 */
	public function show_custom_fields_value( $order_id ) {
		// Ensure proper escaping and initialization.
		$__custom_fields = isset( $GLOBALS['wholesalex_registration_fields']['billing_fields'] )
							? $GLOBALS['wholesalex_registration_fields']['billing_fields']
							: array();

		if ( is_array( $__custom_fields ) ) {
			echo '<div class="wholesalex_custom_fields">';

			foreach ( $__custom_fields as $field ) {
				$__value = get_post_meta( $order_id, 'wholesalex_cf_' . sanitize_key( $field['name'] ), true );
				if ( $__value && ! empty( $__value ) ) {
					echo '<label class="wsx-label">' . esc_html( $field['label'] ) . '</label>: <strong>' . esc_html( $__value ) . '</strong><br />';
				}
			}

			echo '</div>';
		}
	}


	/**
	 * Show Custom Fields On Order Details Page.
	 *
	 * @param object $order Order Object.
	 * @return void
	 */
	public function show_custom_fields_on_order_page( $order ) {
		$order_id        = $order->get_id();
		$__user_id       = get_current_user_id();
		$__custom_fields = isset( $GLOBALS['wholesalex_registration_fields']['billing_fields'] )
							? $GLOBALS['wholesalex_registration_fields']['billing_fields']
							: array();

		if ( is_array( $__custom_fields ) && ! empty( $__custom_fields ) ) {
			echo '<div class="wholesalex_custom_fields">';

			foreach ( $__custom_fields as $field ) {
				$__value = get_post_meta( $order_id, 'wholesalex_cf_' . sanitize_key( $field['name'] ), true );
				if ( $__value && ! empty( $__value ) ) {
					echo '<p class="form-field form-field-wide">';
					echo '<strong>' . esc_html( $field['label'] ) . ':</strong> ';
					echo '<div>' . esc_html( $__value ) . '</div>';
					echo '</p>';
				}
			}

			echo '</div>';
		}
	}

	/**
	 * Sanitize Form Data
	 *
	 * @param array $form_data Form Data.
	 */
	public function sanitize_form_data( $form_data ) {
		$data = array();
		foreach ( $form_data as $key => $value ) {
			$key = sanitize_key( $key );
			switch ( $key ) {
				case 'description':
					$data[ $key ] = sanitize_textarea_field( $value );
					break;
				case ( preg_match( '#^textarea(.*)$#i', $key ) ? true : false ):
					$data[ $key ] = sanitize_textarea_field( $value );
					break;
				case 'user_pass':
				case 'display_name':
				case 'nickname':
				case 'first_name':
				case 'last_name':
				case ( preg_match( '#^text(.*)$#i', $key ) ? true : false ):
						$data[ $key ] = sanitize_text_field( $value );
					break;
				case 'url':
					$data[ $key ] = sanitize_url( $value );
					break;
				case ( preg_match( '#^email(.*)$#i', $key ) ? true : false ):
				case 'user_email':
					$data[ $key ] = sanitize_email( $value );
					break;
				case ( preg_match( '#^select(.*)$#i', $key ) ? true : false ):
				case ( preg_match( '#^checkbox(.*)$#i', $key ) ? true : false ):
				case ( preg_match( '#^number(.*)$#i', $key ) ? true : false ):
				case ( preg_match( '#^radio(.*)$#i', $key ) ? true : false ):
				case ( preg_match( '#^date(.*)$#i', $key ) ? true : false ):
					if ( is_array( $value ) ) {
						$data[ $key ] = wholesalex()->sanitize( $value );
					} else {
						$data[ $key ] = sanitize_text_field( $value );
					}
					break;
				case 'user_login':
					$data[ $key ] = sanitize_user( $value );
					break;

				default:
					if ( is_array( $value ) ) {
						$data[ $key ] = wholesalex()->sanitize( $value );
					} else {
						$data[ $key ] = sanitize_text_field( $value );
					}
					break;
			}
		}

		return $data;
	}

	/**
	 * Enqueue WooCommerce Passowrd Meter For wholesalex registration form
	 *
	 * @return void
	 */
	public function enqueue_password_meter() {
		$status = apply_filters( 'wholesalex_registration_form_password_strength_meter_status', true );
		if ( $status ) {
			wp_enqueue_script( 'woocommerce' );
			wp_enqueue_script( 'wc-password-strength-meter' );
		}
	}


	/**
	 * Process User login
	 *
	 * @return void
	 * @throws \Exception Exception.
	 */
	public function process_login() {
		if ( isset( $_POST['wholesalex-login-nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wholesalex-login-nonce'] ), 'wholesalex-login' ) ) {
			if ( isset( $_POST['username'], $_POST['password'] ) ) {
				do_action( 'wholesalex_before_process_user_login' );

				$data = array(
					'error_messages' => array(),
				);
				try {
					$creds = array(
						'user_login'    => trim( wp_unslash( $_POST['username'] ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						'user_password' => $_POST['password'], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
						'remember'      => isset( $_POST['rememberme'] ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					);

					$validation_error = new \WP_Error();
					$validation_error = apply_filters( 'woocommerce_process_login_errors', $validation_error, $creds['user_login'], $creds['user_password'] );

					if ( $validation_error->get_error_code() ) {
						$data['error_messages']['validation_error'] = $validation_error->get_error_message();
						throw new \Exception();
					}

					if ( empty( $creds['user_login'] ) ) {
						$data['error_messages']['username'] = __( 'Username is Required!', 'wholesalex' );

					}
					if ( empty( $creds['user_password'] ) ) {
						$data['error_messages']['password'] = __( 'Password is Required!', 'wholesalex' );

					}

					if ( ! empty( $data['error_messages'] ) ) {
						throw new \Exception();
					}

					// On multisite, ensure user exists on current site, if not add them before allowing login.
					if ( is_multisite() ) {
						$user_data = get_user_by( is_email( $creds['user_login'] ) ? 'email' : 'login', $creds['user_login'] );

						if ( $user_data && ! is_user_member_of_blog( $user_data->ID, get_current_blog_id() ) ) {
							add_user_to_blog( get_current_blog_id(), $user_data->ID, 'customer' );
						}
					}

					// Peform the login.
					$user = wp_signon( apply_filters( 'woocommerce_login_credentials', $creds ), is_ssl() );

					if ( is_wp_error( $user ) ) {
						switch ( $user->get_error_code() ) {
							case 'invalid_username':
								$data['error_messages']['username'] = $user->get_error_message();
								break;
							case 'incorrect_password':
								$data['error_messages']['password'] = $user->get_error_message();
								break;
							case 'invalid_email':
								$data['error_messages']['username'] = $user->get_error_message();
								break;
							case 'recaptcha_error':
								$data['error_messages']['recaptcha'] = wc_print_notice( $user->get_error_message(), 'error', array(), true );
								break;

							default:
								// code...
								$data['error_messages'][ $user->get_error_code() ] = $user->get_error_message();
								break;
						}

						throw new \Exception();
					} else {

						$data['redirect'] = wp_validate_redirect( apply_filters( 'woocommerce_login_redirect', wc_get_page_permalink( 'myaccount' ), $user ), wc_get_page_permalink( 'myaccount' ) );
						wp_send_json_success( $data );
					}
				} catch ( \Exception $e ) {
					wp_send_json_success( $data );
				}
			}
		}
	}
}
