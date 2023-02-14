<?php
if (!ABSPATH) {
    return;
}

add_action( 'wp_enqueue_scripts', 'unguess_styles' );
function unguess_styles() {
	$theme = wp_get_theme();
	wp_enqueue_style( 'unguess-style',
        get_stylesheet_uri(),
		array(),
		'1.5.91'
	);
}

// CUSTOMIZE LOGIN PAGE
if ( !function_exists( 'login_css' ) ) {
	function login_css() {
		wp_enqueue_style(
			'unguess_login_style',
			get_stylesheet_directory_uri() . '/src/pages/login/style.css',
			array(),
			'1.0.0'
		);
	}
}
add_action( 'login_enqueue_scripts', 'login_css' );

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
				'1.0.1'
			);
	}
}

// ENABLE SVG
add_filter('upload_mimes', 'cc_mime_types');
function cc_mime_types($mimes) {
  $mimes['svg'] = 'image/svg+xml';
  return $mimes;
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
add_filter('get_the_archive_title', function ($title) {
    if (is_category()) {
        $title = single_cat_title('', false);
    } elseif (is_tag()) {
        $title = single_tag_title('', false);
    } elseif (is_author()) {
        $title = '<span class="vcard">' . get_the_author() . '</span>';
    } elseif (is_tax()) { //for custom post types
        $title = sprintf(__('%1$s'), single_term_title('', false));
    } elseif (is_post_type_archive()) {
        $title = post_type_archive_title('', false);
    }
    return $title;
});

function order_terms_by_priority($a, $b) {
	$priority_a = get_field( 'use_case_priority', $a );
	$priority_b = get_field( 'use_case_priority', $b );
	return $priority_a > $priority_b;
}

// SHARED QUERY
function get_services_list($use_case = null, $industry = null) {
	$args = array(
		'posts_per_page' => -1,
		'post_type' => 'services',
		'order' => 'ASC',
		'orderby' => array(
			'meta_value_num' => 'DESC',
			'date' => 'ASC',
		),
		'meta_key' => 'service_top_seller',
	);
	if ($use_case) {
		$args['tax_query'][] = array(
			'taxonomy' => 'use_case',
			'field' => 'term_id',
			'terms' => $use_case
		);
	}
	if ($industry) {
		$args['tax_query'][] = array(
			'taxonomy' => 'industry',
			'field' => 'term_id',
			'terms' => $industry
		);
	}
	$the_query = new WP_Query( $args );
	return $the_query;
}

function get_showcases_list($taxonomy = null, $industry = null, $use_case = null, $custom_query = null) {
	$post_per_page = $custom_query ? 3 : -1;
	
	$args = array(
		'posts_per_page' => $post_per_page,
		'post_type' => 'showcases',
		'order' => 'DESC',
	);
	$args['tax_query'] = array(
		'relation' => 'AND'
	);
	
	if ($custom_query && $custom_query == 'case study') {
		$args['tax_query'][] =
			array(
				'taxonomy' => 'showcase_resources',
				'field' => 'slug',
				'terms' => 'case-study',
			);
	}
	if ($custom_query && $custom_query == 'random') {
		$args['orderby'] = 'rand';
	}
	if ($taxonomy) {
		$args['tax_query'][] =
			array(
				'taxonomy' => 'showcase_resources',
				'field' => 'term_id',
				'terms' => $taxonomy,
			);
	}
	if ($industry) {
		$args['tax_query'][] =
			array(
				'taxonomy' => 'industry',
				'field' => 'term_id',
				'terms' => $industry,
			);
	}
	if ($use_case) {
		$args['tax_query'][] =
			array(
				'taxonomy' => 'use_case',
				'field' => 'term_id',
				'terms' => $use_case,
			);
	}
	$the_query = new WP_Query( $args );
	return $the_query;
}

function get_partners_list($type = null) {
	$args = array(
		'posts_per_page' => -1,
		'post_type' => 'partners',
		'order' => 'ASC',
	);
	if ($type == 'tech') {
		$args['tax_query'][] = array(
			'taxonomy' => 'partner_type',
			'field' => 'slug',
			'terms' => 'tech-partner'
		);
	} else {
		$args['tax_query'][] = array(
			'taxonomy' => 'partner_type',
			'field' => 'slug',
			'terms' => 'commercial-partner'
		);
	}
	$the_query = new WP_Query( $args );
	return $the_query;
}

// SHARED RENDER
function render_services_list($use_case = null, $industry = null, $remove_other_card = false) {
	$my_current_lang = apply_filters( 'wpml_current_language', NULL );
	$other_title = 'Other';
	$other_excerpt = 'Can’t find the type of study you’re looking for? Request a demo and we will show you the full potential of our platform!';
	$other_cta = 'Get in touch';
	$other_link = '/get-started/';
	$more = 'More Info';
	if ( !strcmp($my_current_lang, 'it') ) {
		$other_title = 'Altro';
		$other_excerpt = 'Non riesci a trovare quello che stai cercando? Richiedi una demo e ti mostreremo tutte le potenzialità della nostra piattaforma!';
		$other_cta = 'Mettiti in contatto';
		$other_link = '/get-started/';
		$more = 'Più Info';
	} else if ( !strcmp($my_current_lang, 'es') ) {
		$other_title = 'Otro';
		$other_excerpt = '¿No encuentras lo que buscas? ¡Solicita una demo y te mostraremos todo el potencial de nuestra plataforma!';
		$more = 'Más información';
		$other_cta = 'Ponerse en contacto';
		$other_link = '/get-started/';
	}

	$html = '';
	$the_query = get_services_list($use_case, $industry);
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
			$html .= 	'<a class="elementor-button-link elementor-button elementor-size-sm" href="' . get_permalink() . '">' . $more . '</a>';
			$html .= '</div>';
		}
	}
	if ( !$remove_other_card ) {
		$html .= '<div class="service-card other-service">';
		$html .= 	'<div class="service-card-title">';
		$html .= 		'<div class="service-card-icon">';
		$html .= 			'<img src="/wp-content/uploads/2022/12/other-icon.svg" alt="other service icon"/>';
		$html .= 		'</div>';
		$html .= 		'<h3>' . $other_title . '</h3>';
		$html .= 	'</div>';
		$html .= 	'<div class="service-card-description">';
		$html .= 		'<p>' . $other_excerpt . '</p>';
		$html .= 	'</div>';
		$html .= 	'<a class="elementor-button-link elementor-button elementor-size-sm" href="' . $other_link . '">' . $other_cta . '</a>';
		$html .= '</div>';
	}
	wp_reset_postdata();
	return $html;
}

