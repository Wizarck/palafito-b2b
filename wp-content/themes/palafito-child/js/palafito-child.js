/**
 * JavaScript principal del tema hijo Palafito
 * 
 * @package Palafito_Child
 * @version 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Clase principal del tema
     */
    class PalafitoChild {
        
        constructor() {
            this.init();
        }

        /**
         * Inicializar el tema
         */
        init() {
            this.bindEvents();
            this.initComponents();
            this.setupAjax();
        }

        /**
         * Vincular eventos
         */
        bindEvents() {
            $(document).ready(() => {
                this.onDocumentReady();
            });

            $(window).on('load', () => {
                this.onWindowLoad();
            });

            $(window).on('resize', this.debounce(() => {
                this.onWindowResize();
            }, 250));

            $(window).on('scroll', this.debounce(() => {
                this.onWindowScroll();
            }, 100));
        }

        /**
         * Inicializar componentes
         */
        initComponents() {
            this.initSmoothScroll();
            this.initTooltips();
            this.initLazyLoading();
            this.initStickyHeader();
        }

        /**
         * Configurar AJAX
         */
        setupAjax() {
            // Configurar AJAX para WooCommerce
            if (typeof wc_add_to_cart_params !== 'undefined') {
                $(document.body).on('added_to_cart', this.onProductAddedToCart.bind(this));
                $(document.body).on('removed_from_cart', this.onProductRemovedFromCart.bind(this));
            }
        }

        /**
         * Evento cuando el documento está listo
         */
        onDocumentReady() {
            this.initWooCommerce();
            this.initForms();
            this.initAnimations();
            this.initMobileMenu();
        }

        /**
         * Evento cuando la ventana está cargada
         */
        onWindowLoad() {
            this.initLazyImages();
            this.initParallax();
        }

        /**
         * Evento cuando la ventana cambia de tamaño
         */
        onWindowResize() {
            this.updateStickyHeader();
            this.updateMobileMenu();
        }

        /**
         * Evento cuando se hace scroll
         */
        onWindowScroll() {
            this.updateStickyHeader();
            this.updateScrollProgress();
        }

        /**
         * Inicializar WooCommerce
         */
        initWooCommerce() {
            if (typeof wc_add_to_cart_params === 'undefined') {
                return;
            }

            // Personalizar mensajes de agregar al carrito
            this.customizeAddToCartMessages();

            // Mejorar UX del carrito
            this.enhanceCartUX();

            // Personalizar checkout
            this.enhanceCheckout();
        }

        /**
         * Personalizar mensajes de agregar al carrito
         */
        customizeAddToCartMessages() {
            $(document.body).on('added_to_cart', (event, fragments, cart_hash, button) => {
                const $button = $(button);
                const originalText = $button.data('original-text') || $button.text();
                
                // Cambiar texto temporalmente
                $button.text('¡Agregado!').addClass('added');
                
                setTimeout(() => {
                    $button.text(originalText).removeClass('added');
                }, 2000);

                // Mostrar notificación
                this.showNotification('Producto agregado al carrito', 'success');
            });
        }

        /**
         * Mejorar UX del carrito
         */
        enhanceCartUX() {
            // Hover en productos del carrito
            $('.woocommerce-cart-form__cart-item').hover(
                function() {
                    $(this).addClass('hover');
                },
                function() {
                    $(this).removeClass('hover');
                }
            );

            // Confirmar eliminación de productos
            $('.woocommerce-cart-form__cart-item .remove').on('click', (e) => {
                if (!confirm('¿Estás seguro de que quieres eliminar este producto?')) {
                    e.preventDefault();
                }
            });
        }

        /**
         * Mejorar checkout
         */
        enhanceCheckout() {
            if (!this.isCheckoutPage()) {
                return;
            }

            // Validación en tiempo real
            this.setupRealTimeValidation();

            // Mejorar campos de facturación
            this.enhanceBillingFields();

            // Mostrar progreso del checkout
            this.showCheckoutProgress();
        }

        /**
         * Configurar validación en tiempo real
         */
        setupRealTimeValidation() {
            const $fields = $('.woocommerce-checkout input, .woocommerce-checkout select, .woocommerce-checkout textarea');
            
            $fields.on('blur', function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if ($field.hasClass('required') && !value) {
                    $field.addClass('error');
                    $field.siblings('.error-message').remove();
                    $field.after('<span class="error-message">Este campo es requerido</span>');
                } else {
                    $field.removeClass('error');
                    $field.siblings('.error-message').remove();
                }
            });
        }

        /**
         * Mejorar campos de facturación
         */
        enhanceBillingFields() {
            // Auto-completar campos relacionados
            $('#billing_company').on('change', function() {
                const company = $(this).val();
                if (company) {
                    $('#billing_first_name').attr('placeholder', 'Nombre del contacto');
                    $('#billing_last_name').attr('placeholder', 'Apellido del contacto');
                }
            });

            // Validar RFC en tiempo real
            $('#billing_rfc').on('input', function() {
                const rfc = $(this).val().toUpperCase();
                $(this).val(rfc);
                
                if (rfc.length > 0 && rfc.length < 13) {
                    $(this).addClass('validating');
                } else {
                    $(this).removeClass('validating');
                }
            });
        }

        /**
         * Mostrar progreso del checkout
         */
        showCheckoutProgress() {
            const steps = ['Información', 'Envío', 'Pago', 'Confirmación'];
            const currentStep = this.getCurrentCheckoutStep();
            
            let progressHTML = '<div class="checkout-progress">';
            steps.forEach((step, index) => {
                const isActive = index === currentStep;
                const isCompleted = index < currentStep;
                
                progressHTML += `
                    <div class="progress-step ${isActive ? 'active' : ''} ${isCompleted ? 'completed' : ''}">
                        <div class="step-number">${index + 1}</div>
                        <div class="step-label">${step}</div>
                    </div>
                `;
            });
            progressHTML += '</div>';
            
            $('.woocommerce-checkout').prepend(progressHTML);
        }

        /**
         * Obtener paso actual del checkout
         */
        getCurrentCheckoutStep() {
            if ($('#customer_details').is(':visible')) {
                return 0;
            } else if ($('#shipping_method').is(':visible')) {
                return 1;
            } else if ($('#payment').is(':visible')) {
                return 2;
            } else {
                return 3;
            }
        }

        /**
         * Inicializar formularios
         */
        initForms() {
            // Mejorar UX de formularios
            $('form').on('submit', this.onFormSubmit.bind(this));
            
            // Auto-guardar formularios
            this.setupAutoSave();
        }

        /**
         * Evento de envío de formulario
         */
        onFormSubmit(e) {
            const $form = $(e.target);
            
            // Mostrar loading
            $form.addClass('submitting');
            $form.find('button[type="submit"]').prop('disabled', true);
            
            // Validar campos requeridos
            const $requiredFields = $form.find('[required]');
            let isValid = true;
            
            $requiredFields.each(function() {
                if (!$(this).val().trim()) {
                    $(this).addClass('error');
                    isValid = false;
                } else {
                    $(this).removeClass('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                $form.removeClass('submitting');
                $form.find('button[type="submit"]').prop('disabled', false);
                this.showNotification('Por favor completa todos los campos requeridos', 'error');
            }
        }

        /**
         * Configurar auto-guardado
         */
        setupAutoSave() {
            const $forms = $('form[data-autosave]');
            
            $forms.each(function() {
                const $form = $(this);
                const formId = $form.attr('id') || 'form_' + Math.random().toString(36).substr(2, 9);
                
                // Guardar datos en localStorage
                $form.find('input, textarea, select').on('change', function() {
                    const formData = $form.serialize();
                    localStorage.setItem('autosave_' + formId, formData);
                });
                
                // Restaurar datos al cargar
                const savedData = localStorage.getItem('autosave_' + formId);
                if (savedData) {
                    $form.deserialize(savedData);
                }
            });
        }

        /**
         * Inicializar animaciones
         */
        initAnimations() {
            // Animaciones de entrada
            this.initEntranceAnimations();
            
            // Animaciones de scroll
            this.initScrollAnimations();
        }

        /**
         * Inicializar animaciones de entrada
         */
        initEntranceAnimations() {
            const $elements = $('.animate-on-scroll');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animated');
                    }
                });
            });
            
            $elements.each(function() {
                observer.observe(this);
            });
        }

        /**
         * Inicializar animaciones de scroll
         */
        initScrollAnimations() {
            $(window).on('scroll', () => {
                const scrollTop = $(window).scrollTop();
                const windowHeight = $(window).height();
                
                $('.parallax').each(function() {
                    const $element = $(this);
                    const speed = $element.data('speed') || 0.5;
                    const yPos = -(scrollTop * speed);
                    $element.css('transform', `translateY(${yPos}px)`);
                });
            });
        }

        /**
         * Inicializar scroll suave
         */
        initSmoothScroll() {
            $('a[href^="#"]').on('click', function(e) {
                e.preventDefault();
                
                const target = $(this.getAttribute('href'));
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 800);
                }
            });
        }

        /**
         * Inicializar tooltips
         */
        initTooltips() {
            $('[data-tooltip]').each(function() {
                const $element = $(this);
                const tooltipText = $element.data('tooltip');
                
                $element.on('mouseenter', function() {
                    const $tooltip = $(`<div class="tooltip">${tooltipText}</div>`);
                    $('body').append($tooltip);
                    
                    const elementRect = this.getBoundingClientRect();
                    $tooltip.css({
                        position: 'absolute',
                        top: elementRect.top - $tooltip.outerHeight() - 10,
                        left: elementRect.left + (elementRect.width / 2) - ($tooltip.outerWidth() / 2),
                        zIndex: 1000
                    });
                });
                
                $element.on('mouseleave', function() {
                    $('.tooltip').remove();
                });
            });
        }

        /**
         * Inicializar carga lazy
         */
        initLazyLoading() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });
                
                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        }

        /**
         * Inicializar header sticky
         */
        initStickyHeader() {
            const $header = $('.site-header');
            const headerHeight = $header.outerHeight();
            
            $(window).on('scroll', () => {
                if ($(window).scrollTop() > headerHeight) {
                    $header.addClass('sticky');
                    $('body').css('padding-top', headerHeight);
                } else {
                    $header.removeClass('sticky');
                    $('body').css('padding-top', 0);
                }
            });
        }

        /**
         * Actualizar header sticky
         */
        updateStickyHeader() {
            // Implementar lógica específica si es necesario
        }

        /**
         * Actualizar progreso de scroll
         */
        updateScrollProgress() {
            const scrollTop = $(window).scrollTop();
            const docHeight = $(document).height() - $(window).height();
            const scrollPercent = (scrollTop / docHeight) * 100;
            
            $('.scroll-progress').css('width', scrollPercent + '%');
        }

        /**
         * Inicializar menú móvil
         */
        initMobileMenu() {
            $('.mobile-menu-toggle').on('click', function() {
                $('.mobile-menu').toggleClass('active');
                $('body').toggleClass('menu-open');
            });
        }

        /**
         * Actualizar menú móvil
         */
        updateMobileMenu() {
            // Implementar lógica específica si es necesario
        }

        /**
         * Inicializar imágenes lazy
         */
        initLazyImages() {
            // Implementar si es necesario
        }

        /**
         * Inicializar parallax
         */
        initParallax() {
            // Implementar si es necesario
        }

        /**
         * Evento cuando se agrega producto al carrito
         */
        onProductAddedToCart(event, fragments, cart_hash, button) {
            this.updateCartCount(fragments);
            this.showNotification('Producto agregado al carrito', 'success');
        }

        /**
         * Evento cuando se remueve producto del carrito
         */
        onProductRemovedFromCart(event, fragments, cart_hash, button) {
            this.updateCartCount(fragments);
            this.showNotification('Producto removido del carrito', 'info');
        }

        /**
         * Actualizar contador del carrito
         */
        updateCartCount(fragments) {
            if (fragments && fragments['.cart-count']) {
                $('.cart-count').replaceWith(fragments['.cart-count']);
            }
        }

        /**
         * Mostrar notificación
         */
        showNotification(message, type = 'info') {
            const $notification = $(`
                <div class="notification notification-${type}">
                    <span class="message">${message}</span>
                    <button class="close">&times;</button>
                </div>
            `);
            
            $('body').append($notification);
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                $notification.fadeOut(() => {
                    $notification.remove();
                });
            }, 5000);
            
            // Cerrar manualmente
            $notification.find('.close').on('click', function() {
                $notification.fadeOut(() => {
                    $notification.remove();
                });
            });
        }

        /**
         * Verificar si es página de checkout
         */
        isCheckoutPage() {
            return $('body').hasClass('woocommerce-checkout');
        }

        /**
         * Función debounce
         */
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }

    // Inicializar cuando el DOM esté listo
    $(document).ready(() => {
        new PalafitoChild();
    });

})(jQuery); 