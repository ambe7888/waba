<?php
/**
 * functions.php
 * @package WordPress
 * @subpackage Machic
 * @since Machic 1.6.4
 * 
 */

/*************************************************
## Get Theme Info
*************************************************/ 
if ( ! function_exists( 'machic_get_theme_info' ) ) {
	function machic_get_theme_info( $parameter ) {
		
		$theme_info = wp_get_theme( get_template() )->get( $parameter );
		
		return $theme_info;
	}
}

define( 'MACHIC_VERSION', machic_get_theme_info( 'Version' ) );

/*************************************************
## Admin style and scripts  
*************************************************/ 
function machic_admin_styles() {
	wp_enqueue_style('machic-klbtheme',     get_template_directory_uri() .'/assets/css/admin/klbtheme.css');
	wp_enqueue_script('machic-init', 	    get_template_directory_uri() .'/assets/js/init.js', array('jquery','media-upload','thickbox'));
    wp_enqueue_script('machic-menu-extra',  get_template_directory_uri() .'/assets/js/admin/menu-extra.js', array('jquery'), MACHIC_VERSION, true);
    wp_enqueue_script('machic-register',    get_template_directory_uri() .'/assets/js/admin/register.js', array('jquery'), MACHIC_VERSION, true);
	wp_register_style( 'machic-klbicon', 	get_template_directory_uri() .'/assets/css/klbicon.css', false, MACHIC_VERSION);
}
add_action('admin_enqueue_scripts', 'machic_admin_styles');

/*************************************************
## Styles and Scripts
*************************************************/ 
define('MACHIC_INDEX_CSS', 	  get_template_directory_uri()  . '/assets/css');
define('MACHIC_INDEX_JS', 	  get_template_directory_uri()  . '/assets/js');

