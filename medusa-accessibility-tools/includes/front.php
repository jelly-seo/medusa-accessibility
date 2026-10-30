<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function medusa_a11y_enabled() {
	return ! is_admin() && ! is_customize_preview() && ! wp_doing_ajax() && ! is_feed() && ! is_embed() && (bool) apply_filters( 'medusa_a11y_show', true );
}

function medusa_a11y_text_colour( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) ) {
		return '#ffffff';
	}
	$lum = 0;
	foreach ( array( 0.2126, 0.7152, 0.0722 ) as $i => $weight ) {
		$c    = hexdec( substr( $hex, $i * 2, 2 ) ) / 255;
		$c    = $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		$lum += $weight * $c;
	}
	$white = 1.05 / ( $lum + 0.05 );
	$black = ( $lum + 0.05 ) / ( 0.0119 + 0.05 );
	return $white >= $black ? '#ffffff' : '#111827';
}

function medusa_a11y_button_vars() {
	$o      = medusa_a11y_options();
	$themes = array(
		'dark'    => array( 'var(--wp--preset--color--dark, #111827)', 'var(--wp--custom--contrast--on-dark-heading, #ffffff)' ),
		'primary' => array( 'var(--wp--preset--color--primary, #1554d1)', 'var(--wp--preset--color--white, #ffffff)' ),
		'accent'  => array( 'var(--wp--preset--color--accent, #ff6a1a)', 'var(--wp--custom--contrast--on-accent, #111827)' ),
	);
	if ( 'custom' === $o['colour'] ) {
		$colours = array( $o['custom'], medusa_a11y_text_colour( $o['custom'] ) );
	} else {
		$colours = $themes[ $o['colour'] ] ?? $themes['dark'];
	}
	$sizes = array(
		'small'  => 44,
		'medium' => 52,
		'large'  => 60,
	);
	return sprintf(
		':root{--mat-bg:%1$s;--mat-fg:%2$s;--mat-size:%3$dpx;--mat-offset:%4$dpx}',
		$colours[0],
		$colours[1],
		$sizes[ $o['size'] ] ?? 52,
		(int) $o['offset']
	);
}

function medusa_a11y_early() {
	if ( ! medusa_a11y_enabled() ) {
		return;
	}
	wp_print_inline_script_tag( 'try{var s=JSON.parse(localStorage.getItem("medusaA11y")||"{}"),d=document.documentElement;if(s.text>0&&s.text<5)d.setAttribute("data-mat-text",s.text);if(s.font)d.setAttribute("data-mat-font","dyslexic");if(s.invert)d.setAttribute("data-mat-invert","");if(["protanopia","deuteranopia","tritanopia"].indexOf(s.cb)>-1)d.setAttribute("data-mat-cb",s.cb)}catch(e){}' );
}
add_action( 'wp_head', 'medusa_a11y_early', 1 );

