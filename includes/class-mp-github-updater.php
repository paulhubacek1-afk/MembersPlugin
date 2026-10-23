<?php
/**
 * Members Plugin GitHub release updater.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'MP_GitHub_Updater' ) ) {
	final class MP_GitHub_Updater {
		const REPOSITORY = 'paulhubacek1-afk/MembersPlugin';
		const CACHE_KEY  = 'mp_github_release_update_v1081';
		const CACHE_TTL  = 15 * MINUTE_IN_SECONDS;

		public static function init() {
			add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );
			add_filter( 'plugins_api', array( __CLASS__, 'plugin_information' ), 20, 3 );
			add_action( 'upgrader_process_complete', array( __CLASS__, 'clear_cache' ), 10, 2 );
		}

		private static function plugin_file() {
			return plugin_basename( MP_FILE );
		}

		private static function release() {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) && isset( $cached['tag_name'], $cached['package'] ) ) {
				return $cached;
			}

			// The repository may contain upstream core tags such as 2.14.1.
			// Only use the product's own 1.0.x release line for this updater.
			$response = wp_remote_get(
				'https://api.github.com/repos/' . self::REPOSITORY . '/releases?per_page=30',
				array(
					'timeout' => 8,
					'headers' => array(
						'Accept'     => 'application/vnd.github+json',
						'User-Agent' => 'MembersPlugin-WordPress-Updater',
						'X-GitHub-Api-Version' => '2022-11-28',
				),
			)
			);

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return false;
			}

			$releases = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $releases ) ) {
				return false;
			}

			$selected = false;
			foreach ( $releases as $item ) {
				if ( ! is_array( $item ) || empty( $item['tag_name'] ) || ! empty( $item['draft'] ) || ! empty( $item['prerelease'] ) ) {
					continue;
				}
				$version = ltrim( sanitize_text_field( $item['tag_name'] ), 'vV' );
				if ( ! preg_match( '/^1\\.0\\.\\d+(?:\\.\\d+)?$/', $version ) || empty( $item['assets'] ) ) {
					continue;
				}
				$package = '';
				foreach ( $item['assets'] as $asset ) {
					if ( isset( $asset['name'], $asset['browser_download_url'] ) && 'members-plugin.zip' === $asset['name'] ) {
						$package = esc_url_raw( $asset['browser_download_url'] );
						break;
					}
				}
				if ( empty( $package ) ) {
					continue;
				}
				if ( ! $selected || version_compare( $version, $selected['tag_name'], '>' ) ) {
					$selected = array(
						'tag_name' => $version,
						'package'  => $package,
					'html_url' => isset( $item['html_url'] ) ? esc_url_raw( $item['html_url'] ) : '',
					'name'     => isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : 'Members Plugin ' . $version,
					'body'     => isset( $item['body'] ) ? wp_kses_post( $item['body'] ) : '',
				);
				}
			}
			if ( ! $selected ) {
				return false;
			}
			set_transient( self::CACHE_KEY, $selected, self::CACHE_TTL );
			return $selected;
		}

		public static function inject_update( $transient ) {
			if ( ! is_object( $transient ) ) {
				$transient = new stdClass();
			}
			if ( ! isset( $transient->checked ) || ! is_array( $transient->checked ) ) {
				return $transient;
			}

			$file = self::plugin_file();
			if ( ! isset( $transient->checked[ $file ] ) ) {
				return $transient;
			}

			$release = self::release();
			if ( ! $release ) {
				return $transient;
			}

			$installed = $transient->checked[ $file ];
			if ( version_compare( $release['tag_name'], $installed, '>' ) ) {
				$update = (object) array(
					'slug'        => dirname( $file ),
					'plugin'      => $file,
					'new_version' => $release['tag_name'],
					'url'         => $release['html_url'],
					'package'     => $release['package'],
					'tested'      => '',
					'requires'    => '6.2',
					'requires_php'=> '7.0',
				);
				if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
					$transient->response = array();
				}
				$transient->response[ $file ] = $update;
				unset( $transient->no_update[ $file ] );
			}
			return $transient;
		}

		public static function plugin_information( $result, $action, $args ) {
			if ( 'plugin_information' !== $action || empty( $args->slug ) || dirname( self::plugin_file() ) !== $args->slug ) {
				return $result;
			}
			$release = self::release();
			if ( ! $release ) {
				return $result;
			}
			return (object) array(
				'name'          => 'Members Plugin – Profiles & Community',
				'slug'          => dirname( self::plugin_file() ),
				'version'       => $release['tag_name'],
				'author'        => '<a href="https://github.com/paulhubacek1-afk">Paul Hubacek</a>',
				'homepage'      => 'https://github.com/' . self::REPOSITORY,
				'download_link' => $release['package'],
				'sections'      => array(
					'description' => 'Members Plugin – Profile und Community-Funktionen.',
					'changelog'   => nl2br( esc_html( wp_strip_all_tags( $release['body'] ) ) ),
				),
			);
		}

		public static function clear_cache( $upgrader, $options ) {
			if ( isset( $options['action'], $options['type'] ) && 'update' === $options['action'] && 'plugin' === $options['type'] ) {
				delete_transient( self::CACHE_KEY );
			}
		}
	}
	MP_GitHub_Updater::init();
}