function machic_scripts() {

	if ( is_admin_bar_showing() ) {
		wp_enqueue_style( 'machic-klbtheme', MACHIC_INDEX_CSS . '/admin/klbtheme.css', false, MACHIC_VERSION);    
	}	

	if ( is_singular() ) wp_enqueue_script( 'comment-reply' );

	wp_enqueue_style( 'machic-typekit', 		MACHIC_INDEX_CSS . '/typekit.css', false, MACHIC_VERSION);
	wp_enqueue_style( 'bootstrap', 				MACHIC_INDEX_CSS . '/bootstrap.min.css', false, MACHIC_VERSION);
	wp_enqueue_style( 'select2', 				MACHIC_INDEX_CSS . '/select2.min.css', false, MACHIC_VERSION);
	wp_register_style( 'owl-carousel', 			MACHIC_INDEX_CSS . '/vendor/owl.carousel.min.css', false, MACHIC_VERSION);
	wp_register_style( 'owl-theme-default', 	MACHIC_INDEX_CSS . '/vendor/owl.theme.default.min.css', false, MACHIC_VERSION);
	wp_enqueue_style( 'machic-base', 			MACHIC_INDEX_CSS . '/base.css', false, MACHIC_VERSION);
	wp_enqueue_style( 'machic-klbicon', 		MACHIC_INDEX_CSS . '/klbicon.css', false, MACHIC_VERSION);
	wp_style_add_data( 'machic-base', 'rtl', 'replace' );
	wp_enqueue_style( 'machic-style',         	get_stylesheet_uri() );
	wp_style_add_data( 'machic-style', 'rtl', 'replace' );

	$mapkey = get_theme_mod('machic_mapapi');

	wp_enqueue_script( 'imagesloaded');
	wp_enqueue_script( 'bootstrap-bundle',    	     MACHIC_INDEX_JS . '/bootstrap.bundle.min.js', array('jquery'), MACHIC_VERSION, true);
	wp_enqueue_script( 'select2-full',    	 	     MACHIC_INDEX_JS . '/select2.full.min.js', array('jquery'), MACHIC_VERSION, true);
	wp_enqueue_script( 'gsap',    	    		     MACHIC_INDEX_JS . '/vendor/gsap.min.js', array('jquery'), MACHIC_VERSION, true);
	wp_enqueue_script( 'hover-slider',    	         MACHIC_INDEX_JS . '/vendor/hover-slider.min.js', array('jquery'), MACHIC_VERSION, true);
	wp_enqueue_script( 'jquery-magnific-popup',      MACHIC_INDEX_JS . '/vendor/jquery.magnific-popup.min.js', array('jquery'), MACHIC_VERSION, true);
	if ( is_rtl() ) {
	wp_enqueue_script( 'machic-klbmenu-rtl',  		     MACHIC_INDEX_JS . '/vendor/klbmenu-rtl.js', array('jquery'), MACHIC_VERSION, true);
	} else {
	wp_enqueue_script( 'machic-klbmenu',  		     MACHIC_INDEX_JS . '/vendor/klbmenu.js', array('jquery'), MACHIC_VERSION, true);
	}
	wp_enqueue_script( 'perfect-scrolllbar',         MACHIC_INDEX_JS . '/vendor/perfect-scrollbar.min.js', array('jquery'), MACHIC_VERSION, true);
	wp_enqueue_script( 'swiper-bundle',    	         MACHIC_INDEX_JS . '/vendor/swiper-bundle.min.js', array('jquery'), MACHIC_VERSION, true);
	wp_enqueue_script( 'theia-sticky-sidebar',       MACHIC_INDEX_JS . '/vendor/theia-sticky-sidebar.min.js', array('jquery'), MACHIC_VERSION, true);
	wp_enqueue_script( 'machic-productsorting',      MACHIC_INDEX_JS . '/custom/productSorting.js', array('jquery'), MACHIC_VERSION, true);
	wp_enqueue_script( 'machic-producthover',        MACHIC_INDEX_JS . '/custom/productHover.js', array('jquery'), MACHIC_VERSION, true);
	wp_enqueue_script( 'machic-producthoverSlider',  MACHIC_INDEX_JS . '/custom/productHoverSlider.js', array('jquery'), MACHIC_VERSION, true);
	wp_enqueue_script( 'machic-sidebarfilter',       MACHIC_INDEX_JS . '/custom/sidebarfilter.js', array('jquery'), MACHIC_VERSION, true);
	wp_enqueue_script( 'machic-sitescroll',          MACHIC_INDEX_JS . '/custom/sitescroll.js', array('jquery'), MACHIC_VERSION, true);
	wp_enqueue_script( 'machic-counter',   		     MACHIC_INDEX_JS . '/custom/counter.js', array('jquery'), MACHIC_VERSION, true);
	wp_enqueue_script( 'machic-siteslider',   		 MACHIC_INDEX_JS . '/custom/siteslider.js', array('jquery'), MACHIC_VERSION, true);
	wp_register_script( 'owl-carousel',   		     MACHIC_INDEX_JS . '/vendor/owl.carousel.min.js', array('jquery'), MACHIC_VERSION, true);
	wp_register_script( 'machic_flex_thumbs',        MACHIC_INDEX_JS . '/custom/flex_thumbs.js', array('jquery'), MACHIC_VERSION, true);
	wp_register_script( 'machic_mini_cart_slider',   MACHIC_INDEX_JS . '/custom/mini_cart_slider.js', array('jquery'), MACHIC_VERSION, true);
	wp_register_script( 'machic-loginform',   		 MACHIC_INDEX_JS . '/custom/loginform.js', array('jquery'), MACHIC_VERSION, true);
	wp_register_script( 'machic-googlemap',          '//maps.googleapis.com/maps/api/js?key='. $mapkey .'', array('jquery'), MACHIC_VERSION, true);
	wp_enqueue_script( 'machic-bundle',     	     MACHIC_INDEX_JS . '/bundle.js', array('jquery'), MACHIC_VERSION, true);
}
add_action( 'wp_enqueue_scripts', 'machic_scripts' );

/*************************************************
## Theme Setup
*************************************************/ 

if ( ! isset( $content_width ) ) $content_width = 960;

function machic_theme_setup() {
	
	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-background' );
	add_theme_support( 'post-formats', array('gallery', 'audio', 'video'));
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
	add_theme_support( 'woocommerce', array('gallery_thumbnail_image_width' => 99,'thumbnail_image_width' => 90,) );
	load_theme_textdomain( 'machic', get_template_directory() . '/languages' );
	remove_theme_support( 'widgets-block-editor' );

}
add_action( 'after_setup_theme', 'machic_theme_setup' );