function medusa_a11y_assets() {
	if ( ! medusa_a11y_enabled() ) {
		return;
	}
	wp_enqueue_style( 'medusa-a11y', MEDUSA_A11Y_URL . 'assets/css/widget.css', array(), MEDUSA_A11Y_VERSION );
	wp_add_inline_style( 'medusa-a11y', medusa_a11y_button_vars() );
	wp_enqueue_script( 'medusa-a11y', MEDUSA_A11Y_URL . 'assets/js/widget.js', array(), MEDUSA_A11Y_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_localize_script(
		'medusa-a11y',
		'MedusaA11y',
		array(
			'steps' => array( 100, 112.5, 125, 137.5, 150 ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'medusa_a11y_assets' );

function medusa_a11y_icon() {
	return '<svg viewBox="0 0 16 16" width="24" height="24" aria-hidden="true" focusable="false"><path fill="currentColor" fill-rule="evenodd" d="M13.4338078,4.18678 C13.5358078,4.65874 13.2359078,5.12406 12.7640078,5.22611 L10.0455078,5.81388 C9.58532779,5.91339 9.25686779,6.32045 9.25686779,6.7913 L9.25686779,7.67098 C9.25686779,8.62688 9.42818779,9.57506 9.76270779,10.4705 L11.3873078,14.8195 C11.5563078,15.2718 11.3266078,15.7755 10.8742078,15.9445 C10.4219078,16.1135 9.91820779,15.8837 9.74930779,15.4314 L8.19520779,11.2713 C8.03331779,10.8379 7.42032779,10.8379 7.25843779,11.2713 L5.70436779,15.4314 C5.53538779,15.8837 5.03171779,16.1135 4.57938779,15.9445 C4.12705779,15.7755 3.89734779,15.2718 4.06632779,14.8195 L5.69097779,10.4704 C6.02549779,9.57496 6.19681779,8.62678 6.19681779,7.67088 L6.19681779,6.79129 C6.19681779,6.32044 5.86835779,5.91338 5.40814779,5.81388 L2.68971779,5.22611 C2.21775779,5.12406 1.91788779,4.65874 2.01992779,4.18678 C2.12197779,3.71483 2.58729779,3.41495 3.05925779,3.517 L6.88151779,4.34344 C7.43863779,4.46389 8.01506779,4.46389 8.57218779,4.34344 L12.3944078,3.517 C12.8664078,3.41495 13.3317078,3.71483 13.4338078,4.18678 Z M7.68341779,0 C8.6491429,0 9.43201779,0.782874886 9.43201779,1.7486 C9.43201779,2.71432511 8.6491429,3.4972 7.68341779,3.4972 C6.71769267,3.4972 5.93481779,2.71432511 5.93481779,1.7486 C5.93481779,0.782874886 6.71769267,0 7.68341779,0 Z"/></svg>';
}

function medusa_a11y_widget() {
	if ( ! medusa_a11y_enabled() ) {
		return;
	}
	$o       = medusa_a11y_options();
	$classes = 'mat-widget mat-' . $o['position'] . ( $o['mobile'] ? '' : ' mat-hide-mobile' );
	$modes   = array(
		''             => __( 'Off', 'medusa-accessibility-tools' ),
		'protanopia'   => __( 'Protanopia (red-blind)', 'medusa-accessibility-tools' ),
		'deuteranopia' => __( 'Deuteranopia (green-blind)', 'medusa-accessibility-tools' ),
		'tritanopia'   => __( 'Tritanopia (blue-blind)', 'medusa-accessibility-tools' ),
	);
	?>
<div id="mat-widget" class="<?php echo esc_attr( $classes ); ?>">
	<button type="button" class="mat-toggle" id="mat-toggle" aria-expanded="false" aria-controls="mat-panel">
		<?php echo medusa_a11y_icon(); ?>
		<span class="mat-sr"><?php esc_html_e( 'Accessibility menu', 'medusa-accessibility-tools' ); ?></span>
	</button>
	<div class="mat-panel" id="mat-panel" role="dialog" aria-modal="false" aria-labelledby="mat-title" hidden>
		<div class="mat-head">
			<h2 class="mat-title" id="mat-title"><?php esc_html_e( 'Accessibility Menu', 'medusa-accessibility-tools' ); ?></h2>
			<button type="button" class="mat-close" id="mat-close" aria-label="<?php esc_attr_e( 'Close accessibility menu', 'medusa-accessibility-tools' ); ?>">&times;</button>
		</div>
		<div class="mat-row">
			<span class="mat-label" id="mat-text-label"><?php esc_html_e( 'Text size', 'medusa-accessibility-tools' ); ?></span>
			<div class="mat-stepper" role="group" aria-labelledby="mat-text-label">
				<button type="button" class="mat-step" data-mat-step="-1" aria-label="<?php esc_attr_e( 'Smaller text', 'medusa-accessibility-tools' ); ?>">A&minus;</button>
				<output class="mat-value" id="mat-text-value" aria-live="polite">100%</output>
				<button type="button" class="mat-step" data-mat-step="1" aria-label="<?php esc_attr_e( 'Larger text', 'medusa-accessibility-tools' ); ?>">A+</button>
			</div>
		</div>
		<div class="mat-row">
			<span class="mat-label" id="mat-font-label"><?php esc_html_e( 'Dyslexic Friendly Font', 'medusa-accessibility-tools' ); ?></span>
			<button type="button" class="mat-switch" data-mat-toggle="font" role="switch" aria-checked="false" aria-labelledby="mat-font-label"><span></span></button>
		</div>
		<div class="mat-row">
			<span class="mat-label" id="mat-invert-label"><?php esc_html_e( 'Invert colours', 'medusa-accessibility-tools' ); ?></span>
			<button type="button" class="mat-switch" data-mat-toggle="invert" role="switch" aria-checked="false" aria-labelledby="mat-invert-label"><span></span></button>
		</div>
		<div class="mat-row mat-row-stack">
			<label class="mat-label" for="mat-cb"><?php esc_html_e( 'Colour blind mode', 'medusa-accessibility-tools' ); ?></label>
			<select id="mat-cb" class="mat-select">
				<?php foreach ( $modes as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<button type="button" class="mat-reset" id="mat-reset"><?php esc_html_e( 'Reset all', 'medusa-accessibility-tools' ); ?></button>
		<a class="mat-learn" href="<?php echo esc_url( MEDUSA_A11Y_LEARN ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Learn more about web accessibility', 'medusa-accessibility-tools' ); ?><span class="mat-sr"> <?php esc_html_e( '(opens in a new tab)', 'medusa-accessibility-tools' ); ?></span></a>
	</div>
</div>
<svg class="mat-filters" aria-hidden="true" focusable="false" width="0" height="0">
	<filter id="mat-protanopia"><feColorMatrix type="matrix" values="1 0 0 0 0 0.4789 0.4769 0.0442 0 0 0.5973 -0.6887 1.0914 0 0 0 0 0 1 0"/></filter>
	<filter id="mat-deuteranopia"><feColorMatrix type="matrix" values="1 0 0 0 0 0.1628 0.725 0.1122 0 0 0.4547 -0.6454 1.1907 0 0 0 0 0 1 0"/></filter>
	<filter id="mat-tritanopia"><feColorMatrix type="matrix" values="0.7412 -0.4072 0.666 0 0 0.0751 0.5852 0.3397 0 0 0 0 1 0 0 0 0 0 1 0"/></filter>
</svg>
	<?php
}
add_action( 'wp_footer', 'medusa_a11y_widget', 5 );
