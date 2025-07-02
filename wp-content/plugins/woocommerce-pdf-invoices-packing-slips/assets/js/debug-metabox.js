jQuery(function($) {
    console.log('[PALAFITO DEBUG] Script de debug del metabox cargado');

    // Verificar si estamos en una página de pedido
    if ($('#wpo_wcpdf-data-input-box').length) {
        console.log('[PALAFITO DEBUG] Metabox encontrado');

        // Verificar elementos del metabox
        $('.wcpdf-data-fields').each(function() {
            var $field = $(this);
            var documentType = $field.data('document');
            var orderId = $field.data('order_id');
            console.log('[PALAFITO DEBUG] Campo encontrado:', documentType, 'para pedido:', orderId);

            // Verificar botones de edición
            var $editButtons = $field.find('.wpo-wcpdf-edit-date-number');
            console.log('[PALAFITO DEBUG] Botones de edición encontrados:', $editButtons.length);

            // Verificar campos de fecha
            var $dateFields = $field.find('input[name*="_date"]');
            console.log('[PALAFITO DEBUG] Campos de fecha encontrados:', $dateFields.length);
            $dateFields.each(function() {
                console.log('[PALAFITO DEBUG] Campo de fecha:', $(this).attr('name'), 'ID:', $(this).attr('id'));
            });
        });

        // Verificar event listeners
        $(document).on('click', '.wpo-wcpdf-edit-date-number', function() {
            console.log('[PALAFITO DEBUG] Botón de edición clickeado');
        });

        $(document).on('click', '.wpo-wcpdf-save-document', function() {
            console.log('[PALAFITO DEBUG] Botón de guardar clickeado');
        });

    } else {
        console.log('[PALAFITO DEBUG] Metabox NO encontrado');
    }

    // Verificar si el script principal está cargado
    if (typeof wpo_wcpdf_ajax !== 'undefined') {
        console.log('[PALAFITO DEBUG] Script principal cargado, AJAX URL:', wpo_wcpdf_ajax.ajaxurl);
    } else {
        console.log('[PALAFITO DEBUG] Script principal NO cargado');
    }
});