/*************************************************
## Include the TGM_Plugin_Activation class.
*************************************************/ 

require_once get_template_directory() . '/includes/class-tgm-plugin-activation.php';

add_action( 'tgmpa_register', 'machic_register_required_plugins' );

function machic_register_required_plugins() {

	$url = 'http://klbtheme.com/machic/plugins/';
	$mainurl = 'http://klbtheme.com/plugins/';

	$plugins = array(
		
        array(
            'name'                  => esc_html__('Meta Box','machic'),
            'slug'                  => 'meta-box',
        ),

        array(
            'name'                  => esc_html__('Contact Form 7','machic'),
            'slug'                  => 'contact-form-7',
        ),
		
        array(
            'name'                  => esc_html__('Kirki','machic'),
            'slug'                  => 'kirki',
        ),
		
		array(
            'name'                  => esc_html__('MailChimp Subscribe','machic'),
            'slug'                  => 'mailchimp-for-wp',
        ),
		
        array(
            'name'                  => esc_html__('Elementor','machic'),
            'slug'                  => 'elementor',
            'required'              => true,
        ),
		
        array(
            'name'                  => esc_html__('WooCommerce','machic'),
            'slug'                  => 'woocommerce',
            'required'              => true,
        ),

        array(
            'name'                  => esc_html__('Machic Core','machic'),
            'slug'                  => 'machic-core',
            'source'                => $url . 'machic-core.zip',
            'required'              => true,
            'version'               => '1.6.1',
            'force_activation'      => false,
            'force_deactivation'    => false,
            'external_url'          => '',
        ),

        array(
            'name'                  => esc_html__('Envato Market','machic'),
            'slug'                  => 'envato-market',
            'source'                => $mainurl . 'envato-market.zip',
            'required'              => true,
            'version'               => '2.0.12',
            'force_activation'      => false,
            'force_deactivation'    => false,
            'external_url'          => '',
        ),


	);

	$config = array(
		'id'           => 'machic',                 // Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',                      // Default absolute path to bundled plugins.
		'menu'         => 'tgmpa-install-plugins', // Menu slug.
		'parent_slug'  => 'themes.php',            // Parent menu slug.
		'capability'   => 'edit_theme_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
		'has_notices'  => true,                    // Show admin notices or not.
		'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => false,                   // Automatically activate plugins after installation or not.
		'message'      => '',                      // Message to output right before the plugins table.
	);

	tgmpa( $plugins, $config );
}

/*************************************************
## Machic Register Menu 
*************************************************/

function machic_register_menus() {
	register_nav_menus( array( 'main-menu' 	   => esc_html__('Primary Navigation Menu','machic')) );
	
	$topheader = get_theme_mod('machic_top_header','0');
	$sidebarmenu = get_theme_mod('machic_header_sidebar','0');

	if($sidebarmenu == '1'){
		register_nav_menus( array( 'sidebar-menu'     => esc_html__('Sidebar Menu','machic')) );
	}
	
	if($topheader == '1'){
		register_nav_menus( array( 'canvas-bottom' 	   => esc_html__('Canvas Bottom','machic')) );
		register_nav_menus( array( 'top-right-menu'    => esc_html__('Top Right Menu','machic')) );
		register_nav_menus( array( 'top-left-menu'     => esc_html__('Top Left Menu','machic')) );
	}
}
add_action('init', 'machic_register_menus');

/*************************************************
## Machic Main Menu
*************************************************/ 
class machic_main_walker extends Walker_Nav_Menu {
	function start_lvl( &$output, $depth = 0, $args = array() ) {
		// depth dependent classes
		$indent = ( $depth > 0  ? str_repeat( "\t", $depth ) : '' ); // code indent
		$display_depth = ( $depth + 1); // because it counts the first submenu as 0
		$classes = array(
			'',
			( $display_depth % 2  ? '' : '' ),
			( $display_depth >=2 ? '' : '' ),
			
			);
		$class_names = implode( ' ', $classes );
	  
		// build html
		$output .= "\n" . $indent . '<ul class="sub-menu">' . "\n";
	}

