<?php
if (!ABSPATH) {
    return;
}

add_action( 'wp_enqueue_scripts', 'unguess_styles' );
function unguess_styles() {
	$theme = wp_get_theme();
	wp_enqueue_style( 'unguess-style',
        get_stylesheet_uri(),
		array(),  // If the parent theme code has a dependency, copy it to here.
		//$theme->parent()->get( 'Version' )
		'1.1.7'
	);
}

add_action( 'wp_enqueue_scripts', 'unguess_scripts', 10, 1 );
function unguess_scripts($type) {
	switch ($type) {
		case 'custom-carousel':
			wp_enqueue_script(
				'custom-carousel',
				get_stylesheet_directory_uri() . '/src/components/custom-carousel.js',
				array(),
				'1.0.1'
			);
		case 'mega-menu':
			wp_enqueue_script(
				'mega-menu',
				get_stylesheet_directory_uri() . '/src/components/mega-menu.js',
				array(),
				'1.0.0'
			);
	}
}

!is_admin() && add_action( 'wp', 'mega_menu' );
function mega_menu() {
	if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ||
	\Elementor\Plugin::$instance->preview->is_preview_mode() ) {
		return;
	} else {
		add_action( 'elementor/frontend/section/after_render', 'mega_menu_script' );
	}
}
function mega_menu_script($element) {
	$data = $element->get_data();
	$settings = $data['settings'];
	$html_tag = $settings['html_tag'];
	if ($html_tag == 'header') {
		do_action( 'wp_enqueue_scripts', 'mega-menu' );
	}
}

// CUSTOM UTILS
function explodedPath(): array {
	global $wp;
	$home = home_url( $wp );
	$urlWithPath = home_url( $wp->request );
	$path = str_replace($home, '', $urlWithPath);
	$path = trim($path, '/');
	$explodedPath = explode('/', $path);
	return $explodedPath;
}

// SHARED QUERY
function get_services_list($use_case = null) {
	$args = array(
		'post_type' => 'services',
		'order' => 'ASC'
	);
	if ($use_case) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'use_case',
				'field' => 'term_id',
				'terms' => $use_case
			)
		);
	}
	$the_query = new WP_Query( $args );
	return $the_query;
}

// SHARED RENDER
function render_services_list($use_case = null) {
	$my_current_lang = apply_filters( 'wpml_current_language', NULL );
	$no_service = 'No Service';
	if ( !strcmp($my_current_lang, 'it') ) {
		$no_service = 'Nessun Servizio';
	} else if ( !strcmp($my_current_lang, 'es') ) {
		$no_service = 'Sin servicio';
	}

	$html = '';
	$the_query = get_services_list($use_case);
	if ( $the_query->have_posts() ) {
		while ( $the_query->have_posts() ) {
			$the_query->the_post();
			$icon = get_field('service_icon');
			$icon_url = $icon['url'];
			$icon_alt = $icon['alt'];
			$top_seller = get_field('service_top_seller');
			$html .= '<div class="service-card">';
			if ( $top_seller ) {
				$html .= '<div class="service-top-seller">Top Seller</div>';
			}
			$html .= 	'<div class="service-card-title">';
			$html .= 		'<div class="service-card-icon">';
			$html .= 			'<img src="' . $icon_url . '" alt="' . $icon_alt . '"/>';
			$html .= 		'</div>';
			$html .= 		'<h3>' . get_the_title() . '</h3>';
			$html .= 	'</div>';
			$html .= 	'<div class="service-card-description">';
			$html .= 		'<p>' . get_the_excerpt() . '</p>';
			$html .= 	'</div>';
			$html .= 	'<a class="elementor-button-link elementor-button elementor-size-sm" href="' . get_permalink() . '">More Info</a>';
			$html .= '</div>';
		}
	} else {
		$html .= '<div class="no-service">' . $no_service . '</div>';
	}
	wp_reset_postdata();
	return $html;
}

// SHORTCODES
add_shortcode( 'unguess_breadcrumbs', 'unguess_breadcrumbs' );
function unguess_breadcrumbs($atts) {
	$class = '';
	if ($atts && $atts['color'] && $atts['color'] === 'green-unguess') {
		$class = ' green-unguess';
	}
	$explodedPath = explodedPath();
	$href = '';
	foreach ($explodedPath as $key => $value) {
		$href .= '../';
	}
	$html  = '<div class="unguess-breadcrumbs' . $class . '">';
	$html .= 	'<a href="' . $href . '">Home</a>';
	$temp_path = '';
	foreach ($explodedPath as $key => $value) {
		if ( $key < count($explodedPath) - 1 ) {
			$temp_path .= '/' . $value;
			$href = str_replace('../', '', $href);
			$openA = '<span>';
			$closeA = '</span>';
			$html .= '&nbsp;&nbsp;&#62;&nbsp;&nbsp;';
			if ($key !== count($explodedPath) - 1) {
				$openA = '<a href="' . $href . '">';
				$closeA = '</a>';
			}
			$postID = url_to_postid( $temp_path );
			if ($postID) {
				$html .= $openA . get_the_title($postID) . $closeA;
			} else {
				$html .= $openA . preg_replace('/[-_]/i', ' ', $value) . $closeA;
			}
		} else {
			$html .= '&nbsp;&nbsp;&#62;&nbsp;&nbsp;';
		}
	}
	$html .= '</div>';
	return $html;
}