function render_showcases_list($taxonomy = null, $industry = null, $use_case = null, $custom_query = null) {
	$my_current_lang = apply_filters( 'wpml_current_language', NULL );
	$no_service = 'No Showcase';
	if ( !strcmp($my_current_lang, 'it') ) {
		$no_service = 'Nessuno Showcase';
	} else if ( !strcmp($my_current_lang, 'es') ) {
		$no_service = 'Sin Escaparate';
	}

	$html = '';
	$the_query = get_showcases_list($taxonomy, $industry, $use_case, $custom_query);
	if ( $the_query->have_posts() ) {
		while ( $the_query->have_posts() ) {
			$the_query->the_post();
			
			$showcase_link = get_field('showcase_link');
			$term = get_the_terms( get_the_ID(), 'showcase_resources' );
			$term = array_filter( $term, function($t) {
				return !preg_match('/showcases?/i', $t->name);
			});
			$term = end($term);
			$image_id = get_post_thumbnail_id( get_the_ID() );
			$image_url = get_the_post_thumbnail_url( get_the_ID() );
			$alt_text = get_post_meta($image_id , '_wp_attachment_image_alt', true);

			$html .= '<a href="' . $showcase_link . '" target="_blank">';
			$html .= 	'<div class="showcase-card">';
			$html .= 		'<div class="showcase-image">';
			$html .= 			'<img src="' . $image_url . '" alt="' . $alt_text . '"/>';
			$html .=	 	'</div>';
			$html .= 		'<div class="showcase-term">' . $term->name . '</div>';
			$html .= 		'<div class="showcase-title">';
			$html .= 			'<h3>' . get_the_title() . '</h3>';
			$html .= 		'</div>';
			$html .= 		'<div class="showcase-excerpt">';
			$html .= 			'<p>' . get_the_excerpt() . '</p>';
			$html .= 		'</div>';
			$html .= 	'</div>';
			$html .= '</a>';
		}
	} else {
		$html .= '<div class="no-showcase">' . $no_service . '</div>';
	}
	return $html;
}