    function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ){
        $id_field = $this->db_fields['id'];
        if ( is_object( $args[0] ) ) {
            $args[0]->has_children = ! empty( $children_elements[$element->$id_field] );
        }
        return parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
    }
      function start_el(&$output, $object, $depth = 0, $args = Array() , $current_object_id = 0) {
           
            global $wp_query;

            $indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

            $class_names = $value = '';
		   
		    $classes = empty( $object->classes ) ? array() : (array) $object->classes;
            $icon_class = $classes[0];
		    $classes = array_slice($classes,1);
		   
		    $class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $object ) );
		    
			
			if ( $icon_class == 'mega-menu' ) {
				$class_names = 'class="'.esc_attr($icon_class).' '. esc_attr( $class_names ) . '"';
			} else {
				$class_names = 'class="'. esc_attr( $class_names ) . '"';
			}
			
			$output .= $indent . '<li ' . $value . $class_names .'>';
			
			$attributes = ! empty( $object->url ) ? ' href="'   . esc_attr( $object->url ) .'"' : '';
            $attributes .= ! empty( $object->attr_title ) ? ' title="'  . esc_attr( $object->attr_title ) .'"' : '';
            $attributes .= ! empty( $object->target )     ? ' target="' . esc_attr( $object->target     ) .'"' : '';
            $attributes .= ! empty( $object->xfn )        ? ' rel="'    . esc_attr( $object->xfn        ) .'"' : '';

			$object_output = $args->before;

			$object_output .= '<a'. $attributes .'  >';
			if($icon_class && $icon_class != 'mega-menu'){
			$object_output .= '<i class="'.esc_attr($icon_class).'"></i> ';
			}
			$object_output .= $args->link_before .  apply_filters( 'the_title', $object->title, $object->ID ) . '';
	        $object_output .= $args->link_after;
			$object_output .= '</a>';


			$object_output .= $args->after;

			$output .= apply_filters( 'walker_nav_menu_start_el', $object_output, $object, $depth, $args );            	              	
      }
}

/*************************************************
## Machic Sidebar Menu
*************************************************/ 
class machic_sidebar_walker extends Walker_Nav_Menu {
	
	private $curItem;
	
	function start_lvl( &$output, $depth = 0, $args = array() ) {
		// depth dependent classes
		$indent = ( $depth > 0  ? str_repeat( "\t", $depth ) : '' ); // code indent
		$display_depth = ( $depth + 1); // because it counts the first submenu as 0
		$classes = array(
			'',
			( $display_depth % 2  ? '' : '' ),
			( $display_depth >=2 ? '' : '' ),
			
			);
		$class_names = implode( ' ', $classes );
		// build html
		$menu_item_menuimage = get_post_meta( $this->curItem->ID, '_menu_item_menuimage', true );
		$image_attributes = wp_get_attachment_image_src( $menu_item_menuimage, 'full' );
		
		if ($image_attributes != '' ) {
		$output .= "\n" . $indent . '<ul class="sub-menu" style="background-image: url('.esc_url( $image_attributes[0]).');">' . "\n";
		} else {
		$output .= "\n" . $indent . '<ul class="sub-menu">' . "\n";
		}
	}

    function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ){
        $id_field = $this->db_fields['id'];
        if ( is_object( $args[0] ) ) {
            $args[0]->has_children = ! empty( $children_elements[$element->$id_field] );
        }
        return parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
    }
      function start_el(&$output, $object, $depth = 0, $args = Array() , $current_object_id = 0) {
           $this->curItem = $object;
           global $wp_query;

           $indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

           $class_names = $value = '';
		   
		   $classes = empty( $object->classes ) ? array() : (array) $object->classes;
		   $myclasses = empty( $object->classes ) ? array() : (array) $object->classes;
           $icon_class = $classes[0];
		   $classes = array_slice($classes,1);
		   
		 
		   $class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $object ) );
		   
		   if ( $args->has_children ) {
			   $menu_item_megamenu_columns = get_post_meta( $object->ID, '_menu_item_megamenu_columns', true );
			   $menu_item_menuimage = get_post_meta( $this->curItem->ID, '_menu_item_menuimage', true );
			   if($menu_item_menuimage){
				$class_names = 'class="'. esc_attr( $class_names ) . ' has-image column-'.esc_attr( $menu_item_megamenu_columns).'"';
			   } else {
				$class_names = 'class="'. esc_attr( $class_names ) . ' column-'.esc_attr( $menu_item_megamenu_columns).'"';
			   }
		   } else {
			$class_names = 'class="'. esc_attr( $class_names ) . '"';
		   }
			
			$output .= $indent . '<li ' . $value . $class_names .'>';

			$datahover = str_replace(' ','',$object->title);


			$attributes = ! empty( $object->url ) ? ' href="'   . esc_attr( $object->url ) .'"' : '';


			$object_output = $args->before;

			$object_output .= '<a'. $attributes .'  >';
			if($icon_class){
			$object_output .= '<i class="'.esc_attr($icon_class).'"></i> ';
			}
			$object_output .= $args->link_before .  apply_filters( 'the_title', $object->title, $object->ID ) . '';
	        $object_output .= $args->link_after;
			
			if($object->description){
			$object_output .= '<span class="label danger">'.$object->description.'</span>';
			}
			$object_output .= '</a>';


			$object_output .= $args->after;

			$output .= apply_filters( 'walker_nav_menu_start_el', $object_output, $object, $depth, $args );            	              	
      }
}

