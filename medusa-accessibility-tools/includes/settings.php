<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function medusa_a11y_defaults() {
	return array(
		'colour'   => 'dark',
		'custom'   => '#111827',
		'position' => 'bottom-right',
		'offset'   => 20,
		'size'     => 'medium',
		'mobile'   => 1,
	);
}

function medusa_a11y_colours() {
	return array(
		'dark'    => __( 'Theme dark colour', 'medusa-accessibility-tools' ),
		'primary' => __( 'Theme primary colour', 'medusa-accessibility-tools' ),
		'accent'  => __( 'Theme accent colour', 'medusa-accessibility-tools' ),
		'custom'  => __( 'Custom colour', 'medusa-accessibility-tools' ),
	);
}

function medusa_a11y_positions() {
	return array(
		'bottom-right' => __( 'Bottom right', 'medusa-accessibility-tools' ),
		'bottom-left'  => __( 'Bottom left', 'medusa-accessibility-tools' ),
		'middle-right' => __( 'Middle right', 'medusa-accessibility-tools' ),
		'middle-left'  => __( 'Middle left', 'medusa-accessibility-tools' ),
	);
}

function medusa_a11y_sizes() {
	return array(
		'small'  => __( 'Small', 'medusa-accessibility-tools' ),
		'medium' => __( 'Medium', 'medusa-accessibility-tools' ),
		'large'  => __( 'Large', 'medusa-accessibility-tools' ),
	);
}

function medusa_a11y_options() {
	return wp_parse_args( (array) get_option( 'medusa_a11y', array() ), medusa_a11y_defaults() );
}

function medusa_a11y_sanitize( $input ) {
	$d   = medusa_a11y_defaults();
	$in  = is_array( $input ) ? $input : array();
	$hex = isset( $in['custom'] ) ? sanitize_hex_color( $in['custom'] ) : '';
	return array(
		'colour'   => isset( $in['colour'] ) && array_key_exists( $in['colour'], medusa_a11y_colours() ) ? $in['colour'] : $d['colour'],
		'custom'   => $hex ? $hex : $d['custom'],
		'position' => isset( $in['position'] ) && array_key_exists( $in['position'], medusa_a11y_positions() ) ? $in['position'] : $d['position'],
		'offset'   => isset( $in['offset'] ) ? max( 0, min( 120, absint( $in['offset'] ) ) ) : $d['offset'],
		'size'     => isset( $in['size'] ) && array_key_exists( $in['size'], medusa_a11y_sizes() ) ? $in['size'] : $d['size'],
		'mobile'   => empty( $in['mobile'] ) ? 0 : 1,
	);
}

function medusa_a11y_register_setting() {
	register_setting(
		'medusa_a11y',
		'medusa_a11y',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'medusa_a11y_sanitize',
			'default'           => medusa_a11y_defaults(),
		)
	);
}
add_action( 'admin_init', 'medusa_a11y_register_setting' );

function medusa_a11y_menu() {
	$parent = defined( 'MEDUSA_CORE_VERSION' ) ? 'medusa' : 'options-general.php';
	add_submenu_page( $parent, __( 'Accessibility Tools', 'medusa-accessibility-tools' ), __( 'Accessibility Tools', 'medusa-accessibility-tools' ), 'manage_options', 'medusa-accessibility-tools', 'medusa_a11y_page' );
}
add_action( 'admin_menu', 'medusa_a11y_menu', 15 );

function medusa_a11y_page_url() {
	return defined( 'MEDUSA_CORE_VERSION' ) ? admin_url( 'admin.php?page=medusa-accessibility-tools' ) : admin_url( 'options-general.php?page=medusa-accessibility-tools' );
}

function medusa_a11y_action_links( $links ) {
	array_unshift( $links, '<a href="' . esc_url( medusa_a11y_page_url() ) . '">' . esc_html__( 'Settings', 'medusa-accessibility-tools' ) . '</a>' );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( MEDUSA_A11Y_DIR . 'medusa-accessibility-tools.php' ), 'medusa_a11y_action_links' );

function medusa_a11y_admin_assets( $hook ) {
	if ( ! str_ends_with( (string) $hook, 'page_medusa-accessibility-tools' ) ) {
		return;
	}
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'medusa-a11y-admin', MEDUSA_A11Y_URL . 'assets/js/admin.js', array( 'wp-color-picker' ), MEDUSA_A11Y_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'medusa_a11y_admin_assets' );

function medusa_a11y_select( $name, $choices, $value ) {
	printf( '<select id="medusa-a11y-%1$s" name="medusa_a11y[%1$s]">', esc_attr( $name ) );
	foreach ( $choices as $key => $label ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $key ), selected( $value, $key, false ), esc_html( $label ) );
	}
	echo '</select>';
}

function medusa_a11y_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = medusa_a11y_options();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Accessibility Tools', 'medusa-accessibility-tools' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'medusa_a11y' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="medusa-a11y-colour"><?php esc_html_e( 'Button colour', 'medusa-accessibility-tools' ); ?></label></th>
					<td>
						<?php medusa_a11y_select( 'colour', medusa_a11y_colours(), $o['colour'] ); ?>
						<p class="medusa-a11y-custom"><input type="text" id="medusa-a11y-custom" name="medusa_a11y[custom]" value="<?php echo esc_attr( $o['custom'] ); ?>" class="medusa-a11y-picker" data-default-color="#111827"></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="medusa-a11y-position"><?php esc_html_e( 'Position', 'medusa-accessibility-tools' ); ?></label></th>
					<td><?php medusa_a11y_select( 'position', medusa_a11y_positions(), $o['position'] ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="medusa-a11y-offset"><?php esc_html_e( 'Distance from the edge', 'medusa-accessibility-tools' ); ?></label></th>
					<td><input type="number" id="medusa-a11y-offset" name="medusa_a11y[offset]" value="<?php echo esc_attr( $o['offset'] ); ?>" min="0" max="120" step="1" class="small-text"> px</td>
				</tr>
				<tr>
					<th scope="row"><label for="medusa-a11y-size"><?php esc_html_e( 'Button size', 'medusa-accessibility-tools' ); ?></label></th>
					<td><?php medusa_a11y_select( 'size', medusa_a11y_sizes(), $o['size'] ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Mobile', 'medusa-accessibility-tools' ); ?></th>
					<td><label><input type="checkbox" name="medusa_a11y[mobile]" value="1" <?php checked( $o['mobile'], 1 ); ?>> <?php esc_html_e( 'Show the button on phones', 'medusa-accessibility-tools' ); ?></label></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<p><?php printf( esc_html__( 'Medusa Accessibility Tools is free from %s.', 'medusa-accessibility-tools' ), '<a href="' . esc_url( MEDUSA_A11Y_LEARN ) . '" target="_blank" rel="noopener">Jelly</a>' ); ?></p>
	</div>
	<?php
}
