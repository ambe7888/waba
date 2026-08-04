<?php
/**
* Plugin Name: Machic Core
* Description: Premium & Advanced Essential Elements for Elementor
* Plugin URI:  http://themeforest.net/user/KlbTheme
* Version:     1.6.0
* Author:      KlbTheme
* Author URI:  http://themeforest.net/user/KlbTheme
*/


/*
* Exit if accessed directly.
*/

if ( ! defined( 'ABSPATH' ) ) exit;

final class Machic_Elementor_Addons
{
    /**
    * Plugin Version
    *
    * @since 1.0
    *
    * @var string The plugin version.
    */
    const VERSION = '1.0.0';

    /**
    * Minimum Elementor Version
    *
    * @since 1.0
    *
    * @var string Minimum Elementor version required to run the plugin.
    */
    const MINIMUM_ELEMENTOR_VERSION = '2.0.0';

    /**
    * Minimum PHP Version
    *
    * @since 1.0
    *
    * @var string Minimum PHP version required to run the plugin.
    */
    const MINIMUM_PHP_VERSION = '7.0';

    /**
    * Instance
    *
    * @since 1.0
    *
    * @access private
    * @static
    *
    * @var Machic_Elementor_Addons The single instance of the class.
    */
    private static $_instance = null;

    /**
    * Instance
    *
    * Ensures only one instance of the class is loaded or can be loaded.
    *
    * @since 1.0
    *
    * @access public
    * @static
    *
    * @return Machic_Elementor_Addons An instance of the class.
    */
    public static function instance()
    {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
    * Constructor
    *
    * @since 1.0
    *
    * @access public
    */
    public function __construct()
    {
        add_action( 'init', [ $this, 'i18n' ] );
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    /**
    * Load Textdomain
    *
    * Load plugin localization files.
    *
    * Fired by `init` action hook.
    *
    * @since 1.0
    *
    * @access public
    */
    public function i18n()
    {
        load_plugin_textdomain( 'machic-core' );

        /* Customizer Kirki */
        require_once( __DIR__ . '/inc/customizer.php' );
    }
	
   /**
    * Initialize the plugin
    *
    * Load the plugin only after Elementor (and other plugins) are loaded.
    * Checks for basic plugin requirements, if one check fail don't continue,
    * if all check have passed load the files required to run the plugin.
    *
    * Fired by `plugins_loaded` action hook.
    *
    * @since 1.0
    *
    * @access public
    */
    public function init()
    {
        // Check if Elementor is installed and activated
        if ( ! did_action( 'elementor/loaded' ) ) {
            add_action( 'admin_notices', [ $this, 'machic_admin_notice_missing_main_plugin' ] );
            return;
        }
        // Check for required Elementor version
        if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
            add_action( 'admin_notices', [ $this, 'machic_admin_notice_minimum_elementor_version' ] );
            return;
        }
        // Check for required PHP version
        if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
            add_action( 'admin_notices', [ $this, 'machic_admin_notice_minimum_php_version' ] );
            return;
        }
		
		// Categories registered
        add_action( 'elementor/elements/categories_registered', [ $this, 'machic_add_widget_category' ] );

		/* Init Include */
        require_once( __DIR__ . '/init.php' );

        /* Style php */
        require_once( __DIR__ . '/inc/style.php' );
		
		/* Aq Resizer Image Resize */
        require_once( __DIR__ . '/inc/aq_resizer.php' );
		
		/* Breadcrumb */
        require_once( __DIR__ . '/inc/breadcrumb.php' );

		/* Menu Item Icon */
		require_once( __DIR__ . '/inc/menu-item-icon/menu-item-icon.php' );

		/* Menu Endpoints - Mega Menu*/
		require_once( __DIR__ . '/inc/menu-endpoints/menu-endpoints.php' );
		
		/* Newsletter */
		if(get_theme_mod('machic_newsletter_popup_toggle',0) == 1){
			require_once( __DIR__ . '/inc/newsletter-popup/newsletter-main.php' );
		}

		/* GDPR */
		if(get_theme_mod('machic_gdpr_toggle',0) == 1){
			require_once( __DIR__ . '/inc/gdpr/gdpr.php' );
		}

		/* Post view for popular posts widget */
        require_once( __DIR__ . '/inc/post_view.php' );

        /* Popular Posts Widget */
        require_once( __DIR__ . '/widgets/widget-popular-posts.php' );
		
		/* WooCommerce Filter */
        require_once( __DIR__ . '/woocommerce-filter/woocommerce-filter.php' );
		
        /* Custom plugin helper functions */
        require_once( __DIR__ . '/elementor/classes/class-helpers-functions.php' );
		
        /* Custom plugin helper functions */
        require_once( __DIR__ . '/elementor/classes/class-customizing-page-settings.php' );

		/* Maintenance */
		if(get_theme_mod('machic_maintenance_toggle', 0) == 1 || machic_ft() == 'maintenance'){
			require_once( __DIR__ . '/inc/maintenance/maintenance.php' );
		}

		/* Icon Picker */
		require_once( __DIR__ . '/inc/icon-picker/icon-picker.php' );

        // Register Widget Styles
        add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'widget_styles' ] );
		
        // Register Widget Scripts
        add_action( 'elementor/frontend/after_enqueue_scripts', [ $this, 'widget_scripts' ] );
		
		// Register Widget Editor Style
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'widget_editor_style' ] );

        // Register Widget Editor Scripts
        add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'widget_editor_scripts' ] );
		
        // Widgets registered
        add_action( 'elementor/widgets/register', [ $this, 'init_widgets' ] );
    }
	
    /**
    * Register Widgets Category
    *
    */
    public function machic_add_widget_category( $elements_manager )
    {
        $elements_manager->add_category( 'machic', ['title' => esc_html__( 'Machic Core', 'machic' )]);
    }	
	
    /**
    * Init Widgets
    *
    * Include widgets files and register them
    *
    * @since 1.0
    *
    * @access public
    */
    public function init_widgets()
    {

		// Home Slider
		require_once( __DIR__ . '/elementor/widgets/machic-home-slider.php' );
		\Elementor\Plugin::instance()->widgets_manager->register( new \Elementor\Machic_Home_Slider_Widget() );
		
		// Custom Title
		require_once( __DIR__ . '/elementor/widgets/machic-custom-title.php' );
		\Elementor\Plugin::instance()->widgets_manager->register( new \Elementor\Machic_Custom_Title_Widget() );
		
		// Clients Box
		require_once( __DIR__ . '/elementor/widgets/machic-clients-box.php' );
		\Elementor\Plugin::instance()->widgets_manager->register( new \Elementor\Machic_Clients_Box_Widget() );
		
		// Banner Box
		require_once( __DIR__ . '/elementor/widgets/machic-banner-box.php' );
		\Elementor\Plugin::instance()->widgets_manager->register( new \Elementor\Machic_Banner_Box_Widget() );
		
		// Banner Box2
		require_once( __DIR__ . '/elementor/widgets/machic-banner-box2.php' );
		\Elementor\Plugin::instance()->widgets_manager->register( new \Elementor\Machic_Banner_Box2_Widget() );
		
		// Banner Box3
		require_once( __DIR__ . '/elementor/widgets/machic-banner-box3.php' );
		\Elementor\Plugin::instance()->widgets_manager->register( new \Elementor\Machic_Banner_Box3_Widget() );
		
		// Icon List
		require_once( __DIR__ . '/elementor/widgets/machic-icon-list.php' );
		\Elementor\Plugin::instance()->widgets_manager->register( new \Elementor\Machic_Icon_List_Widget() );

		// Product Grid
		require_once( __DIR__ . '/elementor/widgets/machic-product-grid.php' );
		\Elementor\Plugin::instance()->widgets_manager->register( new \Elementor\Machic_Product_Grid_Widget() );

		// Deals Product
		require_once( __DIR__ . '/elementor/widgets/machic-deals-product.php' );
		\Elementor\Plugin::instance()->widgets_manager->register( new \Elementor\Machic_Deals_Product_Widget() );
		
		// Text Banner
		require_once( __DIR__ . '/elementor/widgets/machic-text-banner.php' );
		\Elementor\Plugin::instance()->widgets_manager->register( new \Elementor\Machic_Text_Banner_Widget() );
		
		// Text Banner
		require_once( __DIR__ . '/elementor/widgets/machic-text-banner2.php' );
		\Elementor\Plugin::instance()->widgets_manager->register( new \Elementor\Machic_Text_Banner2_Widget() );
		
		// Category Banner
		require_once( __DIR__ . '/elementor/widgets/machic-category-banner.php' );
		\Elementor\Plugin::instance()->widgets_manager->register( new \El