function render_partners_list($type = null) {
	$my_current_lang = apply_filters( 'wpml_current_language', NULL );
	$card_title = 'Become the next partner!';
	$card_description = 'Want to know more about the exclusive benefits reserved to our partners? Book a quick discovery call with one of our representatives.';
	$button_text = 'CONTACT US';
	$button_link = 'mailto:laura.villa@unguess.io?subject=I want to become your partner';
	if ( !strcmp($my_current_lang, 'it') ) {
		$card_title = 'Diventa il prossimo partner!';
		$card_description = 'Vuoi saperne di più sui vantaggi esclusivi riservati ai nostri partner? Prenota una rapida chiamata di scoperta con uno dei nostri rappresentanti.';
		$button_text = 'CONTATTACI';
		$button_link = 'mailto:laura.villa@unguess.io?subject=Voglio diventare vostro partner';
	} else if ( !strcmp($my_current_lang, 'es') ) {
		$card_title = '¡Conviértete en el próximo socio!';
		$card_description = '¿Quiere saber más sobre los beneficios exclusivos reservados a nuestros socios? Reserve una llamada de descubrimiento rápido con uno de nuestros representantes.';
		$button_text = 'CONTACTO';
		$button_link = 'mailto:laura.villa@unguess.io?subject=quiero ser tu pareja';
	}
	
	$html = '';
	$the_query = get_partners_list($type);
	if ( $the_query->have_posts() ) {
		while ( $the_query->have_posts() ) {
			$the_query->the_post();
			$image_id = get_post_thumbnail_id( get_the_ID() );
			$image_url = get_the_post_thumbnail_url( get_the_ID() );
			$partner_link = get_field('partner_link');
			$alt_text = get_post_meta($image_id , '_wp_attachment_image_alt', true);
			$html .= '<a href="' . $partner_link . '" target="_blank" rel=”nofollow”>';
			$html .= 	'<div class="partner-card">';
			$html .= 		'<div class="partner-card-image">';
			$html .= 			'<img src="' . $image_url . '" alt="' . $alt_text . '">';
			$html .= 		'</div>';
			$html .= 		'<div class="partner-card-title">';
			$html .= 			'<h4>' . get_the_title() . '</h4>';
			$html .= 		'</div>';
			$html .= 		'<div class="partner-card-description">';
			$html .= 			'<p>' . get_the_excerpt() . '</p>';
			$html .= 		'</div>';
			$html .= 	'</div>';
			$html .= '</a>';
		}
	}
	
	/*if ( $type == 'commercial' ) {
		$html .= '<div class="partner-card contact-card">';
		$html .= 	'<div class="partner-card-title">';
		$html .= 		'<h3>' . $card_title . '</h3>';
		$html .= 	'</div>';
		$html .= 	'<div class="partner-card-description">';
		$html .= 		'<p>' . $card_description . '</p>';
		$html .= 	'</div>';
		$html .= 	'<a class="elementor-button-link elementor-button elementor-size-sm" href="' . $button_link . '">' . $button_text . '</a>';
		$html .= '</div>';
	}*/
	
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
		if ( !is_post_type_archive() && !is_tax()
			|| ( is_post_type_archive() || is_tax() ) && $key < count($explodedPath) - 1 ) {
			$temp_path .= '/' . $value;
			$href = preg_replace('/..\//', '', $href, 1);
			$openA = '<span>';
			$closeA = '</span>';
			$html .= '&nbsp;&nbsp;<i aria-hidden="true" class="fas fa-chevron-right"></i>&nbsp;&nbsp;';
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
		} /*else {
			$html .= '&nbsp;&nbsp;<i aria-hidden="true" class="fas fa-chevron-right"></i>&nbsp;&nbsp;';
		}*/
		if ( get_post_type() == 'services' && $key == count($explodedPath) - 2 ) {
			break;
		}
	}
	$html .= '</div>';
	return $html;
}