/*************************************************
## Excerpt More
*************************************************/ 

function machic_excerpt_more($more) {
  global $post;
  return '<div class="klb-readmore entry-button"><a class="btn read-more" href="'. esc_url(get_permalink($post->ID)) . '">' . esc_html__('Read More', 'machic') . '</a></div>';
  }
 add_filter('excerpt_more', 'machic_excerpt_more');
 
/*************************************************
## Word Limiter
*************************************************/ 
function machic_limit_words($string, $limit) {
	$words = explode(' ', $string);
	return implode(' ', array_slice($words, 0, $limit));
}

/*************************************************
## Widgets
*************************************************/ 

function machic_widgets_init() {
	register_sidebar( array(
	  'name' => esc_html__( 'Blog Sidebar', 'machic' ),
	  'id' => 'blog-sidebar',
	  'description'   => esc_html__( 'These are widgets for the Blog page.','machic' ),
	  'before_widget' => '<div class="widget %2$s">',
	  'after_widget'  => '</div>',
	  'before_title'  => '<h4 class="widget-title">',
	  'after_title'   => '</h4>'
	) );

	register_sidebar( array(
	  'name' => esc_html__( 'Shop Sidebar', 'machic' ),
	  'id' => 'shop-sidebar',
	  'description'   => esc_html__( 'These are widgets for the Shop.','machic' ),
	  'before_widget' => '<div class="widget %2$s">',
	  'after_widget'  => '</div>',
	  'before_title'  => '<h4 class="widget-title">',
	  'after_title'   => '</h4>'
	) );

	register_sidebar( array(
	  'name' => esc_html__( 'Footer First Column', 'machic' ),
	  'id' => 'footer-1',
	  'description'   => esc_html__( 'These are widgets for the Footer.','machic' ),
	  'before_widget' => '<div class="klbfooterwidget widget %2$s">',
	  'after_widget'  => '</div>',
	  'before_title'  => '<h4 class="widget-title">',
	  'after_title'   => '</h4>'
	) );

	register_sidebar( array(
	  'name' => esc_html__( 'Footer Second Column', 'machic' ),
	  'id' => 'footer-2',
	  'description'   => esc_html__( 'These are widgets for the Footer.','machic' ),
	  'before_widget' => '<div class="klbfooterwidget widget %2$s">',
	  'after_widget'  => '</div>',
	  'before_title'  => '<h4 class="widget-title">',
	  'after_title'   => '</h4>'
	) );

	register_sidebar( array(
	  'name' => esc_html__( 'Footer Third Column', 'machic' ),
	  'id' => 'footer-3',
	  'description'   => esc_html__( 'These are widgets for the Footer.','machic' ),
	  'before_widget' => '<div class="klbfooterwidget widget %2$s">',
	  'after_widget'  => '</div>',
	  'before_title'  => '<h4 class="widget-title">',
	  'after_title'   => '</h4>'
	) );

	register_sidebar( array(
	  'name' => esc_html__( 'Footer Fourth Column', 'machic' ),
	  'id' => 'footer-4',
	  'description'   => esc_html__( 'These are widgets for the Footer.','machic' ),
	  'before_widget' => '<div class="klbfooterwidget widget %2$s">',
	  'after_widget'  => '</div>',
	  'before_title'  => '<h4 class="widget-title">',
	  'after_title'   => '</h4>'
	) );

	register_sidebar( array(
	  'name' => esc_html__( 'Footer Fifth Column', 'machic' ),
	  'id' => 'footer-5',
	  'description'   => esc_html__( 'These are widgets for the Footer.','machic' ),
	  'before_widget' => '<div class="klbfooterwidget widget %2$s">',
	  'after_widget'  => '</div>',
	  'before_title'  => '<h4 class="widget-title">',
	  'after_title'   => '</h4>'
	) );
}
add_action( 'widgets_init', 'machic_widgets_init' );
 
