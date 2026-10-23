<?php
/**
 * Members Plugin – mail recipient policy and delivery diagnostics.
 *
 * Messages addressed to @buehne-total.eu are intentionally suppressed.
 * SMTP/wp_mail failures are logged without storing message bodies.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

final class MP_Mail_Diagnostics {

	const LOG_OPTION = 'mp_mail_diagnostics_log';
	const PAGE       = 'mp-mail-log';
	const BLOCKED_DOMAIN = 'buehne-total.eu';

	/**
	 * Set only when every recipient in the current wp_mail call is suppressed.
	 *
	 * @var bool
	 */
	private static $suppress_next_mail = false;

	/**
	 * Register mail-policy and diagnostics hooks.
	 */
	public static function init() {
		add_filter( 'wp_mail', array( __CLASS__, 'filter_recipients' ), 9999 );
		add_filter( 'pre_wp_mail', array( __CLASS__, 'short_circuit_suppressed_mail' ), 9999, 2 );
		add_action( 'wp_mail_failed', array( __CLASS__, 'record_mail_failure' ), 10, 1 );
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 1003 );
		add_action( 'admin_post_mp_clear_mail_log', array( __CLASS__, 'clear_log' ) );
	}

	/**
	 * Remove recipients at the blocked domain from To, Cc and Bcc.
	 *
	 * Remaining recipients still receive the email. If all recipients are
	 * blocked, pre_wp_mail short-circuits the request to avoid SMTP delivery.
	 *
	 * @param array $args wp_mail arguments.
	 * @return array
	 */
	public static function filter_recipients( $args ) {
		if ( ! is_array( $args ) ) {
			return $args;
		}

		$blocked_count = 0;
		$to_tokens     = self::split_recipients( isset( $args['to'] ) ? $args['to'] : array() );
		$allowed_to    = self::remove_blocked_recipients( $to_tokens, $blocked_count );

		$headers = isset( $args['headers'] ) ? $args['headers'] : array();
		$headers = self::filter_headers( $headers, $blocked_count, $remaining_header_recipients );

		$args['to']      = $allowed_to;
		$args['headers'] = $headers;

		$remaining_count = count( $allowed_to ) + (int) $remaining_header_recipients;
		if ( $blocked_count > 0 ) {
			if ( 0 === $remaining_count ) {
				self::$suppress_next_mail = true;
				self::record_event(
					'suppressed',
					sprintf(
						/* translators: %d: number of suppressed recipients */
						__( 'E-Mail nicht versendet: %d Empfänger mit @buehne-total.eu wurden durch die Empfängerregel ausgeschlossen.', 'ultimate-member' ),
						$blocked_count
					)
				);
			} else {
				self::record_event(
					'partially-suppressed',
					sprintf(
						/* translators: %d: number of suppressed recipients */
						__( '%d Empfänger mit @buehne-total.eu wurden entfernt; die übrigen Empfänger werden weiterhin angeschrieben.', 'ultimate-member' ),
						$blocked_count
					)
				);
			}
		}

		return $args;
	}

	/**
	 * Split one or more recipient entries into safely preserved address tokens.
	 *
	 * @param mixed $recipients Recipient entry or entries.
	 * @return string[]
	 */
	private static function split_recipients( $recipients ) {
		$values = is_array( $recipients ) ? $recipients : array( $recipients );
		$tokens = array();

		foreach ( $values as $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}
			$parts = preg_split( '/[,;]+/', (string) $value );
			foreach ( $parts as $part ) {
				$part = trim( $part );
				if ( '' !== $part ) {
					$tokens[] = $part;
				}
			}
		}

		return $tokens;
	}

	/**
	 * Return the email address inside a recipient token.
	 *
	 * @param string $token Recipient token.
	 * @return string
	 */
	private static function extract_email( $token ) {
		if ( preg_match( '/<\s*([^<>]+)\s*>/', $token, $matches ) ) {
			$token = $matches[1];
		} elseif ( preg_match( '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $token, $matches ) ) {
			$token = $matches[0];
		}

		return strtolower( sanitize_email( trim( $token ) ) );
	}

	/**
	 * Determine whether a recipient belongs to the suppressed domain.
	 *
	 * @param string $token Recipient token.
	 * @return bool
	 */
	private static function is_blocked_recipient( $token ) {
		$email = self::extract_email( $token );
		if ( ! $email || false === strpos( $email, '@' ) ) {
			return false;
		}

		$domain = substr( $email, strrpos( $email, '@' ) + 1 );
		return self::BLOCKED_DOMAIN === $domain;
	}

	/**
	 * Keep allowed recipients and count removed addresses.
	 *
	 * @param string[] $tokens        Recipient tokens.
	 * @param int      $blocked_count Counter passed by reference.
	 * @return string[]
	 */
	private static function remove_blocked_recipients( $tokens, &$blocked_count ) {
		$allowed = array();
		foreach ( $tokens as $token ) {
			if ( self::is_blocked_recipient( $token ) ) {
				$blocked_count++;
			} else {
				$allowed[] = $token;
			}
		}
		return $allowed;
	}

	/**
	 * Filter Cc and Bcc headers without changing Reply-To or other headers.
	 *
	 * @param string|array $headers       Header lines.
	 * @param int          $blocked_count Suppressed recipient count.
	 * @param int          $remaining     Remaining Cc/Bcc count, passed by reference.
	 * @return string|array
	 */
	private static function filter_headers( $headers, &$blocked_count, &$remaining ) {
		$was_array = is_array( $headers );
		if ( $was_array ) {
			$lines = $headers;
		} elseif ( is_string( $headers ) && '' !== $headers ) {
			$lines = preg_split( '/\r\n|\r|\n/', $headers );
		} else {
			$remaining = 0;
			return $headers;
		}

		$filtered = array();
		$remaining = 0;
		foreach ( $lines as $line ) {
			if ( ! is_string( $line ) ) {
				continue;
			}

			if ( preg_match( '/^(Cc|Bcc)\s*:\s*(.*)$/i', trim( $line ), $matches ) ) {
				$tokens  = self::split_recipients( $matches[2] );
				$allowed = self::remove_blocked_recipients( $tokens, $blocked_count );
				$remaining += count( $allowed );
				if ( ! empty( $allowed ) ) {
					$filtered[] = $matches[1] . ': ' . implode( ', ', $allowed );
				}
				continue;
			}

			$filtered[] = $line;
		}

		return $was_array ? $filtered : implode( "\r\n", $filtered );
	}

	/**
	 * Short-circuit wp_mail only when all recipients were suppressed.
	 *
	 * Returning true stops PHPMailer from attempting delivery; it is a logged
	 * policy suppression, not proof that a message was delivered.
	 *
	 * @param null|bool $pre_wp_mail Existing short-circuit value.
	 * @param array     $atts        Filtered wp_mail arguments.
	 * @return null|bool
	 */
	public static function short_circuit_suppressed_mail( $pre_wp_mail, $atts ) {
		if ( self::$suppress_next_mail ) {
			self::$suppress_next_mail = false;
			return true;
		}

		return $pre_wp_mail;
	}

	/**
	 * Record the WP_Error reported when WordPress/PHPMailer cannot send mail.
	 *
	 * @param WP_Error $error Mail failure.
	 */
	public static function record_mail_failure( $error ) {
		if ( ! is_wp_error( $error ) ) {
			return;
		}

		$message = $error->get_error_message();
		$message = sanitize_text_field( wp_strip_all_tags( (string) $message ) );
		if ( strlen( $message ) > 350 ) {
			$message = substr( $message, 0, 350 ) . '…';
		}

		self::record_event(
			'smtp-failed',
			sprintf(
				/* translators: 1: WordPress mail error code, 2: error message */
				__( 'WordPress meldet einen Mailversand-Fehler (%1$s): %2$s', 'ultimate-member' ),
				sanitize_key( (string) $error->get_error_code() ),
				$message
			)
		);
	}

	/**
	 * Save a short, bounded log without email body contents.
	 *
	 * @param string $status  Event status.
	 * @param string $message Human-readable diagnostic.
	 */
	private static function record_event( $status, $message ) {
		$events = get_option( self::LOG_OPTION, array() );
		if ( ! is_array( $events ) ) {
			$events = array();
		}

		array_unshift(
			$events,
			array(
				'time'    => current_time( 'mysql' ),
				'status'  => sanitize_key( $status ),
				'message' => sanitize_text_field( $message ),
			)
		);

		update_option( self::LOG_OPTION, array_slice( $events, 0, 40 ), false );
	}

	/**
	 * Register the mail log under the Members Plugin dashboard menu.
	 */
	public static function register_menu() {
		add_submenu_page(
			'ultimatemember',
			__( 'E-Mail-Protokoll', 'ultimate-member' ),
			__( 'E-Mail-Protokoll', 'ultimate-member' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render recent suppression and delivery failure events.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$events = get_option( self::LOG_OPTION, array() );
		if ( ! is_array( $events ) ) {
			$events = array();
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'E-Mail-Protokoll', 'ultimate-member' ); ?></h1>
			<p><?php esc_html_e( 'Empfänger mit @buehne-total.eu werden absichtlich nicht angeschrieben. Fehlgeschlagene WordPress-/SMTP-Sendeversuche werden hier ohne den eigentlichen E-Mail-Inhalt protokolliert.', 'ultimate-member' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:12px 0">
				<input type="hidden" name="action" value="mp_clear_mail_log">
				<?php wp_nonce_field( 'mp_clear_mail_log' ); ?>
				<?php submit_button( __( 'Protokoll leeren', 'ultimate-member' ), 'secondary', 'submit', false ); ?>
			</form>
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Zeitpunkt', 'ultimate-member' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'ultimate-member' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Details', 'ultimate-member' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $events ) ) : ?>
						<tr><td colspan="3"><?php esc_html_e( 'Noch keine Mail-Ereignisse gespeichert.', 'ultimate-member' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $events as $event ) : ?>
							<tr>
								<td><?php echo esc_html( isset( $event['time'] ) ? $event['time'] : '' ); ?></td>
								<td>
									<?php
									$status = isset( $event['status'] ) ? $event['status'] : '';
									if ( 'smtp-failed' === $status ) {
										esc_html_e( 'SMTP/Versand fehlgeschlagen', 'ultimate-member' );
									} elseif ( 'partially-suppressed' === $status ) {
										esc_html_e( 'Teilweise unterdrückt', 'ultimate-member' );
									} else {
										esc_html_e( 'Empfänger unterdrückt', 'ultimate-member' );
									}
									?>
								</td>
								<td><?php echo esc_html( isset( $event['message'] ) ? $event['message'] : '' ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Clear diagnostics as an administrator action.
	 */
	public static function clear_log() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'ultimate-member' ), 403 );
		}
		check_admin_referer( 'mp_clear_mail_log' );
		delete_option( self::LOG_OPTION );
		wp_safe_redirect(
			add_query_arg(
				array( 'page' => self::PAGE, 'mp-cleared' => 1 ),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
