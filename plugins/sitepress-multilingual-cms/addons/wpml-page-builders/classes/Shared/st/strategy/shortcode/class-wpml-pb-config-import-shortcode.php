<?php

use WPML\Convert\Ids;
use WPML\FP\Obj;
use WPML\PB\ConvertIds\Helper as ConvertIdsHelper;

class WPML_PB_Config_Import_Shortcode {

	const PB_SHORTCODE_SETTING       = 'pb_shortcode';
	const PB_MEDIA_SHORTCODE_SETTING = 'wpml_pb_media_shortcode';
	const PB_IDS_SHORTCODE_SETTING   = 'wpml_pb_ids_shortcode';

	const TYPE_POST_IDS     = 'post-ids';
	const TYPE_TAXONOMY_IDS = 'taxonomy-ids';

	/** @var  WPML_ST_Settings $st_settings */
	private $st_settings;

	public function __construct( WPML_ST_Settings $st_settings ) {
		$this->st_settings = $st_settings;
	}

	public function add_hooks() {
		add_filter( 'wpml_config_array', array( $this, 'wpml_config_filter' ) );
	}

	public function wpml_config_filter( $config_data ) {
		$this->update_shortcodes_config( $config_data );
		$this->update_ids_shortcodes_config( $config_data );
		$this->update_media_shortcodes_config( $config_data );

		return $config_data;
	}

	/** @param array $config_data */
	private function update_shortcodes_config( $config_data ) {
		$old_shortcode_data = $this->get_settings();

		$shortcode_data = array();
		if ( isset ( $config_data['wpml-config']['shortcodes']['shortcode'] ) ) {
			foreach ( $config_data['wpml-config']['shortcodes']['shortcode'] as $data ) {
				$ignore_content = isset( $data['tag']['attr']['ignore-content'] )
				                  && $data['tag']['attr']['ignore-content'];

				$attributes = array();
				if ( isset( $data['attributes']['attribute'] ) ) {

					$data['attributes']['attribute'] = $this->convert_single_attribute_to_multiple_format( $data['attributes']['attribute'] );

					foreach ( $data['attributes']['attribute'] as $attribute ) {

						if ( ! $this->is_string_attribute( $attribute ) ) {
							continue;
						}

						if ( ! empty( $attribute['value'] ) ) {
							$attribute_encoding = isset( $attribute['attr']['encoding'] ) ? $attribute['attr']['encoding'] : '';
							$attribute_type     = isset( $attribute['attr']['type'] ) ? $attribute['attr']['type'] : '';
							$attribute_label    = isset( $attribute['attr']['label'] ) ? $attribute['attr']['label'] : '';
							$attributes[]       = [
								'value'    => $attribute['value'],
								'encoding' => $attribute_encoding,
								'type'     => $attribute_type,
								'label'    => $attribute_label,
							];
						}
					}
				}

				if ( ! ( $ignore_content && empty( $attributes ) ) ) {
					$shortcode_data[] = [
						'tag'        => [
							'value'              => $data['tag']['value'],
							'encoding'           => isset( $data['tag']['attr']['encoding'] ) ? $data['tag']['attr']['encoding'] : '',
							'encoding-condition' => isset( $data['tag']['attr']['encoding-condition'] ) ? $data['tag']['attr']['encoding-condition'] : '',
							'type'               => isset( $data['tag']['attr']['type'] ) ? $data['tag']['attr']['type'] : '',
							'raw-html'           => isset( $data['tag']['attr']['raw-html'] ) ? $data['tag']['attr']['raw-html'] : '',
							'ignore-content'     => $ignore_content,
							'label'              => isset( $data['tag']['attr']['label'] ) ? $data['tag']['attr']['label'] : '',
						],
						'attributes' => $attributes,
					];
				}
			}
		}

		if ( $shortcode_data != $old_shortcode_data ) {
			$this->st_settings->update_setting( self::PB_SHORTCODE_SETTING, $shortcode_data, true );
		}
	}

