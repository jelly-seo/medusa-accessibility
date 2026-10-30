<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Medusa_A11y_Updater {

	const SLUG  = 'medusa-accessibility-tools';
	const CACHE = 'medusa_a11y_release';

	public static function init() {
		add_filter( 'update_plugins_github.com', array( __CLASS__, 'update' ), 10, 3 );
		add_filter( 'plugins_api', array( __CLASS__, 'info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_folder' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'flush' ), 10, 0 );
	}

	public static function basename() {
		return plugin_basename( MEDUSA_A11Y_FILE );
	}

	public static function release( $force = false ) {
		$cached = $force ? false : get_site_transient( self::CACHE );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$url      = apply_filters( 'medusa_a11y_release_url', 'https://api.github.com/repos/' . MEDUSA_A11Y_REPO . '/releases/latest' );
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'Medusa-Accessibility-Tools/' . MEDUSA_A11Y_VERSION . '; ' . home_url( '/' ),
				),
			)
		);
		$body     = is_wp_error( $response ) ? null : json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['tag_name'] ) ) {
			$fail = array(
				'version' => '',
				'package' => '',
			);
			set_site_transient( self::CACHE, $fail, HOUR_IN_SECONDS );
			return $fail;
		}
		$package = '';
		foreach ( isset( $body['assets'] ) ? (array) $body['assets'] : array() as $asset ) {
			if ( isset( $asset['name'], $asset['browser_download_url'] ) && self::SLUG . '.zip' === $asset['name'] ) {
				$package = $asset['browser_download_url'];
			}
		}
		$release = array(
			'version'   => ltrim( (string) $body['tag_name'], 'vV' ),
			'package'   => $package,
			'url'       => isset( $body['html_url'] ) ? $body['html_url'] : 'https://github.com/' . MEDUSA_A11Y_REPO,
			'notes'     => isset( $body['body'] ) ? (string) $body['body'] : '',
			'published' => isset( $body['published_at'] ) ? (string) $body['published_at'] : '',
		);
		set_site_transient( self::CACHE, $release, 12 * HOUR_IN_SECONDS );
		return $release;
	}

	public static function update( $update, $plugin_data, $plugin_file ) {
		if ( self::basename() !== $plugin_file ) {
			return $update;
		}
		$release = self::release();
		if ( ! $release['version'] || ! $release['package'] || version_compare( $release['version'], $plugin_data['Version'], '<=' ) ) {
			return $update;
		}
		return array(
			'id'           => $plugin_data['UpdateURI'],
			'slug'         => self::SLUG,
			'version'      => $release['version'],
			'url'          => MEDUSA_A11Y_LEARN,
			'package'      => $release['package'],
			'requires'     => '6.3',
			'requires_php' => '7.4',
		);
	}

	public static function notes_html( $markdown ) {
		$html  = '';
		$list  = false;
		$lines = preg_split( '/\r\n|\r|\n/', (string) $markdown );
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( preg_match( '/^[-*]\s+(.*)$/', $line, $m ) ) {
				if ( ! $list ) {
					$html .= '<ul>';
					$list  = true;
				}
				$html .= '<li>' . esc_html( $m[1] ) . '</li>';
				continue;
			}
			if ( $list ) {
				$html .= '</ul>';
				$list  = false;
			}
			if ( '' === $line ) {
				continue;
			}
			if ( preg_match( '/^#+\s*(.*)$/', $line, $m ) ) {
				$html .= '<h4>' . esc_html( $m[1] ) . '</h4>';
			} else {
				$html .= '<p>' . esc_html( $line ) . '</p>';
			}
		}
		return $list ? $html . '</ul>' : $html;
	}

	public static function info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || self::SLUG !== $args->slug ) {
			return $result;
		}
		$release = self::release();
		$version = $release['version'] ? $release['version'] : MEDUSA_A11Y_VERSION;
		return (object) array(
			'name'          => 'Medusa Accessibility Tools',
			'slug'          => self::SLUG,
			'version'       => $version,
			'author'        => '<a href="https://jellyseo.com/">Jelly</a>',
			'homepage'      => MEDUSA_A11Y_LEARN,
			'requires'      => '6.3',
			'requires_php'  => '7.4',
			'last_updated'  => $release['published'],
			'download_link' => $release['package'],
			'sections'      => array(
				'description' => '<p>' . esc_html__( 'A floating accessibility button for any WordPress site. Visitors can make text larger, switch to a dyslexic friendly font, invert colours and turn on a colour blind mode for protanopia, deuteranopia or tritanopia. Their choices are remembered in their browser.', 'medusa-accessibility-tools' ) . '</p><p><a href="' . esc_url( MEDUSA_A11Y_LEARN ) . '" target="_blank" rel="noopener">' . esc_html__( 'More about web accessibility from Jelly', 'medusa-accessibility-tools' ) . '</a></p>',
				'changelog'   => $release['notes'] ? self::notes_html( $release['notes'] ) : '<p>' . esc_html__( 'See the releases on GitHub.', 'medusa-accessibility-tools' ) . '</p>',
			),
		);
	}

	public static function fix_folder( $source, $remote_source, $upgrader, $hook_extra ) {
		global $wp_filesystem;
		if ( empty( $hook_extra['plugin'] ) || self::basename() !== $hook_extra['plugin'] ) {
			return $source;
		}
		if ( basename( untrailingslashit( $source ) ) === self::SLUG ) {
			return $source;
		}
		$new = trailingslashit( $remote_source ) . self::SLUG . '/';
		if ( $wp_filesystem && $wp_filesystem->move( $source, $new ) ) {
			return $new;
		}
		return $source;
	}

	public static function flush() {
		delete_site_transient( self::CACHE );
	}
}

Medusa_A11y_Updater::init();
