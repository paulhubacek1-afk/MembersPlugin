<?php
/**
 * Members Plugin – configurable fields on custom account tabs.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

final class MP_Account_Tabs {

	const OPTION = 'mp_account_tabs';
	const PAGE   = 'mp-account-tabs';

	/**
	 * Register account hooks and the administrator page.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 1002 );
		add_action( 'admin_post_mp_save_account_tabs', array( __CLASS__, 'save_settings' ) );
		add_filter( 'um_account_page_default_tabs_hook', array( __CLASS__, 'add_tabs' ), 20, 1 );
		add_action( 'um_submit_account_errors_hook', array( __CLASS__, 'validate_submitted_fields' ), 20, 1 );
		// Run before UM's standard priority-10 callback redirects after a successful save.
		add_action( 'um_submit_account_details', array( __CLASS__, 'save_submitted_fields' ), 9, 1 );

		foreach ( self::get_tabs() as $tab ) {
			if ( empty( $tab['id'] ) || empty( $tab['fields'] ) ) {
				continue;
			}
			add_filter(
				'um_account_content_hook_' . $tab['id'],
				static function ( $output, $args ) use ( $tab ) {
					return MP_Account_Tabs::render_tab_fields( $tab, $args );
				},
				20,
				2
			);
		}
	}

	/**
	 * Register the settings screen under the existing UM menu.
	 */
	public static function menu() {
		add_submenu_page(
			'ultimatemember',
			__( 'Konto-Tabs', 'members-plugin' ),
			__( 'Konto-Tabs', 'members-plugin' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_admin' )
		);
	}

	/**
	 * Read saved tab definitions.
	 *
	 * @return array
	 */
	public static function get_tabs() {
		$tabs = get_option( self::OPTION, array() );
		if ( ! is_array( $tabs ) ) {
			return array();
		}

		$clean = array();
		foreach ( $tabs as $tab ) {
			if ( ! is_array( $tab ) || empty( $tab['id'] ) || empty( $tab['title'] ) || empty( $tab['fields'] ) || ! is_array( $tab['fields'] ) ) {
				continue;
			}
			$tab['id']     = sanitize_key( $tab['id'] );
			$tab['slug']   = isset( $tab['slug'] ) ? sanitize_key( $tab['slug'] ) : '';
			$tab['title']  = sanitize_text_field( $tab['title'] );
			$tab['icon']   = isset( $tab['icon'] ) ? self::sanitize_icon( $tab['icon'] ) : 'um-faicon-cog';
			$tab['button'] = isset( $tab['button'] ) ? sanitize_text_field( $tab['button'] ) : __( 'Speichern', 'members-plugin' );
			$fields        = array();

			foreach ( $tab['fields'] as $field ) {
				if ( ! is_array( $field ) || empty( $field['key'] ) || empty( $field['label'] ) ) {
					continue;
				}
				$field['key']         = sanitize_key( $field['key'] );
				$field['label']       = sanitize_text_field( $field['label'] );
				$field['type']        = self::field_type( isset( $field['type'] ) ? $field['type'] : 'text' );
				$field['required']    = ! empty( $field['required'] );
				$field['placeholder'] = isset( $field['placeholder'] ) ? sanitize_text_field( $field['placeholder'] ) : '';
				$field['default']     = isset( $field['default'] ) ? sanitize_text_field( $field['default'] ) : '';
				$field['description'] = isset( $field['description'] ) ? sanitize_text_field( $field['description'] ) : '';
				$field['options']     = isset( $field['options'] ) ? self::sanitize_options_text( $field['options'] ) : '';
				$fields[]             = $field;
			}

			if ( empty( $fields ) ) {
				continue;
			}
			$tab['fields'] = $fields;
			$clean[]       = $tab;
		}

		return $clean;
	}

	/**
	 * Add configured tabs to UM's account navigation.
	 *
	 * @param array $tabs Default tabs grouped by priority.
	 * @return array
	 */
	public static function add_tabs( $tabs ) {
		if ( ! is_array( $tabs ) ) {
			$tabs = array();
		}
		foreach ( self::get_tabs() as $index => $tab ) {
			$priority = 600 + ( (int) $index * 10 );
			$tabs[ $priority ][ $tab['id'] ] = array(
				'icon'         => $tab['icon'],
				'title'        => $tab['title'],
				'submit_title' => $tab['button'],
			);
		}
		return $tabs;
	}

	/**
	 * Render a tab using UM's field wrapper classes.
	 *
	 * @param array $tab Tab settings.
	 * @param array $args Account shortcode args.
	 * @return string
	 */
	public static function render_tab_fields( $tab, $args = array() ) {
		if ( ! is_user_logged_in() || empty( $tab['fields'] ) ) {
			return '';
		}

		$user_id = get_current_user_id();
		$html    = '';
		foreach ( $tab['fields'] as $field ) {
			$key       = $field['key'];
			$meta_key  = self::meta_key( $tab, $field );
			$value     = get_user_meta( $user_id, $meta_key, true );
			if ( ( '' === $value || null === $value ) && '' !== $field['default'] ) {
				$value = $field['default'];
			}
			$value     = is_scalar( $value ) ? (string) $value : '';
			$field_id  = 'mp-account-' . $tab['slug'] . '-' . $key;
			$field_cls = 'um-field um-field-' . $field['type'] . ' um-field-' . $key;
			$required  = $field['required'] ? ' required="required" aria-required="true"' : '';
			$label     = '<div class="um-field-label"><label for="' . esc_attr( $field_id ) . '">' . esc_html( $field['label'] ) . '</label>' .
				( $field['required'] ? ' <span class="um-req" title="' . esc_attr__( 'Erforderlich', 'members-plugin' ) . '">*</span>' : '' ) .
				'<div class="um-clear"></div></div>';

			$html .= '<div class="' . esc_attr( $field_cls ) . '" data-mp-account-field="' . esc_attr( $key ) . '">';

			if ( 'checkbox' === $field['type'] ) {
				$checked = in_array( $value, array( '1', 'yes', 'true', 'on' ), true );
				$html .= '<div class="um-field-area"><label class="um-field-checkbox' . ( $checked ? ' active' : '' ) . '">' .
					'<input type="hidden" name="mp_account_fields[' . esc_attr( $tab['id'] ) . '][' . esc_attr( $key ) . ']" value="0" />' .
					'<input type="checkbox" id="' . esc_attr( $field_id ) . '" name="mp_account_fields[' . esc_attr( $tab['id'] ) . '][' . esc_attr( $key ) . ']" value="1"' . checked( $checked, true, false ) . $required . ' />' .
					'<span class="um-field-checkbox-option">' . esc_html( $field['label'] ) . '</span></label></div>';
			} else {
				$html .= $label . '<div class="um-field-area">';
				if ( 'textarea' === $field['type'] ) {
					$html .= '<textarea id="' . esc_attr( $field_id ) . '" name="mp_account_fields[' . esc_attr( $tab['id'] ) . '][' . esc_attr( $key ) . ']" rows="4" class="um-form-field" placeholder="' . esc_attr( $field['placeholder'] ) . '"' . $required . '>' . esc_textarea( $value ) . '</textarea>';
				} elseif ( 'select' === $field['type'] ) {
					$html .= '<select id="' . esc_attr( $field_id ) . '" name="mp_account_fields[' . esc_attr( $tab['id'] ) . '][' . esc_attr( $key ) . ']" class="um-form-field"' . $required . '>';
					$html .= '<option value="">' . esc_html__( 'Bitte auswählen', 'members-plugin' ) . '</option>';
					foreach ( self::parse_options( $field['options'] ) as $option_value => $option_label ) {
						$html .= '<option value="' . esc_attr( $option_value ) . '"' . selected( $value, $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
					}
					$html .= '</select>';
				} elseif ( 'radio' === $field['type'] ) {
					foreach ( self::parse_options( $field['options'] ) as $option_value => $option_label ) {
						$radio_id = $field_id . '-' . sanitize_html_class( $option_value );
						$html .= '<label class="um-field-radio"><input type="radio" id="' . esc_attr( $radio_id ) . '" name="mp_account_fields[' . esc_attr( $tab['id'] ) . '][' . esc_attr( $key ) . ']" value="' . esc_attr( $option_value ) . '"' . checked( $value, $option_value, false ) . $required . ' /> <span class="um-field-radio-option">' . esc_html( $option_label ) . '</span></label>';
					}
				} else {
					$input_type = in_array( $field['type'], array( 'email', 'url', 'number', 'date' ), true ) ? $field['type'] : 'text';
					$min_max    = 'number' === $input_type ? ' step="any"' : '';
					$html .= '<input type="' . esc_attr( $input_type ) . '" id="' . esc_attr( $field_id ) . '" name="mp_account_fields[' . esc_attr( $tab['id'] ) . '][' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" class="um-form-field" placeholder="' . esc_attr( $field['placeholder'] ) . '"' . $min_max . $required . ' />';
				}
				$html .= '</div>';
			}

			if ( '' !== $field['description'] ) {
				$html .= '<div class="um-field-description">' . esc_html( $field['description'] ) . '</div>';
			}
			$html .= '<div class="um-clear"></div></div>';
		}

		return $html;
	}

	/**
	 * Validate custom account fields before UM processes a successful save.
	 *
	 * @param array $submitted Submitted UM form data.
	 */
	public static function validate_submitted_fields( $submitted ) {
		$tab_id = self::submitted_tab_id( $submitted );
		$tab    = self::find_tab( $tab_id );
		if ( ! $tab || ! is_user_logged_in() ) {
			return;
		}

		$values = self::submitted_values( $tab_id );
		foreach ( $tab['fields'] as $field ) {
			$key   = $field['key'];
			$value = isset( $values[ $key ] ) ? self::sanitize_field_value( $values[ $key ], $field['type'] ) : ( 'checkbox' === $field['type'] ? '0' : '' );

			if ( $field['required'] && '' === trim( (string) $value ) && '0' !== $value ) {
				UM()->form()->add_error( 'mp_account_' . $key, sprintf( __( 'Bitte fülle das Feld „%s“ aus.', 'members-plugin' ), $field['label'] ) );
				continue;
			}
			if ( '' === $value || '0' === $value && 'checkbox' === $field['type'] ) {
				continue;
			}
			if ( 'email' === $field['type'] && ! is_email( $value ) ) {
				UM()->form()->add_error( 'mp_account_' . $key, sprintf( __( 'Bitte gib für „%s“ eine gültige E-Mail-Adresse ein.', 'members-plugin' ), $field['label'] ) );
			} elseif ( 'url' === $field['type'] && ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
				UM()->form()->add_error( 'mp_account_' . $key, sprintf( __( 'Bitte gib für „%s“ eine gültige URL ein.', 'members-plugin' ), $field['label'] ) );
			} elseif ( in_array( $field['type'], array( 'select', 'radio' ), true ) ) {
				$options = self::parse_options( $field['options'] );
				if ( ! array_key_exists( $value, $options ) ) {
					UM()->form()->add_error( 'mp_account_' . $key, sprintf( __( 'Bitte wähle eine gültige Option für „%s“ aus.', 'members-plugin' ), $field['label'] ) );
				}
			} elseif ( 'date' === $field['type'] && ! self::valid_date( $value ) ) {
				UM()->form()->add_error( 'mp_account_' . $key, sprintf( __( 'Bitte gib für „%s“ ein gültiges Datum ein.', 'members-plugin' ), $field['label'] ) );
			}
		}
	}

	/**
	 * Save fields on the selected custom tab. UM's own handler runs afterwards.
	 *
	 * @param array $submitted Submitted values.
	 */
	public static function save_submitted_fields( $submitted ) {
		if ( ! is_user_logged_in() ) {
			return;
		}
		$tab_id = self::submitted_tab_id( $submitted );
		$tab    = self::find_tab( $tab_id );
		if ( ! $tab ) {
			return;
		}

		$user_id = get_current_user_id();
		$values  = self::submitted_values( $tab_id );
		foreach ( $tab['fields'] as $field ) {
			$key = $field['key'];
			if ( 'checkbox' === $field['type'] && ! isset( $values[ $key ] ) ) {
				$value = '0';
			} elseif ( ! array_key_exists( $key, $values ) ) {
				continue;
			} else {
				$value = self::sanitize_field_value( $values[ $key ], $field['type'] );
			}

			if ( 'select' === $field['type'] || 'radio' === $field['type'] ) {
				$options = self::parse_options( $field['options'] );
				if ( '' !== $value && ! array_key_exists( $value, $options ) ) {
					continue;
				}
			}
			update_user_meta( $user_id, self::meta_key( $tab, $field ), $value );
		}
	}

	/**
	 * Settings save handler.
	 */
	public static function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'members-plugin' ), 403 );
		}
		check_admin_referer( 'mp_save_account_tabs' );

		$raw_tabs = isset( $_POST['mp_account_tabs'] ) && is_array( $_POST['mp_account_tabs'] ) ? wp_unslash( $_POST['mp_account_tabs'] ) : array();
		$clean    = array();
		$used     = array();
		$tab_count = 0;

		foreach ( $raw_tabs as $raw_tab ) {
			if ( ! is_array( $raw_tab ) ) {
				continue;
			}
			$title = isset( $raw_tab['title'] ) ? sanitize_text_field( $raw_tab['title'] ) : '';
			if ( '' === $title ) {
				continue;
			}
			$slug = isset( $raw_tab['slug'] ) ? sanitize_key( $raw_tab['slug'] ) : '';
			if ( '' === $slug ) {
				$slug = sanitize_key( remove_accents( strtolower( str_replace( ' ', '-', $title ) ) ) );
			}
			$slug = trim( preg_replace( '/[^a-z0-9_-]/', '', $slug ), '_-' );
			if ( '' === $slug ) {
				$slug = 'tab-' . ( $tab_count + 1 );
			}
			$base_slug = $slug;
			$suffix    = 2;
			while ( isset( $used[ $slug ] ) ) {
				$slug = $base_slug . '-' . $suffix;
				$suffix++;
			}
			$used[ $slug ] = true;

			$raw_fields = isset( $raw_tab['fields'] ) && is_array( $raw_tab['fields'] ) ? $raw_tab['fields'] : array();
			$fields     = array();
			$used_keys  = array();
			foreach ( $raw_fields as $raw_field ) {
				if ( ! is_array( $raw_field ) ) {
					continue;
				}
				$key   = isset( $raw_field['key'] ) ? sanitize_key( $raw_field['key'] ) : '';
				$label = isset( $raw_field['label'] ) ? sanitize_text_field( $raw_field['label'] ) : '';
				if ( '' === $key || '' === $label ) {
					continue;
				}
				$base_key = $key;
				$counter  = 2;
				while ( isset( $used_keys[ $key ] ) ) {
					$key = $base_key . '_' . $counter;
					$counter++;
				}
				$used_keys[ $key ] = true;

				$fields[] = array(
					'key'         => $key,
					'label'       => $label,
					'type'        => self::field_type( isset( $raw_field['type'] ) ? $raw_field['type'] : 'text' ),
					'required'    => ! empty( $raw_field['required'] ),
					'placeholder' => isset( $raw_field['placeholder'] ) ? sanitize_text_field( $raw_field['placeholder'] ) : '',
					'default'     => isset( $raw_field['default'] ) ? sanitize_text_field( $raw_field['default'] ) : '',
					'description' => isset( $raw_field['description'] ) ? sanitize_text_field( $raw_field['description'] ) : '',
					'options'     => isset( $raw_field['options'] ) ? self::sanitize_options_text( $raw_field['options'] ) : '',
				);
				if ( count( $fields ) >= 40 ) {
					break;
				}
			}

			if ( empty( $fields ) ) {
				continue;
			}

			$icon = isset( $raw_tab['icon'] ) ? self::sanitize_icon( $raw_tab['icon'] ) : 'um-faicon-cog';
			$button = isset( $raw_tab['button'] ) ? sanitize_text_field( $raw_tab['button'] ) : '';
			$clean[] = array(
				'id'     => 'mp_custom_' . $slug,
				'slug'   => $slug,
				'title'  => $title,
				'icon'   => $icon,
				'button' => $button ? $button : __( 'Speichern', 'members-plugin' ),
				'fields' => $fields,
			);

			$tab_count++;
			if ( count( $clean ) >= 25 ) {
				break;
			}
		}

		update_option( self::OPTION, $clean, false );
		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE, 'mp-saved' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Render admin page.
	 */
	public static function render_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$tabs = get_option( self::OPTION, array() );
		if ( ! is_array( $tabs ) ) {
			$tabs = array();
		}
		if ( empty( $tabs ) ) {
			$tabs = array(
				array(
					'slug' => '',
					'title' => '',
					'icon' => 'um-faicon-cog',
					'button' => __( 'Speichern', 'members-plugin' ),
					'fields' => array(),
				),
			);
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Eigene Konto-Tabs', 'members-plugin' ); ?></h1>
			<p><?php esc_html_e( 'Erstelle zusätzliche Tabs auf der Konto-Seite. Jeder Tab kann eigene Felder, Pflichtfelder und einen eigenen Speichern-Button haben. Leere Tabs ohne gültige Felder werden nicht veröffentlicht.', 'members-plugin' ); ?></p>
			<?php if ( isset( $_GET['mp-saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Konto-Tabs gespeichert.', 'members-plugin' ); ?></p></div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="mp-account-tabs-form">
				<input type="hidden" name="action" value="mp_save_account_tabs" />
				<?php wp_nonce_field( 'mp_save_account_tabs' ); ?>
				<div id="mp-account-tabs-list">
					<?php foreach ( $tabs as $index => $tab ) : ?>
						<?php self::render_tab_card( $tab, $index ); ?>
					<?php endforeach; ?>
				</div>
				<p><button type="button" class="button" id="mp-add-account-tab"><?php esc_html_e( 'Konto-Tab hinzufügen', 'members-plugin' ); ?></button></p>
				<?php submit_button( __( 'Konto-Tabs speichern', 'members-plugin' ) ); ?>
			</form>

			<template id="mp-account-tab-template">
				<?php self::render_tab_card( array( 'title' => '', 'slug' => '', 'icon' => 'um-faicon-cog', 'button' => __( 'Speichern', 'members-plugin' ), 'fields' => array() ), '__INDEX__', true ); ?>
			</template>
			<template id="mp-account-field-template">
				<?php self::render_field_row( array(), '__INDEX__', '__FIELD__' ); ?>
			</template>
			<script>
			(function(){
				var list=document.getElementById('mp-account-tabs-list');
				var addTab=document.getElementById('mp-add-account-tab');
				var tabTemplate=document.getElementById('mp-account-tab-template');
				var fieldTemplate=document.getElementById('mp-account-field-template');
				if(!list||!addTab||!tabTemplate||!fieldTemplate){return;}
				var nextTab=list.querySelectorAll('[data-mp-tab-card]').length;
				function addField(card){
					var body=card.querySelector('[data-mp-fields-body]');
					if(!body){return;}
					var tabIndex=card.getAttribute('data-tab-index');
					var next=parseInt(body.getAttribute('data-next-field')||'0',10);
					body.setAttribute('data-next-field',String(next+1));
					var html=fieldTemplate.innerHTML.replace(/__INDEX__/g,tabIndex).replace(/__FIELD__/g,String(next));
					var holder=document.createElement('tbody');holder.innerHTML=html;
					while(holder.firstChild){body.appendChild(holder.firstChild);}
				}
				addTab.addEventListener('click',function(){
					var index=nextTab++;
					var html=tabTemplate.innerHTML.replace(/__INDEX__/g,String(index));
					var holder=document.createElement('div');holder.innerHTML=html;
					var card=holder.firstElementChild;
					if(card){list.appendChild(card);addField(card);}
				});
				list.addEventListener('click',function(event){
					var removeTab=event.target.closest('[data-mp-remove-tab]');
					if(removeTab){event.preventDefault();var card=removeTab.closest('[data-mp-tab-card]');if(card){card.remove();}return;}
					var addFieldButton=event.target.closest('[data-mp-add-field]');
					if(addFieldButton){event.preventDefault();var card=addFieldButton.closest('[data-mp-tab-card]');if(card){addField(card);}return;}
					var removeField=event.target.closest('[data-mp-remove-field]');
					if(removeField){event.preventDefault();var row=removeField.closest('[data-mp-field-row]');if(row){row.remove();}}
				});
			})();
			</script>
			<style>
				.mp-account-tab-card{background:#fff;border:1px solid #c3c4c7;padding:18px;margin:18px 0;max-width:1200px}
				.mp-account-tab-card h2{margin-top:0}
				.mp-account-tab-settings{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px}
				.mp-account-tab-settings label,.mp-account-field-row label{display:block;font-weight:600;margin-bottom:4px}
				.mp-account-tab-settings input,.mp-account-field-row input,.mp-account-field-row select,.mp-account-field-row textarea{width:100%;max-width:100%}
				.mp-account-fields{overflow:auto;margin:14px 0}
				.mp-account-field-row{min-width:950px;display:grid;grid-template-columns:125px 135px 120px 110px 130px 160px 160px 65px;gap:8px;align-items:start;padding:8px 0;border-top:1px solid #e2e4e7}
				.mp-account-field-row .mp-field-required{padding-top:24px}
				.mp-account-field-row .mp-field-required input{width:auto}
			</style>
		</div>
		<?php
	}

	/**
	 * Render one tab settings card.
	 *
	 * @param array      $tab Tab data.
	 * @param string|int $index Form index.
	 * @param bool       $template Whether this is a template element.
	 */
	private static function render_tab_card( $tab, $index, $template = false ) {
		$fields = isset( $tab['fields'] ) && is_array( $tab['fields'] ) ? $tab['fields'] : array();
		$next_field = 0;
		foreach ( array_keys( $fields ) as $field_index ) {
			if ( is_numeric( $field_index ) ) {
				$next_field = max( $next_field, (int) $field_index + 1 );
			}
		}
		?>
		<section class="mp-account-tab-card" data-mp-tab-card data-tab-index="<?php echo esc_attr( $index ); ?>">
			<h2><?php esc_html_e( 'Konto-Tab', 'members-plugin' ); ?> <button type="button" class="button-link-delete" data-mp-remove-tab><?php esc_html_e( 'Diesen Tab entfernen', 'members-plugin' ); ?></button></h2>
			<div class="mp-account-tab-settings">
				<div><label><?php esc_html_e( 'Titel im Menü', 'members-plugin' ); ?></label><input type="text" name="mp_account_tabs[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( isset( $tab['title'] ) ? $tab['title'] : '' ); ?>" required /></div>
				<div><label><?php esc_html_e( 'Interner Name (Slug)', 'members-plugin' ); ?></label><input type="text" name="mp_account_tabs[<?php echo esc_attr( $index ); ?>][slug]" value="<?php echo esc_attr( isset( $tab['slug'] ) ? $tab['slug'] : '' ); ?>" placeholder="z. B. kontakt" /><p class="description"><?php esc_html_e( 'Nach dem ersten Einsatz möglichst nicht mehr ändern.', 'members-plugin' ); ?></p></div>
				<div><label><?php esc_html_e( 'Icon-Klasse', 'members-plugin' ); ?></label><input type="text" name="mp_account_tabs[<?php echo esc_attr( $index ); ?>][icon]" value="<?php echo esc_attr( isset( $tab['icon'] ) ? $tab['icon'] : 'um-faicon-cog' ); ?>" placeholder="um-faicon-cog" /></div>
				<div><label><?php esc_html_e( 'Button-Text', 'members-plugin' ); ?></label><input type="text" name="mp_account_tabs[<?php echo esc_attr( $index ); ?>][button]" value="<?php echo esc_attr( isset( $tab['button'] ) ? $tab['button'] : __( 'Speichern', 'members-plugin' ) ); ?>" /></div>
			</div>
			<h3><?php esc_html_e( 'Eingabefelder', 'members-plugin' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Bei Auswahlfeldern eine Option pro Zeile eintragen. Optional: Wert|Anzeigetext, zum Beispiel theatre|Theater.', 'members-plugin' ); ?></p>
			<div class="mp-account-fields">
				<div class="mp-account-field-row" aria-hidden="true">
					<span><?php esc_html_e( 'Feldname', 'members-plugin' ); ?></span><span><?php esc_html_e( 'Beschriftung', 'members-plugin' ); ?></span><span><?php esc_html_e( 'Typ', 'members-plugin' ); ?></span><span><?php esc_html_e( 'Pflichtfeld', 'members-plugin' ); ?></span><span><?php esc_html_e( 'Platzhalter', 'members-plugin' ); ?></span><span><?php esc_html_e( 'Standardwert', 'members-plugin' ); ?></span><span><?php esc_html_e( 'Optionen', 'members-plugin' ); ?></span><span></span>
				</div>
				<table style="width:100%;border-collapse:collapse"><tbody data-mp-fields-body data-next-field="<?php echo esc_attr( $next_field ); ?>">
					<?php
					if ( empty( $fields ) ) {
						self::render_field_row( array(), $index, 0 );
					} else {
						foreach ( $fields as $field_index => $field ) {
							self::render_field_row( $field, $index, $field_index );
						}
					}
					?>
				</tbody></table>
			</div>
			<p><button type="button" class="button" data-mp-add-field><?php esc_html_e( 'Eingabefeld hinzufügen', 'members-plugin' ); ?></button></p>
		</section>
		<?php
	}

	/**
	 * Render a field row.
	 *
	 * @param array      $field Field.
	 * @param string|int $tab_index Tab index.
	 * @param string|int $field_index Field index.
	 */
	private static function render_field_row( $field, $tab_index, $field_index ) {
		$types = array(
			'text'     => __( 'Text', 'members-plugin' ),
			'textarea' => __( 'Langer Text', 'members-plugin' ),
			'email'    => __( 'E-Mail', 'members-plugin' ),
			'url'      => __( 'Website / URL', 'members-plugin' ),
			'number'   => __( 'Zahl', 'members-plugin' ),
			'date'     => __( 'Datum', 'members-plugin' ),
			'select'   => __( 'Dropdown', 'members-plugin' ),
			'radio'    => __( 'Einzelauswahl', 'members-plugin' ),
			'checkbox' => __( 'Checkbox', 'members-plugin' ),
		);
		$name = 'mp_account_tabs[' . $tab_index . '][fields][' . $field_index . ']';
		?>
		<tr data-mp-field-row>
			<td style="padding:8px 4px;vertical-align:top"><input type="text" name="<?php echo esc_attr( $name . '[key]' ); ?>" value="<?php echo esc_attr( isset( $field['key'] ) ? $field['key'] : '' ); ?>" placeholder="kontakt_email" /></td>
			<td style="padding:8px 4px;vertical-align:top"><input type="text" name="<?php echo esc_attr( $name . '[label]' ); ?>" value="<?php echo esc_attr( isset( $field['label'] ) ? $field['label'] : '' ); ?>" placeholder="<?php esc_attr_e( 'E-Mail-Adresse', 'members-plugin' ); ?>" /></td>
			<td style="padding:8px 4px;vertical-align:top"><select name="<?php echo esc_attr( $name . '[type]' ); ?>"><?php foreach ( $types as $type => $label ) : ?><option value="<?php echo esc_attr( $type ); ?>" <?php selected( isset( $field['type'] ) ? $field['type'] : 'text', $type ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></td>
			<td style="padding:8px 4px;vertical-align:top"><label style="font-weight:normal"><input type="checkbox" name="<?php echo esc_attr( $name . '[required]' ); ?>" value="1" <?php checked( ! empty( $field['required'] ) ); ?> /> <?php esc_html_e( 'Ja', 'members-plugin' ); ?></label></td>
			<td style="padding:8px 4px;vertical-align:top"><input type="text" name="<?php echo esc_attr( $name . '[placeholder]' ); ?>" value="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>" /></td>
			<td style="padding:8px 4px;vertical-align:top"><input type="text" name="<?php echo esc_attr( $name . '[default]' ); ?>" value="<?php echo esc_attr( isset( $field['default'] ) ? $field['default'] : '' ); ?>" /></td>
			<td style="padding:8px 4px;vertical-align:top"><textarea name="<?php echo esc_attr( $name . '[options]' ); ?>" rows="2" placeholder="ja|Ja&#10;nein|Nein"><?php echo esc_textarea( isset( $field['options'] ) ? $field['options'] : '' ); ?></textarea></td>
			<td style="padding:8px 4px;vertical-align:top"><button type="button" class="button-link-delete" data-mp-remove-field><?php esc_html_e( 'Entfernen', 'members-plugin' ); ?></button><input type="text" style="margin-top:6px" name="<?php echo esc_attr( $name . '[description]' ); ?>" value="<?php echo esc_attr( isset( $field['description'] ) ? $field['description'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Hilfetext', 'members-plugin' ); ?>" /></td>
		</tr>
		<?php
	}

	/**
	 * Find a saved tab by its internal id.
	 *
	 * @param string $id Tab id.
	 * @return array|null
	 */
	private static function find_tab( $id ) {
		foreach ( self::get_tabs() as $tab ) {
			if ( isset( $tab['id'] ) && $tab['id'] === $id ) {
				return $tab;
			}
		}
		return null;
	}

	/**
	 * Get tab id from the submitted UM form.
	 *
	 * @param array $submitted Submitted values.
	 * @return string
	 */
	private static function submitted_tab_id( $submitted ) {
		$id = '';
		if ( is_array( $submitted ) && isset( $submitted['_um_account_tab'] ) ) {
			$id = sanitize_key( $submitted['_um_account_tab'] );
		}
		if ( '' === $id && isset( $_POST['_um_account_tab'] ) ) {
			$id = sanitize_key( wp_unslash( $_POST['_um_account_tab'] ) );
		}
		return $id;
	}

	/**
	 * Read the raw submitted values for a given tab.
	 *
	 * @param string $tab_id Tab id.
	 * @return array
	 */
	private static function submitted_values( $tab_id ) {
		if ( ! isset( $_POST['mp_account_fields'] ) || ! is_array( $_POST['mp_account_fields'] ) ) {
			return array();
		}
		$data = wp_unslash( $_POST['mp_account_fields'] );
		return isset( $data[ $tab_id ] ) && is_array( $data[ $tab_id ] ) ? $data[ $tab_id ] : array();
	}

	/**
	 * Generate a per-tab meta key.
	 *
	 * @param array $tab Tab.
	 * @param array $field Field.
	 * @return string
	 */
	private static function meta_key( $tab, $field ) {
		return 'mp_account_' . sanitize_key( $tab['slug'] ) . '_' . sanitize_key( $field['key'] );
	}

	/**
	 * Normalize a supported field type.
	 *
	 * @param string $type Type.
	 * @return string
	 */
	private static function field_type( $type ) {
		$allowed = array( 'text', 'textarea', 'email', 'url', 'number', 'date', 'select', 'radio', 'checkbox' );
		$type    = sanitize_key( $type );
		return in_array( $type, $allowed, true ) ? $type : 'text';
	}

	/**
	 * Sanitize icon classes to simple whitespace-separated class names.
	 *
	 * @param string $icon Icon.
	 * @return string
	 */
	private static function sanitize_icon( $icon ) {
		$icon = is_string( $icon ) ? $icon : '';
		$icon = preg_replace( '/[^a-zA-Z0-9 _-]/', '', $icon );
		$icon = trim( preg_replace( '/\s+/', ' ', $icon ) );
		return '' !== $icon ? $icon : 'um-faicon-cog';
	}

	/**
	 * Keep option textarea content safe.
	 *
	 * @param mixed $text Option lines.
	 * @return string
	 */
	private static function sanitize_options_text( $text ) {
		if ( ! is_string( $text ) ) {
			return '';
		}
		$lines = preg_split( '/\r\n|\r|\n/', $text );
		$clean = array();
		foreach ( $lines as $line ) {
			$line = sanitize_text_field( trim( $line ) );
			if ( '' !== $line ) {
				$clean[] = $line;
			}
			if ( count( $clean ) >= 100 ) {
				break;
			}
		}
		return implode( "\n", $clean );
	}

	/**
	 * Parse choice options; supports "value|label" or one label per line.
	 *
	 * @param string $text Option lines.
	 * @return array
	 */
	private static function parse_options( $text ) {
		$choices = array();
		foreach ( preg_split( '/\r\n|\r|\n/', self::sanitize_options_text( $text ) ) as $line ) {
			if ( '' === trim( $line ) ) {
				continue;
			}
			$parts = explode( '|', $line, 2 );
			$value = sanitize_text_field( trim( $parts[0] ) );
			$label = isset( $parts[1] ) ? sanitize_text_field( trim( $parts[1] ) ) : $value;
			if ( '' !== $value ) {
				$choices[ $value ] = '' !== $label ? $label : $value;
			}
		}
		return $choices;
	}

	/**
	 * Sanitize one custom field value.
	 *
	 * @param mixed  $value Submitted value.
	 * @param string $type Field type.
	 * @return string
	 */
	private static function sanitize_field_value( $value, $type ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}
		$value = (string) $value;
		switch ( self::field_type( $type ) ) {
			case 'textarea':
				return sanitize_textarea_field( $value );
			case 'email':
				return sanitize_email( $value );
			case 'url':
				return esc_url_raw( $value, array( 'http', 'https' ) );
			case 'number':
				return is_numeric( $value ) ? (string) $value : '';
			case 'checkbox':
				return in_array( $value, array( '1', 'yes', 'true', 'on' ), true ) ? '1' : '0';
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Validate ISO date input.
	 *
	 * @param string $value Date.
	 * @return bool
	 */
	private static function valid_date( $value ) {
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', (string) $value, $matches ) ) {
			return false;
		}
		return checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] );
	}
}