/*************************************************
## Machic Comment
*************************************************/

if ( ! function_exists( 'machic_comment' ) ) :
 function machic_comment( $comment, $args, $depth ) {
  $GLOBALS['comment'] = $comment;
  switch ( $comment->comment_type ) :
   case 'pingback' :
   case 'trackback' :
  ?>

   <article class="post pingback">
   <p><?php esc_html_e( 'Pingback:', 'machic' ); ?> <?php comment_author_link(); ?><?php edit_comment_link( esc_html__( '(Edit)', 'machic' ), ' ' ); ?></p>
  <?php
    break;
   default :
  ?>
  
	<li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
		<div id="div-comment-<?php comment_ID(); ?>" class="comment-body">
			<div class="comment-avatar">
				<div class="comment-author vcard">
					<img src="<?php echo get_avatar_url( $comment, 90 ); ?>" alt="<?php comment_author(); ?>" class="avatar">
				</div>
			</div>
			<div class="comment-content">
				<div class="comment-meta">
					<b class="fn"><a class="url"><?php comment_author(); ?></a></b>
					<div class="comment-metadata">
						<time><?php comment_date(); ?></time>
					</div>
				</div>
				<div class="klb-post">
					<?php comment_text(); ?>
					<?php if ( $comment->comment_approved == '0' ) : ?>
					<em><?php esc_html_e( 'Your comment is awaiting moderation.', 'machic' ); ?></em>
					<?php endif; ?>
				</div>
				<div class="reply">
					<?php comment_reply_link( array_merge( $args, array( 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
				</div>
			</div>

		</div>
	</li>


  <?php
    break;
  endswitch;
 }
endif;

/*************************************************
## Machic Widget Count Filter
 *************************************************/

function machic_cat_count_span($links) {
  $links = str_replace('</a> (', '</a> <span class="catcount">(', $links);
  $links = str_replace(')', ')</span>', $links);
  return machic_sanitize_data($links);
}
add_filter('wp_list_categories', 'machic_cat_count_span');
 
function machic_archive_count_span( $links ) {
	$links = str_replace( '</a>&nbsp;(', '</a><span class="catcount">(', $links );
	$links = str_replace( ')', ')</span>', $links );
	return machic_sanitize_data($links);
}
add_filter( 'get_archives_link', 'machic_archive_count_span' );


/*************************************************
## Pingback url auto-discovery header for single posts, pages, or attachments
 *************************************************/
function machic_pingback_header() {
	if ( is_singular() && pings_open() ) {
		echo '<link rel="pingback" href="', esc_url( get_bloginfo( 'pingback_url' ) ), '">';
	}
}
add_action( 'wp_head', 'machic_pingback_header' );

/************************************************************
## DATA CONTROL FROM PAGE METABOX OR ELEMENTOR PAGE SETTINGS
*************************************************************/
function machic_page_settings( $opt_id){
	
	if ( class_exists( '\Elementor\Core\Settings\Manager' ) ) {
		// Get the current post id
		$post_id = get_the_ID();

		// Get the page settings manager
		$page_settings_manager = \Elementor\Core\Settings\Manager::get_settings_managers( 'page' );

		// Get the settings model for current post
		$page_settings_model = $page_settings_manager->get_model( $post_id )->get_data('settings');

		// Retrieve the color we added before
		return isset($page_settings_model['machic_elementor_'.$opt_id]) ? $page_settings_model['machic_elementor_'.$opt_id] : false;
	}
}

/************************************************************
## Elementor Register Location
*************************************************************/
function machic_register_elementor_locations( $elementor_theme_manager ) {

    $elementor_theme_manager->register_location( 'header' );
    $elementor_theme_manager->register_location( 'footer' );
    $elementor_theme_manager->register_location( 'single' );
	$elementor_theme_manager->register_location( 'archive' );

}
add_action( 'elementor/theme/register_locations', 'machic_register_elementor_locations' );

/************************************************************
## Elementor Get Templates
*************************************************************/
function machic_get_elementor_template($template_id){
	if($template_id){

	    $frontend = new \Elementor\Frontend;
	    printf( '<div class="machic-elementor-template template-'.esc_attr($template_id).'">%1$s</div>', $frontend->get_builder_content_for_display( $template_id, true ) );
	
	    if ( class_exists( '\Elementor\Plugin' ) ) {
	        $elementor = \Elementor\Plugin::instance();
	        $elementor->frontend->enqueue_styles();
			$elementor->frontend->enqueue_scripts();
	    }
	
	    if ( class_exists( '\ElementorPro\Plugin' ) ) {
	        $elementor_pro = \ElementorPro\Plugin::instance();
	        $elementor_pro->enqueue_styles();
	    }

	}

}
add_action( 'machic_before_main_shop', 'machic_get_elementor_template', 10);
add_action( 'machic_after_main_shop', 'machic_get_elementor_template', 10);
add_action( 'machic_before_main_footer', 'machic_get_elementor_template', 10);
add_action( 'machic_after_main_footer', 'machic_get_elementor_template', 10);
add_action( 'machic_before_main_header', 'machic_get_elementor_template', 10);
add_action( 'machic_after_main_header', 'machic_get_elementor_template', 10);

/************************************************************
## Do Action for Templates and Product Categories
*************************************************************/
function machic_do_action($hook){
	
	if ( !class_exists( 'woocommerce' ) ) {
		return;
	}

	$categorytemplate = get_theme_mod('machic_elementor_template_each_shop_category');
	if(is_product_category()){
		if($categorytemplate && array_search(get_queried_object()->term_id, array_column($categorytemplate, 'category_id')) !== false){
			foreach($categorytemplate as $c){
				if($c['category_id'] == get_queried_object()->term_id){
					do_action( $hook, $c[$hook.'_elementor_template_category']);
				}
			}
		} else {
			do_action( $hook, get_theme_mod($hook.'_elementor_template'));
		}
	} else {
		do_action( $hook, get_theme_mod($hook.'_elementor_template'));
	}
	
}

/*************************************************
## Machic Get Image
*************************************************/
function machic_get_image($image){
	$img = ! wp_attachment_is_image($image) ? $image : wp_get_attachment_url($image);
	
	return esc_html($img);
}

/*************************************************
## Machic Get options
*************************************************/
function machic_get_option(){	
	$getopt  = isset( $_GET['opt'] ) ? $_GET['opt'] : '';

	return esc_html($getopt);
}

/*************************************************
## Machic Custom 404 Page
*************************************************/ 
if ( ! function_exists( 'machic_custom_404_page' ) ) {
	function machic_custom_404_page( $template ) {
		global $wp_query;
		$custom_404 = get_theme_mod('machic_404_page');

		if ( $custom_404 === 'default' || empty( $custom_404 ) ) {
			return $template;
		}

		$wp_query->query( 'page_id=' . $custom_404 );
		$wp_query->the_post();
		$template = get_page_template();
		rewind_posts();

		return $template;
	}

	add_filter( '404_template', 'machic_custom_404_page', 999 );
}

/*************************************************
## Machic Theme options
*************************************************/

	require_once get_template_directory() . '/includes/metaboxes.php';
	require_once get_template_directory() . '/includes/woocommerce.php';
	require_once get_template_directory() . '/includes/woocommerce-filter.php';
	require_once get_template_directory() . '/includes/sanitize.php';
	require_once get_template_directory() . '/includes/extra-menu-field.php';
	require_once get_template_directory() . '/includes/merlin/theme-register.php';
	require_once get_template_directory() . '/includes/merlin/setup-wizard.php';
	require_once get_template_directory() . '/includes/pjax/filter-functions.php';
	require_once get_template_directory() . '/includes/header/main-header.php';
	require_once get_template_directory() . '/includes/footer/main-footer.php';