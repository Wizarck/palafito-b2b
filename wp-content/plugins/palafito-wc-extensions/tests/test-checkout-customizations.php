<?php
/**
 * Tests for Palafito_Checkout_Customizations class.
 *
 * @package Palafito_WC_Extensions
 * @since 1.0.0
 */

/**
 * Test class for Palafito_Checkout_Customizations.
 */
class Test_Palafito_Checkout_Customizations extends WP_UnitTestCase {

	/**
	 * Test instance of the class.
	 *
	 * @var Palafito_Checkout_Customizations
	 */
	private $checkout_customizations;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->checkout_customizations = new Palafito_Checkout_Customizations();
	}

	/**
	 * Test that checkout customizations class is instantiated correctly.
	 */
	public function test_checkout_customizations_class_exists() {
		$this->assertInstanceOf( 'Palafito_Checkout_Customizations', $this->checkout_customizations );
	}

	/**
	 * Test that checkout fields are customized correctly.
	 */
	public function test_customize_checkout_fields() {
		$fields = array(
			'billing' => array(
				'billing_phone' => array(
					'required' => false,
				),
			),
		);

		$customized_fields = $this->checkout_customizations->customize_checkout_fields( $fields );

		// Check that company type field is added.
		$this->assertArrayHasKey( 'billing_company_type', $customized_fields['billing'] );
		$this->assertTrue( $customized_fields['billing']['billing_company_type']['required'] );
		$this->assertEquals( 'select', $customized_fields['billing']['billing_company_type']['type'] );
	}

	/**
	 * Test that B2B fields are added correctly.
	 */
	public function test_add_b2b_fields() {
		$fields = array(
			'billing' => array(),
		);

		$customized_fields = $this->checkout_customizations->add_b2b_fields( $fields );

		// Check that business name field is added.
		$this->assertArrayHasKey( 'billing_business_name', $customized_fields['billing'] );
		$this->assertTrue( $customized_fields['billing']['billing_business_name']['required'] );
	}

	/**
	 * Test that custom fields are saved correctly.
	 */
	public function test_save_custom_fields() {
		// Create a simple mock order.
		$order_id = 123;

		// Mock POST data.
		$_POST['billing_company_type']  = 'wholesale';
		$_POST['billing_business_name'] = 'Test Company';

		// Test that the method can be called without errors.
		$this->checkout_customizations->save_custom_fields( $order_id );

		// Verify the data was saved.
		$this->assertEquals( 'wholesale', get_post_meta( $order_id, '_billing_company_type', true ) );
		$this->assertEquals( 'Test Company', get_post_meta( $order_id, '_billing_business_name', true ) );
	}

	/**
	 * Test that last name fields are made optional.
	 */
	public function test_make_last_name_optional() {
		$fields = array(
			'billing'  => array(
				'billing_last_name' => array(
					'required' => true,
				),
			),
			'shipping' => array(
				'shipping_last_name' => array(
					'required' => true,
				),
			),
		);

		$modified_fields = $this->checkout_customizations->make_last_name_optional( $fields );

		// Check that billing last name is now optional.
		$this->assertFalse( $modified_fields['billing']['billing_last_name']['required'] );

		// Check that shipping last name is now optional.
		$this->assertFalse( $modified_fields['shipping']['shipping_last_name']['required'] );
	}

	/**
	 * Test that checkout customizations methods are callable.
	 */
	public function test_checkout_customizations_methods_exist() {
		// Test that the class has the expected methods.
		$this->assertTrue( method_exists( $this->checkout_customizations, 'customize_checkout_fields' ) );
		$this->assertTrue( method_exists( $this->checkout_customizations, 'add_b2b_fields' ) );
		$this->assertTrue( method_exists( $this->checkout_customizations, 'save_custom_fields' ) );
		$this->assertTrue( method_exists( $this->checkout_customizations, 'make_last_name_optional' ) );
	}
}
 