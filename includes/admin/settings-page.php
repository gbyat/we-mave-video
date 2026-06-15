<?php
/**
 * Plugin settings and asset management screen.
 *
 * @package Webentwicklerin\WeMaveVideo
 */

declare(strict_types=1);

namespace Webentwicklerin\WeMaveVideo\Admin;

use Webentwicklerin\WeMaveVideo\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders and processes the settings page.
 */
final class Settings_Page {

	private const PAGE_SLUG     = 'we-mave-video';
	private const ACTION_CHECK  = 'we_mave_video_check_update';
	private const ACTION_UPDATE = 'we_mave_video_run_update';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add settings submenu.
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		add_options_page(
			__( 'WE Mave Video', 'we-mave-video' ),
			__( 'WE Mave Video', 'we-mave-video' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register settings with the Settings API.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'we_mave_video',
			Options::OPTION_SETTINGS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => Options::default_settings(),
			)
		);
	}

	/**
	 * Sanitize settings input.
	 *
	 * @param mixed $input Raw settings.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( $input ): array {
		$defaults = Options::default_settings();
		$input    = is_array( $input ) ? $input : array();
		$output   = $defaults;

		$schedule = sanitize_key( (string) ( $input['update_schedule'] ?? $defaults['update_schedule'] ) );
		if ( in_array( $schedule, array( 'daily', 'weekly', 'monthly', 'manual' ), true ) ) {
			$output['update_schedule'] = $schedule;
		}

		$output['auto_update']   = ! empty( $input['auto_update'] );
		$output['load_globally'] = ! empty( $input['load_globally'] );
		$output['load_from_cdn'] = ! empty( $input['load_from_cdn'] );

		$github_updates = sanitize_key( (string) ( $input['github_updates_enabled'] ?? $defaults['github_updates_enabled'] ) );
		if ( in_array( $github_updates, array( 'yes', 'no' ), true ) ) {
			$output['github_updates_enabled'] = $github_updates;
		}

		$player_input = $input['player_defaults'] ?? array();
		if ( ! is_array( $player_input ) ) {
			$player_input = array();
		}

		$output['player_defaults'] = $this->sanitize_player_defaults( $player_input, $defaults['player_defaults'] );

		return $output;
	}

	/**
	 * Sanitize player default values.
	 *
	 * @param array<string, mixed> $input    Raw player defaults.
	 * @param array<string, mixed> $defaults Default values.
	 * @return array<string, mixed>
	 */
	private function sanitize_player_defaults( array $input, array $defaults ): array {
		$output = $defaults;

		$aspect_ratio = sanitize_text_field( (string) ( $input['aspect_ratio'] ?? $defaults['aspect_ratio'] ) );
		if ( preg_match( '/^\d+\s*\/\s*\d+$/', $aspect_ratio ) ) {
			$output['aspect_ratio'] = str_replace( ' ', '', $aspect_ratio );
		}

		$autoplay = sanitize_key( (string) ( $input['autoplay'] ?? $defaults['autoplay'] ) );
		if ( in_array( $autoplay, array( 'false', 'always', 'lazy' ), true ) ) {
			$output['autoplay'] = $autoplay;
		}

		$controls = sanitize_key( (string) ( $input['controls'] ?? $defaults['controls'] ) );
		if ( in_array( $controls, array( 'full', 'big', 'none' ), true ) ) {
			$output['controls'] = $controls;
		}

		$color = sanitize_hex_color( (string) ( $input['color'] ?? '' ) );
		$output['color'] = is_string( $color ) ? $color : '';

		$output['opacity'] = '';
		if ( '' !== $output['color'] && isset( $input['opacity'] ) && is_numeric( $input['opacity'] ) ) {
			$opacity = (float) $input['opacity'];
			if ( $opacity >= 0 && $opacity <= 1 ) {
				$output['opacity'] = (string) $opacity;
			}
		}

		$output['loop']   = ! empty( $input['loop'] );
		$output['poster'] = esc_url_raw( (string) ( $input['poster'] ?? '' ) );

		$output['subtitles'] = sanitize_text_field( (string) ( $input['subtitles'] ?? '' ) );
		$output['theme']     = sanitize_key( (string) ( $input['theme'] ?? '' ) );

		$quality = sanitize_key( (string) ( $input['quality'] ?? '' ) );
		if ( in_array( $quality, array( '', 'sd', 'hd', 'fhd', 'qhd', 'uhd' ), true ) ) {
			$output['quality'] = $quality;
		}

		$audiotracks = sanitize_key( (string) ( $input['audiotracks'] ?? $defaults['audiotracks'] ) );
		if ( in_array( $audiotracks, array( 'auto', 'off' ), true ) ) {
			$output['audiotracks'] = $audiotracks;
		}

		$width  = absint( $input['width'] ?? 0 );
		$height = absint( $input['height'] ?? 0 );
		$output['width']  = $width > 0 ? (string) $width : '';
		$output['height'] = $height > 0 ? (string) $height : '';

		return $output;
	}

	/**
	 * Handle manual check/update actions.
	 *
	 * @return void
	 */
	public function handle_actions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['we_mave_video_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$action = sanitize_key( wp_unslash( (string) $_POST['we_mave_video_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( self::ACTION_CHECK === $action ) {
				check_admin_referer( self::ACTION_CHECK );
				$this->process_check();
			}

			if ( self::ACTION_UPDATE === $action ) {
				check_admin_referer( self::ACTION_UPDATE );
				$this->process_update();
			}
		}
	}

	/**
	 * Process a manual version check.
	 *
	 * @return void
	 */
	private function process_check(): void {
		$updater = new Asset_Updater();
		$result  = $updater->check_for_update();

		if ( is_wp_error( $result ) ) {
			add_settings_error(
				'we_mave_video',
				'we_mave_video_check_failed',
				$result->get_error_message(),
				'error'
			);
			return;
		}

		if ( $result['update_available'] ) {
			add_settings_error(
				'we_mave_video',
				'we_mave_video_update_available',
				sprintf(
					/* translators: 1: remote version, 2: local version */
					__( 'Update available: %1$s (installed: %2$s).', 'we-mave-video' ),
					$result['remote_version'],
					'' !== $result['local_version'] ? $result['local_version'] : __( 'none', 'we-mave-video' )
				),
				'warning'
			);
			return;
		}

		add_settings_error(
			'we_mave_video',
			'we_mave_video_up_to_date',
			sprintf(
				/* translators: %s: installed version */
				__( 'Components are up to date (%s).', 'we-mave-video' ),
				$result['remote_version']
			),
			'success'
		);
	}

	/**
	 * Process a manual update.
	 *
	 * @return void
	 */
	private function process_update(): void {
		$updater = new Asset_Updater();
		$result  = $updater->maybe_update( true );

		if ( is_wp_error( $result ) ) {
			add_settings_error(
				'we_mave_video',
				'we_mave_video_update_failed',
				$result->get_error_message(),
				'error'
			);
			return;
		}

		add_settings_error(
			'we_mave_video',
			'we_mave_video_update_success',
			sprintf(
				/* translators: %s: installed version */
				__( 'Components updated to version %s.', 'we-mave-video' ),
				$result['remote_version']
			),
			'success'
		);
	}

	/**
	 * Enqueue admin assets on the settings page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script(
			'we-mave-video-admin',
			WE_MAVE_VIDEO_URL . 'assets/admin/settings.js',
			array(),
			WE_MAVE_VIDEO_VERSION,
			true
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = Options::get_settings();
		$asset    = Options::get_asset();
		$updater  = new Asset_Updater();
		$defaults = $settings['player_defaults'] ?? Options::default_settings()['player_defaults'];

		if ( ! is_array( $defaults ) ) {
			$defaults = Options::default_settings()['player_defaults'];
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'we_mave_video' ); ?>

			<div class="notice notice-info inline">
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: link to mave AGPL components */
							__(
								'This plugin self-hosts <a href="%s" target="_blank" rel="noopener noreferrer">@maveio/components</a>, which is licensed under AGPL-3.0-or-later.',
								'we-mave-video'
							),
							'https://www.npmjs.com/package/@maveio/components'
						)
					);
					?>
				</p>
			</div>

			<h2><?php esc_html_e( 'Hosted components', 'we-mave-video' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Installed version', 'we-mave-video' ); ?></th>
					<td><?php echo esc_html( '' !== $asset['version'] ? (string) $asset['version'] : __( 'Not installed', 'we-mave-video' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Status', 'we-mave-video' ); ?></th>
					<td><?php echo esc_html( (string) $asset['status'] ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Last check', 'we-mave-video' ); ?></th>
					<td><?php echo esc_html( $this->format_timestamp( (int) $asset['last_check_at'] ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Last update', 'we-mave-video' ); ?></th>
					<td><?php echo esc_html( $this->format_timestamp( (int) $asset['updated_at'] ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Local path', 'we-mave-video' ); ?></th>
					<td><code><?php echo esc_html( (string) $asset['path'] ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Front-end script source', 'we-mave-video' ); ?></th>
					<td>
						<?php if ( $updater->is_using_cdn() ) : ?>
							<span class="dashicons dashicons-warning" style="color:#dba617;" aria-hidden="true"></span>
							<?php esc_html_e( 'Official CDN (debug mode)', 'we-mave-video' ); ?>
							<br />
							<code><?php echo esc_html( Asset_Updater::OFFICIAL_CDN_URL ); ?></code>
						<?php else : ?>
							<?php esc_html_e( 'Self-hosted', 'we-mave-video' ); ?>
							<?php
							$local_url = $updater->get_entry_url();
							if ( '' !== $local_url ) :
								?>
								<br />
								<code><?php echo esc_html( $local_url ); ?></code>
							<?php endif; ?>
						<?php endif; ?>
					</td>
				</tr>
				<?php if ( ! empty( $asset['error_message'] ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Last error', 'we-mave-video' ); ?></th>
					<td><span class="description"><?php echo esc_html( (string) $asset['error_message'] ); ?></span></td>
				</tr>
				<?php endif; ?>
			</table>

			<form method="post" style="display:inline-block;margin-right:8px;">
				<?php wp_nonce_field( self::ACTION_CHECK ); ?>
				<input type="hidden" name="we_mave_video_action" value="<?php echo esc_attr( self::ACTION_CHECK ); ?>" />
				<?php submit_button( __( 'Check for updates', 'we-mave-video' ), 'secondary', 'submit', false ); ?>
			</form>

			<form method="post" style="display:inline-block;">
				<?php wp_nonce_field( self::ACTION_UPDATE ); ?>
				<input type="hidden" name="we_mave_video_action" value="<?php echo esc_attr( self::ACTION_UPDATE ); ?>" />
				<?php submit_button( __( 'Update now', 'we-mave-video' ), 'primary', 'submit', false ); ?>
			</form>

			<hr />

			<form method="post" action="options.php">
				<?php settings_fields( 'we_mave_video' ); ?>

				<h2><?php esc_html_e( 'Update schedule', 'we-mave-video' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Schedule', 'we-mave-video' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[update_schedule]">
								<option value="daily" <?php selected( $settings['update_schedule'], 'daily' ); ?>><?php esc_html_e( 'Daily', 'we-mave-video' ); ?></option>
								<option value="weekly" <?php selected( $settings['update_schedule'], 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'we-mave-video' ); ?></option>
								<option value="monthly" <?php selected( $settings['update_schedule'], 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'we-mave-video' ); ?></option>
								<option value="manual" <?php selected( $settings['update_schedule'], 'manual' ); ?>><?php esc_html_e( 'Manual only', 'we-mave-video' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Automatic updates', 'we-mave-video' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[auto_update]" value="1" <?php checked( ! empty( $settings['auto_update'] ) ); ?> />
								<?php esc_html_e( 'Download and replace components automatically when a newer version is found.', 'we-mave-video' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Load script globally', 'we-mave-video' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[load_globally]" value="1" <?php checked( ! empty( $settings['load_globally'] ) ); ?> />
								<?php esc_html_e( 'Enqueue the player script on every front-end page (not recommended).', 'we-mave-video' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Load from official CDN', 'we-mave-video' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[load_from_cdn]" value="1" <?php checked( ! empty( $settings['load_from_cdn'] ) ); ?> />
								<?php esc_html_e( 'Load the player script from the official mave CDN instead of the self-hosted copy.', 'we-mave-video' ); ?>
							</label>
							<p class="description">
								<?php
								echo wp_kses_post(
									sprintf(
										/* translators: %s: official CDN script URL */
										__(
											'Use this only for debugging: compare player behavior against the reference implementation documented at <a href="%1$s" target="_blank" rel="noopener noreferrer">mave.io</a>. The script is loaded from <code>%2$s</code>. Disable again for production self-hosting.',
											'we-mave-video'
										),
										'https://www.mave.io/docs/player/',
										esc_html( Asset_Updater::OFFICIAL_CDN_URL )
									)
								);
								?>
							</p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Player defaults', 'we-mave-video' ); ?></h2>
				<p class="description"><?php esc_html_e( 'These defaults apply to every embed unless overridden by shortcode or block attributes.', 'we-mave-video' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="we-mave-aspect-ratio"><?php esc_html_e( 'Aspect ratio', 'we-mave-video' ); ?></label></th>
						<td><input id="we-mave-aspect-ratio" type="text" class="regular-text" name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[player_defaults][aspect_ratio]" value="<?php echo esc_attr( (string) $defaults['aspect_ratio'] ); ?>" placeholder="16/9" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="we-mave-autoplay"><?php esc_html_e( 'Autoplay', 'we-mave-video' ); ?></label></th>
						<td>
							<select id="we-mave-autoplay" name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[player_defaults][autoplay]">
								<option value="false" <?php selected( $defaults['autoplay'], 'false' ); ?>><?php esc_html_e( 'Off', 'we-mave-video' ); ?></option>
								<option value="always" <?php selected( $defaults['autoplay'], 'always' ); ?>><?php esc_html_e( 'Always', 'we-mave-video' ); ?></option>
								<option value="lazy" <?php selected( $defaults['autoplay'], 'lazy' ); ?>><?php esc_html_e( 'When in view', 'we-mave-video' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="we-mave-controls"><?php esc_html_e( 'Controls', 'we-mave-video' ); ?></label></th>
						<td>
							<select id="we-mave-controls" name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[player_defaults][controls]">
								<option value="full" <?php selected( $defaults['controls'], 'full' ); ?>><?php esc_html_e( 'Full', 'we-mave-video' ); ?></option>
								<option value="big" <?php selected( $defaults['controls'], 'big' ); ?>><?php esc_html_e( 'Big', 'we-mave-video' ); ?></option>
								<option value="none" <?php selected( $defaults['controls'], 'none' ); ?>><?php esc_html_e( 'None', 'we-mave-video' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="we-mave-color"><?php esc_html_e( 'Controls color', 'we-mave-video' ); ?></label></th>
						<td><input id="we-mave-color" type="text" class="regular-text" name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[player_defaults][color]" value="<?php echo esc_attr( (string) $defaults['color'] ); ?>" placeholder="#5850ec" /></td>
					</tr>
					<tr id="we-mave-opacity-row" <?php echo '' === (string) $defaults['color'] ? 'style="display:none;"' : ''; ?>>
						<th scope="row"><label for="we-mave-opacity"><?php esc_html_e( 'Color opacity', 'we-mave-video' ); ?></label></th>
						<td><input id="we-mave-opacity" type="number" min="0" max="1" step="0.1" class="small-text" name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[player_defaults][opacity]" value="<?php echo esc_attr( (string) $defaults['opacity'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Loop', 'we-mave-video' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[player_defaults][loop]" value="1" <?php checked( ! empty( $defaults['loop'] ) ); ?> />
								<?php esc_html_e( 'Loop playback', 'we-mave-video' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="we-mave-poster"><?php esc_html_e( 'Poster URL', 'we-mave-video' ); ?></label></th>
						<td><input id="we-mave-poster" type="url" class="regular-text" name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[player_defaults][poster]" value="<?php echo esc_attr( (string) $defaults['poster'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="we-mave-subtitles"><?php esc_html_e( 'Subtitles', 'we-mave-video' ); ?></label></th>
						<td><input id="we-mave-subtitles" type="text" class="regular-text" name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[player_defaults][subtitles]" value="<?php echo esc_attr( (string) $defaults['subtitles'] ); ?>" placeholder="en, de or none" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="we-mave-theme"><?php esc_html_e( 'Theme', 'we-mave-video' ); ?></label></th>
						<td><input id="we-mave-theme" type="text" class="regular-text" name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[player_defaults][theme]" value="<?php echo esc_attr( (string) $defaults['theme'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="we-mave-quality"><?php esc_html_e( 'Quality', 'we-mave-video' ); ?></label></th>
						<td>
							<select id="we-mave-quality" name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[player_defaults][quality]">
								<option value="" <?php selected( $defaults['quality'], '' ); ?>><?php esc_html_e( 'Highest available', 'we-mave-video' ); ?></option>
								<option value="sd" <?php selected( $defaults['quality'], 'sd' ); ?>>SD</option>
								<option value="hd" <?php selected( $defaults['quality'], 'hd' ); ?>>HD</option>
								<option value="fhd" <?php selected( $defaults['quality'], 'fhd' ); ?>>FHD</option>
								<option value="qhd" <?php selected( $defaults['quality'], 'qhd' ); ?>>QHD</option>
								<option value="uhd" <?php selected( $defaults['quality'], 'uhd' ); ?>>UHD</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="we-mave-audiotracks"><?php esc_html_e( 'Audio tracks', 'we-mave-video' ); ?></label></th>
						<td>
							<select id="we-mave-audiotracks" name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[player_defaults][audiotracks]">
								<option value="auto" <?php selected( $defaults['audiotracks'], 'auto' ); ?>><?php esc_html_e( 'Auto', 'we-mave-video' ); ?></option>
								<option value="off" <?php selected( $defaults['audiotracks'], 'off' ); ?>><?php esc_html_e( 'Off', 'we-mave-video' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="we-mave-width"><?php esc_html_e( 'Width (px)', 'we-mave-video' ); ?></label></th>
						<td><input id="we-mave-width" type="number" min="0" class="small-text" name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[player_defaults][width]" value="<?php echo esc_attr( (string) $defaults['width'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="we-mave-height"><?php esc_html_e( 'Height (px)', 'we-mave-video' ); ?></label></th>
						<td><input id="we-mave-height" type="number" min="0" class="small-text" name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[player_defaults][height]" value="<?php echo esc_attr( (string) $defaults['height'] ); ?>" /></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Plugin updates', 'we-mave-video' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="we-mave-github-updates"><?php esc_html_e( 'GitHub auto updates', 'we-mave-video' ); ?></label></th>
						<td>
							<select id="we-mave-github-updates" name="<?php echo esc_attr( Options::OPTION_SETTINGS ); ?>[github_updates_enabled]">
								<option value="yes" <?php selected( $settings['github_updates_enabled'], 'yes' ); ?>><?php esc_html_e( 'Enabled', 'we-mave-video' ); ?></option>
								<option value="no" <?php selected( $settings['github_updates_enabled'], 'no' ); ?>><?php esc_html_e( 'Disabled', 'we-mave-video' ); ?></option>
							</select>
							<p class="description">
								<?php
								echo wp_kses_post(
									sprintf(
										/* translators: %s: GitHub repository slug */
										__(
											'Check for plugin updates from <a href="https://github.com/%s/releases" target="_blank" rel="noopener noreferrer">GitHub releases</a>. This updates the WordPress plugin only, not the self-hosted mave components.',
											'we-mave-video'
										),
										esc_html( WE_MAVE_VIDEO_GITHUB_REPO )
									)
								);
								?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save settings', 'we-mave-video' ) ); ?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Embed snippets', 'we-mave-video' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'For Enfold, use the Shortcode element and paste the shortcode below. This is the most reliable integration path.', 'we-mave-video' ); ?>
			</p>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="we-mave-snippet-embed"><?php esc_html_e( 'Embed ID', 'we-mave-video' ); ?></label></th>
					<td>
						<input id="we-mave-snippet-embed" type="text" class="regular-text" placeholder="ubg50Cq5Ilpnar1" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Shortcode', 'we-mave-video' ); ?></th>
					<td>
						<textarea id="we-mave-snippet-shortcode" class="large-text code" rows="2" readonly></textarea>
						<p class="description"><?php esc_html_e( 'Recommended for Enfold and most page builders.', 'we-mave-video' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'HTML markup', 'we-mave-video' ); ?></th>
					<td>
						<textarea id="we-mave-snippet-markup" class="large-text code" rows="2" readonly></textarea>
						<p class="description"><?php esc_html_e( 'Use only in HTML/code modules. The plugin loads the script automatically when this markup is detected in page content.', 'we-mave-video' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Format a unix timestamp for display.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @return string
	 */
	private function format_timestamp( int $timestamp ): string {
		if ( $timestamp <= 0 ) {
			return __( 'Never', 'we-mave-video' );
		}

		return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}
}
