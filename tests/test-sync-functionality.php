<?php
/**
 * Tests para funcionalidad de sincronización de fechas Palafito
 * 
 * @package Palafito_WC_Extensions
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;

class PalafitoSyncTest extends TestCase {

    private $order_id;
    private $order;

    public function setUp(): void {
        parent::setUp();
        
        // Verificar que WooCommerce está disponible
        if (!function_exists('wc_get_order')) {
            $this->markTestSkipped('WooCommerce not available');
        }
        
        // Crear pedido de prueba
        $this->order = new WC_Order();
        $this->order->set_status('processing');
        $this->order->save();
        $this->order_id = $this->order->get_id();
    }

    public function tearDown(): void {
        // Limpiar pedido de prueba
        if ($this->order_id) {
            wp_delete_post($this->order_id, true);
        }
        parent::tearDown();
    }

    /**
     * Test: Sincronización de _wcpdf_packing-slip_date a _entregado_date
     */
    public function test_packing_slip_to_entregado_sync() {
        $test_date = '2025-01-15';
        
        // Actualizar fecha de albarán
        update_post_meta($this->order_id, '_wcpdf_packing-slip_date', $test_date);
        
        // Verificar que se sincronizó
        $entregado_date = get_post_meta($this->order_id, '_entregado_date', true);
        
        $this->assertEquals($test_date, $entregado_date, 
            'La fecha de entrega debe sincronizarse con la fecha del albarán');
    }

    /**
     * Test: Sincronización de _entregado_date a _wcpdf_packing-slip_date
     */
    public function test_entregado_to_packing_slip_sync() {
        $test_date = '2025-01-20';
        
        // Actualizar fecha de entrega
        update_post_meta($this->order_id, '_entregado_date', $test_date);
        
        // Verificar que se sincronizó
        $packing_slip_date = get_post_meta($this->order_id, '_wcpdf_packing-slip_date', true);
        
        $this->assertEquals($test_date, $packing_slip_date, 
            'La fecha del albarán debe sincronizarse con la fecha de entrega');
    }

    /**
     * Test: Prevención de bucles infinitos
     */
    public function test_infinite_loop_prevention() {
        $test_date = '2025-01-25';
        
        // Simular que ya hay una sincronización en progreso
        set_transient("palafito_syncing_{$this->order_id}", true, 30);
        
        // Intentar actualizar (debería ignorarse)
        update_post_meta($this->order_id, '_wcpdf_packing-slip_date', $test_date);
        
        // Verificar que NO se sincronizó
        $entregado_date = get_post_meta($this->order_id, '_entregado_date', true);
        
        $this->assertNotEquals($test_date, $entregado_date, 
            'La sincronización debe estar bloqueada durante el flag temporal');
        
        // Limpiar flag temporal
        delete_transient("palafito_syncing_{$this->order_id}");
    }

    /**
     * Test: Columna fecha de entrega muestra datos correctos
     */
    public function test_entregado_date_column_display() {
        $test_date = '2025-01-30';
        
        // Configurar fecha de entrega
        $this->order->update_meta_data('_entregado_date', $test_date);
        $this->order->save();
        
        // Simular obtención de datos de columna
        ob_start();
        Palafito_WC_Extensions::custom_order_columns_data('entregado_date', $this->order_id);
        $output = ob_get_clean();
        
        // Verificar que muestra la fecha formateada
        $formatted_date = date_i18n('d/m/Y', strtotime($test_date));
        $this->assertStringContainsString($formatted_date, $output,
            'La columna debe mostrar la fecha de entrega formateada');
    }

    /**
     * Test: Fallback cuando no existe _entregado_date
     */
    public function test_fallback_to_packing_slip_date() {
        $test_date = '2025-02-01';
        
        // Solo configurar fecha de albarán, no fecha de entrega
        $this->order->update_meta_data('_wcpdf_packing-slip_date', $test_date);
        $this->order->save();
        
        // Simular obtención de datos de columna
        ob_start();
        Palafito_WC_Extensions::custom_order_columns_data('entregado_date', $this->order_id);
        $output = ob_get_clean();
        
        // Verificar que usa el fallback
        $formatted_date = date_i18n('d/m/Y', strtotime($test_date));
        $this->assertStringContainsString($formatted_date, $output,
            'La columna debe usar la fecha del albarán como fallback');
    }

    /**
     * Test: Manejo de errores en sincronización
     */
    public function test_sync_error_handling() {
        // Simular orden inválida
        $invalid_order_id = 99999;
        
        // No debería generar errores fatales
        $this->expectNotToPerformAssertions();
        
        // Ejecutar funciones con orden inválida
        palafito_sync_packing_slip_to_entregado(1, $invalid_order_id, '_wcpdf_packing-slip_date', '2025-01-01');
        palafito_sync_entregado_to_packing_slip(1, $invalid_order_id, '_entregado_date', '2025-01-01');
    }
}