add_shortcode( 'service_title', 'service_title' );
function service_title($atts) {
	if ( is_post_type_archive() ) {
		$my_current_lang = apply_filters( 'wpml_current_language', NULL );
		$title = 'What do you want to UNGUESS?';
		if ( !strcmp($my_current_lang, 'it') ) {
			$title = 'Cosa vuoi scoprire con UNGUESS?';
		} else if ( !strcmp($my_current_lang, 'es') ) {
			$title = '¿qué te gustaría averiguar con UNGUESS?';
		}
	} else {
		$title = get_the_archive_title();
	}
	
	$html  = '';
	$html .= '<div class="elementor-element elementor-element-ee33574 elementor-widget elementor-widget-theme-archive-title elementor-page-title elementor-widget-heading" data-id="ee33574" data-element_type="widget" data-widget_type="theme-archive-title.default">';
	$html .= 	'<div class="elementor-widget-container">';
	$html .= 		'<h1 class="elementor-heading-title elementor-size-default">' . $title . '</h1>';
	$html .= 	'</div>';
	$html .= '</div>';
	return $html;
}

add_shortcode( 'service_excerpt', 'service_excerpt' );
function service_excerpt($atts) {
	if ( is_post_type_archive() ) {
		return;
	}
	
	$html  = '';
	$html .= '<div class="elementor-element elementor-element-73d23c33 elementor-widget elementor-widget-text-editor" data-id="73d23c33" data-element_type="widget" data-widget_type="text-editor.default">';
	$html .= 	'<div class="elementor-widget-container">';
	$html .= 		get_the_excerpt();
	$html .= 	'</div>';
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
	$my_current_lang = apply_filters( 'wpml_current_language', NULL );
	$more = 'More Info';
	$lang_slug = '';
	if ( !strcmp($my_current_lang, 'it') ) {
		$more = 'Più Info';
		$lang_slug = '/it';
	} else if ( !strcmp($my_current_lang, 'es') ) {
		$more = 'Más información';
		$lang_slug = '/es';
	}
	
	$terms = get_terms( 
		array(
			'taxonomy' => 'use_case',
			'hide_empty' => false
		) 
	);
	
	usort( $terms, 'order_terms_by_priority' );

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
		$html .= 	'<a class="elementor-button-link elementor-button elementor-size-sm" href="' . $lang_slug . '/use-case/' . $term->slug . '">' . $more .' </a>';
		$html .= '</div>';
	}
	$html .= '</div>';
	return $html;
}