	/** @param array $config_data */
	private function update_media_shortcodes_config( $config_data ) {
		$old_shortcodes_data = $this->get_media_settings();
		$shortcodes_data     = array();

		if ( isset ( $config_data['wpml-config']['shortcodes']['shortcode'] ) ) {

			foreach ( $config_data['wpml-config']['shortcodes']['shortcode'] as $data ) {
				$shortcode_data = array();

				if ( isset( $data['attributes']['attribute'] ) ) {
					$attributes = array();

					$data['attributes']['attribute'] = $this->convert_single_attribute_to_multiple_format( $data['attributes']['attribute'] );

					foreach ( $data['attributes']['attribute'] as $attribute ) {

						if ( ! $this->is_media_attribute( $attribute ) ) {
							continue;
						}

						if ( ! empty( $attribute['value'] ) ) {
							$attribute_type = isset( $attribute['attr']['type'] ) ? $attribute['attr']['type'] : '';
							$attributes[ $attribute['value'] ] = array( 'type' => $attribute_type );
						}
					}

					if ( $attributes ) {
						$shortcode_data['attributes'] = $attributes;
					}
				}

				if ( isset( $data['tag']['attr']['type'] )
				     && $data['tag']['attr']['type'] === WPML_Page_Builders_Media_Shortcodes::TYPE_URL
				) {
					$shortcode_data['content'] = array( 'type' => WPML_Page_Builders_Media_Shortcodes::TYPE_URL );
				}

				if ( $shortcode_data ) {
					$shortcode_data['tag'] = array( 'name' => $data['tag']['value'] );
					$shortcodes_data[]     = $shortcode_data;
				}
			}
		}

		if ( $shortcodes_data != $old_shortcodes_data ) {
			update_option( self::PB_MEDIA_SHORTCODE_SETTING, $shortcodes_data, true );
		}
	}

	/** @param array $config_data */
	private function update_ids_shortcodes_config( $config_data ) {
		$old_shortcodes_data = $this->get_id_settings();
		$shortcodes_data     = [];

		if ( isset ( $config_data['wpml-config']['shortcodes']['shortcode'] ) ) {

			foreach ( $config_data['wpml-config']['shortcodes']['shortcode'] as $data ) {
				$attributes = [];

				if ( isset( $data['attributes']['attribute'] ) ) {
					$data['attributes']['attribute'] = $this->convert_single_attribute_to_multiple_format( $data['attributes']['attribute'] );

					foreach ( $data['attributes']['attribute'] as $attribute ) {

						if ( ! $this->is_id_attribute( $attribute ) ) {
							continue;
						}

						if ( ! empty( $attribute['value'] ) ) {
							$attributes[ $attribute['value'] ] = ConvertIdsHelper::selectElementType(
								Obj::path( [ 'attr','sub-type' ], $attribute ),
								Obj::path( [ 'attr','type' ], $attribute )
							);
						}
					}
				}

				if ( $attributes ) {
					$tag_name                     = $data['tag']['value'];
					$shortcodes_data[ $tag_name ] = $attributes;
				}
			}
		}

		if ( $shortcodes_data != $old_shortcodes_data ) {
			update_option( self::PB_IDS_SHORTCODE_SETTING, $shortcodes_data, true );
		}
	}

	/**
	 * @param array $attribute
	 *
	 * @return bool
	 */
	private function is_string_attribute( array $attribute ) {
		return ! $this->is_id_attribute( $attribute )
		       && ! $this->is_media_attribute( $attribute );
	}

	/**
	 * @param array $attribute
	 *
	 * @return bool
	 */
	private function is_id_attribute( array $attribute ) {
		return ConvertIdsHelper::isValidType( Obj::path( [ 'attr', 'type' ], $attribute ) );
	}

	private function is_media_attribute( array $attribute ) {
		$media_attribute_types = array(
			WPML_Page_Builders_Media_Shortcodes::TYPE_URL,
			WPML_Page_Builders_Media_Shortcodes::TYPE_IDS,
		);

		return isset( $attribute['attr']['type'] )
		       && in_array( $attribute['attr']['type'], $media_attribute_types, true );
	}

	private function convert_single_attribute_to_multiple_format( array $attribute ) {
		if ( ! is_numeric( key( $attribute ) ) ) {
			$attribute = array( $attribute );
		}

		return $attribute;
	}

	public function get_settings() {
		return $this->st_settings->get_setting( self::PB_SHORTCODE_SETTING );
	}

	public function get_media_settings() {
		return get_option( self::PB_MEDIA_SHORTCODE_SETTING, array() );
	}

	/**
	 * @return array
	 */
	public function get_id_settings() {
		return get_option( self::PB_IDS_SHORTCODE_SETTING, [] );
	}

	public function has_settings() {
		$settings = $this->get_settings();

		return ! empty( $settings );
	}
}