!is_admin() && add_shortcode( 'unguess_accordion', 'unguess_accordion' );
function unguess_accordion($atts) {
	wp_enqueue_script( 
		'unguess-accordion', 
		get_stylesheet_directory_uri() . '/src/components/custom-accordion.js',
		array(),
		'1.0.0'
	);
}

add_shortcode( 'use_cases_list', 'use_cases_list' );
function use_cases_list() {
	$terms = get_terms( 
		array(
			'taxonomy' => 'use_case',
			'hide_empty' => false
		) 
	);
	$html  = '<div class="use-cases-list">';
	foreach ($terms as $term) {
		$icon = get_field('taxonomy_icon', $term);
		$icon_url = $icon['url'];
		$icon_alt = $icon['alt'];
		$excerpt = get_field('taxonomy_excerpt', $term);
		
		$html .= '<div class="use-case-card">';
		$html .= 	'<div class="use-case-card-title">';
		$html .= 		'<div class="use-case-card-icon">';
		$html .= 			'<img src="' . $icon_url . '" alt="' . $icon_alt . '"/>';
		$html .= 		'</div>';
		$html .= 		'<h3>' . $term->name . '</h3>';
		$html .= 	'</div>';
		$html .= 	'<div class="use-case-card-description">';
		$html .= 		'<p>' . $excerpt . '</p>';
		$html .= 	'</div>';
		$html .= 	'<a class="elementor-button-link elementor-button elementor-size-sm" href="/use-case/' . $term->slug . '">More Info</a>';
		$html .= '</div>';
	}
	$html .= '</div>';
	return $html;
}

add_shortcode( 'services_list', 'services_list' );
function services_list() {

	wp_enqueue_script( 
		'services-filter', 
		get_stylesheet_directory_uri() . '/src/ajax/services-filter.js',
		array(),
		'1.0.1'
	);
	wp_localize_script( 
		'services-filter', 
		'ajax',
		array(
			'url' => admin_url( 'admin-ajax.php' ),
		)
	);

	$my_current_lang = apply_filters( 'wpml_current_language', NULL );
	$all = 'All';
	if ( !strcmp($my_current_lang, 'it') ) {
		$all = 'Vedi tutto';
	} else if ( !strcmp($my_current_lang, 'es') ) {
		$all = 'Todo';
	}
	$terms = get_terms( 
		array(
			'taxonomy' => 'use_case',
			'hide_empty' => false
		) 
	);
	$html  = '<div class="services-filter">';
	$html .= 	'<ul>';
	foreach ($terms as $term) {
		$html .= 	'<li>';
		$html .= 		'<input type="radio" name="use-case" value="' . $term->term_id . '">';
		$html .= 		'<span>' . $term->name . '</span>';
		$html .= 	'</li>';
	}
	$html .= 	'</ul>';
	$html .= 	'<ul>';
	$html .= 		'<li>';
	$html .= 			'<input type="radio" name="use-case" value="0" checked>';
	$html .= 			'<span>' . $all . '</span>';
	$html .= 		'</li>';
	$html .= 	'</ul>';
	$html .= '</div>';

	$html .= '<div class="services-list">';
	$html .= render_services_list();
	$html .= '</div>';
	return $html;
}

// CALLBACK
add_action( 'wp_ajax_nopriv_services_filter_callback', 'services_filter_callback' );
add_action( 'wp_ajax_services_filter_callback', 'services_filter_callback' );
function services_filter_callback() {
	$use_case = $_POST['useCaseId'] > 0 ? $_POST['useCaseId'] : null;
    $html = render_services_list($use_case);
	wp_send_json_success( $html );
	die();
}

// TO DO??
add_shortcode( 'carousel_global_brands', 'carousel_global_brands' );
function carousel_global_brands($atts) {
	$white_image = false;
	if ($atts && $atts['color'] && $atts['color'] === 'white') {
		$white_image = true;
	}
	$html  = '<div class="elementor-element custom-element-carousel">';
	$html .=	'<div class="custom-element-container" data-transition="on">';

	do_action( 'wp_enqueue_scripts', 'custom-carousel' );
	
	$the_query = get_global_brands();
	if ( $the_query->have_posts() ) {
		while ( $the_query->have_posts() ) {
			$the_query->the_post();
			if ($white_image) {
				$image = get_field( 'global_brands_white_image' );
				$image_url = $image['url'];
			} else {
				$image_id = get_post_thumbnail_id( get_the_ID() );
				$image_url = get_the_post_thumbnail_url( get_the_ID() );
			}
			$alt_text = get_post_meta($image_id , '_wp_attachment_image_alt', true);
			$html .= 	'<div class="custom-element-carousel-slide">';
			$html .= 		'<figure><img src="' . $image_url . '" alt="' . $alt_text . '"></figure>';
			$html .= 	'</div>';
		}
	}
	wp_reset_postdata();

	$html .= 	'</div>';
	$html .= '</div>';
	return $html;
}

// CUSTOM QUERY
function get_global_brands() {
	$args = array(
		'post_type' => 'global_brands'
	);
	$the_query = new WP_Query( $args );
	return $the_query;
}
// ELEMENTOR CUSTOM QUERY
function service_related_success_stories( $query ) {
	$success_stories = get_post_meta(get_the_ID(), 'success_stories', true);
	$success_stories_ID = $success_stories[0];
	$query->set( 'post_type', 'success_stories' );
	$query->set( 'p', $success_stories_ID );
	return $query;
}
add_action( 'elementor/query/service_related_success_stories', 'service_related_success_stories' );