add_shortcode( 'use_cases_nav', 'use_cases_nav' );
function use_cases_nav() {
	$terms = get_terms( 
		array(
			'taxonomy' => 'use_case',
			'hide_empty' => false
		) 
	);
	
	$active_use_case = get_queried_object()->term_id;
	
	$my_current_lang = apply_filters( 'wpml_current_language', NULL );
	$more = 'More Info';
	$lang_slug = '/services';
	if ( !strcmp($my_current_lang, 'it') ) {
		$more = 'Più Info';
		$lang_slug = '/it/servizi';
	} else if ( !strcmp($my_current_lang, 'es') ) {
		$more = 'Más información';
		$lang_slug = '/es/servicios';
	}

	$html  = '<div class="use-cases-nav">';
	foreach ($terms as $term) {
		$icon = get_field('taxonomy_icon', $term);
		$icon_url = $icon['url'];
		$icon_alt = $icon['alt'];
		$excerpt = get_field('taxonomy_excerpt', $term);
		$class = $active_use_case == $term->term_id ? ' active' : ''; 
		$html .= '<div class="use-case-card' . $class . '">';
		$html .= 	'<div class="use-case-card-title">';
		$html .= 		'<div class="use-case-card-icon">';
		$html .= 			'<img src="' . $icon_url . '" alt="' . $icon_alt . '"/>';
		$html .= 		'</div>';
		$html .= 		'<h3>' . $term->name . '</h3>';
		$html .= 	'</div>';
		$html .= 	'<div class="use-case-card-description">';
		$html .= 		'<p>' . $excerpt . '</p>';
		$html .= 	'</div>';
		if ( $active_use_case != $term->term_id ) {
			$html .= 	'<a class="elementor-button-link elementor-button elementor-size-sm" href="' . $lang_slug . '/use-case/' . $term->slug . '">' . $more . '</a>';
		}
		$html .= '</div>';
	}
	$html .= '</div>';
	return $html;
}

add_shortcode( 'list_post_carousel', 'list_post_carousel' );
function list_post_carousel($atts) {
	if (!$atts || !$atts['type']) {
		return;
	}
	
	wp_enqueue_script( 
		'list-post-carousel', 
		get_stylesheet_directory_uri() . '/src/components/list-post-carousel.js',
		array(),
		'1.0.3'
	);
	wp_localize_script( 
		'services-filter', 
		'ajax',
		array()
	);
	
	$type = $atts['type'];
	$active_use_case = get_queried_object()->term_id;
	$industry = null;
	$remove_other_card = true;
	$html = '';
	$showcase_class = '';
	if ( $type == 'showcase') {
		$showcase_class = ' post-carousel-showcase';
		$my_current_lang = apply_filters( 'wpml_current_language', NULL );
		$pre_archive_title = 'How UNGUESS has brought value in ';
		if ( !strcmp($my_current_lang, 'it') ) {
			$pre_archive_title = 'Come UNGUESS ha apportato valore in ';
		} else if ( !strcmp($my_current_lang, 'es') ) {
			$pre_archive_title = 'Cómo UNGUESS ha aportado valor en ';
		}
		$archive_title = get_the_archive_title();
		$html .= '<div class="elementor-element elementor-element-156edf9 elementor-widget elementor-widget-heading" data-id="156edf9" data-element_type="widget" data-widget_type="heading.default">';
		$html .= 	'<div class="elementor-widget-container">';
		$html .= 		'<h3 class="elementor-heading-title elementor-size-default list-post-carousel-title">' . $pre_archive_title . $archive_title . '</h3>';
		$html .= 	'</div>';
		$html .= '</div>';
	}
	
	$html .= '<div class="list-post-carousel' . $showcase_class . '">';
	$html .= 	'<a class="list-post-scroll-trigger off" data-move="prev"><i aria-hidden="true" class="fas fa-chevron-left"></i></a>';
	$html .= 	'<div class="list-post-carousel-content">';
	if ( $type == 'service') {
		$html .= render_services_list($active_use_case, $industry, $remove_other_card);
	} else {
		$temp_html .= render_showcases_list(null, $industry, $active_use_case);
		if ( preg_match('/no-showcase/i', $temp_html ) ) {
			return;
		} 
		$html .= $temp_html;
	}
	$html .= 	'</div>';
	$html .= 	'<a class="list-post-scroll-trigger off" data-move="next"><i aria-hidden="true" class="fas fa-chevron-right"></i></a>';
	$html .= '</div>';
	return $html;
}

add_shortcode( 'services_list', 'services_list' );
function services_list() {

	wp_enqueue_script( 
		'services-filter', 
		get_stylesheet_directory_uri() . '/src/ajax/services-filter.js',
		array(),
		'1.0.2'
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
	$active_tax = get_queried_object();
	$use_case = null;
	$industry = null;
	if ($active_tax->taxonomy == 'use_case') {
		$use_case = $active_tax->term_id;
	}
	if ($active_tax->taxonomy == 'industry') {
		$industry = $active_tax->term_id;
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
		$checked = $term->term_id == $use_case ? ' checked' : '';
		$html .= 	'<li>';
		$html .= 		'<input type="radio" name="use-case" data-industry="' . $industry . '" value="' . $term->term_id . '"' . $checked . '>';
		$html .= 		'<span>' . $term->name . '</span>';
		$html .= 	'</li>';
	}
	$html .= 	'</ul>';
	$html .= 	'<ul>';
	$html .= 		'<li>';
	$checked_all = !$use_case ? ' checked' : '';
	$html .= 			'<input type="radio" name="use-case" data-industry="' . $industry . '" value="0"' . $checked_all . '>';
	$html .= 			'<span>' . $all . '</span>';
	$html .= 		'</li>';
	$html .= 	'</ul>';
	$html .= '</div>';
	
	$html .= '<div class="services-list">';
	$html .= render_services_list($use_case, $industry);
	$html .= '</div>';
	return $html;
}

add_shortcode( 'showcases_list', 'showcases_list' );
function showcases_list() {
	wp_enqueue_script( 
		'showcases-filter', 
		get_stylesheet_directory_uri() . '/src/ajax/showcases-filter.js',
		array(),
		'1.0.2'
	);
	wp_localize_script( 
		'showcases-filter', 
		'ajax',
		array(
			'url' => admin_url( 'admin-ajax.php' ),
		)
	);
	$active_term_id = get_queried_object()->term_id;
	$html  = '<div class="showcases-list">';
	$html .= render_showcases_list($active_term_id);
	$html .= '</div>';
	return $html;
}

add_shortcode( 'filter_showcases', 'filter_showcases');
function filter_showcases($atts) {
	if (!$atts || !$atts['filter']) {
		return;
	}
	
	$type = $atts['type'] ? $atts['type'] : 'checkbox';
	
	$showcases = get_terms( 
		array(
			'taxonomy' => $atts['filter'],
			'hide_empty' => false
		) 
	);
	
	$active_term_id = get_queried_object()->term_id;
			
	$html = '<div class="filter-showcases">';
	$html .= 	'<ul>';
	foreach ( $showcases as $showcase ) {
		$html .= 	'<li>';
		if ($type == 'checkbox') {
			$html .=		'<div class="filter-showcase-check">';
			$html .=			'<input type="checkbox" name="showcase" data-filter="' . $atts['filter'] . '" data-taxonomy="' . $active_term_id . '" value="' . $showcase->term_id . '">';
			$html .= 			'<span></span>';
			$html .= 		'</div>';
			$html .= 		'<div class="filter-showcase-name">' . $showcase->name . '</div>';
		} else {
			$html .=		'<div class="filter-showcase-radio">';
			$html .=			'<input type="radio" name="showcase" data-taxonomy="' . $atts['filter'] . '" value="' . $showcase->term_id . '">';
			$html .= 		'<div class="filter-showcase-name">' . $showcase->name . '</div>';
			$html .=		'</div>';
		}
		$html .= 	'</li>';
	}
	$html .= 	'</ul>';
	$html .= '</div>';
	return $html;
}

add_shortcode( 'showcases_preview', 'showcases_preview' );
function showcases_preview($atts) {
	if (!$atts || !$atts['query']) {
		return;
	}
	$html  = '<div class="showcases-preview">';
	$html .= render_showcases_list(null, null, null, $atts['query']);
	$html .= '</div>';
	return $html;
}

add_shortcode( 'partners_list', 'partners_list');
function partners_list($atts) {
	if (!$atts || !$atts['type']) {
		return;
	}
	
	$type = $atts['type'];
		
	$html  = '<div class="list-partners">';
	$html .= render_partners_list($type);
	$html .= '</div>';
	return $html;
}

add_shortcode( 'carousel_testimonials', 'carousel_testimonials');
function carousel_testimonials($atts) {
	$success_stories = get_post_meta(get_the_ID(), 'success_stories', true);
	if ( !$success_stories ) {
		return '[elementor-template id="2537"]';
	}
}

add_shortcode( 'service_success_story', 'service_success_story');
function service_success_story($atts) {
	if (!$atts || !$atts['type']) {
		return;
	}
	
	$type = $atts['type'];
	$html = '';
	if ($type == 'statement') {
		$statement = get_field('success_stories_testimonial_content', get_the_ID());
		if ( $statement ) {
			$html .= '<div class="elementor-element elementor-element-6bc573f elementor-widget elementor-widget-text-editor success-stories-statement-text" data-id="6bc573f" data-element_type="widget" data-widget_type="text-editor.default">';
			$html .= 	'<div class="elementor-widget-container">"' . $statement . '"</div>';
			$html .= '</div>';
		}
	} else {
		$image_id = get_post_thumbnail_id( get_the_ID() );
		if ( $image_id ) {
			$image_url = get_the_post_thumbnail_url( get_the_ID() );
			$image_srcset = wp_get_attachment_image_srcset( $image_id, array( 225, 150 ) );

			$testimonial_full_name = get_field( 'success_stories_full_name', get_the_ID() );
			$testimonial_role = get_field( 'success_stories_testimonial_role', get_the_ID() );
			$html .= '<section class="elementor-section elementor-inner-section elementor-element elementor-element-f93f656 elementor-section-content-middle elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-id="f93f656" data-element_type="section">';
			$html .= 	'<div class="elementor-container elementor-column-gap-default">';
			$html .= 		'<div class="elementor-column elementor-col-50 elementor-inner-column elementor-element elementor-element-40c9b16" data-id="40c9b16" data-element_type="column">';
			$html .= 			'<div class="elementor-widget-wrap elementor-element-populated">';
			$html .= 				'<div class="elementor-element elementor-element-19049ae elementor-widget elementor-widget-theme-post-featured-image elementor-widget-image" data-id="19049ae" data-element_type="widget" data-widget_type="theme-post-featured-image.default">';
			$html .= 					'<div class="elementor-widget-container">';
			$html .= 						'<img width="225" height="225" src="' . $image_url . '" class="attachment-large size-large" alt="francesco fiaschi" loading="lazy" srcset="' . $image_srcset . '" sizes="(max-width: 225px) 100vw, 225px">';
			$html .= 					'</div>';
			$html .= 				'</div>';
			$html .= 			'</div>';
			$html .= 		'</div>';
			$html .= 		'<div class="elementor-column elementor-col-50 elementor-inner-column elementor-element elementor-element-6fd15f0" data-id="6fd15f0" data-element_type="column">';
			$html .= 			'<div class="elementor-widget-wrap elementor-element-populated">';
			$html .= 				'<div class="elementor-element elementor-element-51d0c7e elementor-widget elementor-widget-heading" data-id="51d0c7e" data-element_type="widget" data-widget_type="heading.default">';
			$html .= 					'<div class="elementor-widget-container">';
			$html .= 						'<h4 class="elementor-heading-title elementor-size-default">' . $testimonial_full_name . '</h4>';
			$html .= 					'</div>';
			$html .= 				'</div>';
			$html .= 				'<div class="elementor-element elementor-element-2b3082b elementor-widget elementor-widget-text-editor" data-id="2b3082b" data-element_type="widget" data-widget_type="text-editor.default">';
			$html .= 					'<div class="elementor-widget-container">' . $testimonial_role . '</div>';
			$html .= 				'</div>';
			$html .= 			'</div>';
			$html .= 		'</div>';
			$html .= 	'</div>';
			$html .= '</section>';
		}
	}
	return $html;
}

add_shortcode( 'whitejar', 'whitejar');
function whitejar($atts) {
	if (!$atts || !$atts['type']) {
		return;
	}
	
	$html = '';
	$type = $atts['type'];
	if ( is_user_logged_in() ) {

		$active_use_case = get_queried_object()->slug;
		if ( preg_match('/cybersecurity|ciberseguridad/i', $active_use_case) ) {

			if ($type == 'logo') {
				$html .= '<div class="whitejar-logo">';
				$html .= 	'<img width="739" height="272" src="/wp-content/uploads/2023/01/logo-bianco.png" class="attachment-full size-full" alt="whitejar logo" loading="lazy" srcset="/wp-content/uploads/2023/01/logo-bianco.png 739w, /wp-content/uploads/2023/01/logo-bianco-300x110.png 300w" sizes="(max-width: 739px) 100vw, 739px">';
				$html .= '<div/>';
			} else {
				$html .= '<div class="elementor-element elementor-element-5b5f82c elementor-widget__width-auto elementor-widget elementor-widget-button" data-id="5b5f82c" data-element_type="widget" data-widget_type="button.default">';
				$html .= 	'<div class="elementor-widget-container">';
				$html .= 		'<div class="elementor-button-wrapper">';
				$html .= 			'<a href="/get-started/" class="elementor-button-link elementor-button elementor-size-sm" role="button">';
				$html .= 				'<span class="elementor-button-content-wrapper">';
				$html .= 					'<span class="elementor-button-text">BOOK A DEMO</span>';
				$html .= 				'</span>';
				$html .= 			'</a>';
				$html .= 		'</div>';
				$html .= 	'</div>';
				$html .=  '</div>';
			}

		}

	}
	return $html;
}

// CALLBACK
add_action( 'wp_ajax_nopriv_services_filter_callback', 'services_filter_callback' );
add_action( 'wp_ajax_services_filter_callback', 'services_filter_callback' );
function services_filter_callback() {
	$use_case = $_POST['useCaseId'] > 0 ? $_POST['useCaseId'] : null;
	$industry = $_POST['industryId'] > 0 ? $_POST['industryId'] : null;
    $html = render_services_list($use_case, $industry);
	wp_send_json_success( $html );
	die();
}

add_action( 'wp_ajax_nopriv_showcases_filter_callback', 'showcases_filter_callback' );
add_action( 'wp_ajax_showcases_filter_callback', 'showcases_filter_callback' );
function showcases_filter_callback() {
	$taxonomy = $_POST['taxonomy'] > 0 ? $_POST['taxonomy'] : null;
	$industry = $_POST['industryId'];
	$use_case = $_POST['useCaseId'];
    $html = render_showcases_list($taxonomy, $industry, $use_case);
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
	if ( !$success_stories ) {
		$query->set( 'post__in', array(0) );
	} else {
		$success_stories_ID = $success_stories[0];
		$query->set( 'post_type', 'success_stories' );
		$query->set( 'p', $success_stories_ID );
	}
	return $query;
}
add_action( 'elementor/query/service_related_success_stories', 'service_related_success_stories' );

// DISABLE ALL COMMENTS (ANY POST_TYPE)
function disable_comments() {
   $post_types = get_post_types();
   foreach ($post_types as $post_type) {
      if ( post_type_supports($post_type, 'comments') ) {
         remove_post_type_support($post_type, 'comments');
         remove_post_type_support($post_type, 'trackbacks');
      }
   }
}
add_action('admin_init', 'disable_comments');

// DISABLE ANONYMOUS REST API
function disable_anonymous_api( $result ) {
	if ( $result === true || is_wp_error( $result ) ) {
		return $result;
	}
	if ( !is_user_logged_in() ) {
		return new WP_Error(
			'rest_not_logged_in',
			__( 'You are not currently logged in.' ),
			array( 'status' => 401 )
		);
	}
	return $result;
};
add_filter( 'rest_authentication_errors', 'disable_anonymous_api' );