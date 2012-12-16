<?php

class WysijaPlugin_Upgrader_Skin extends WP_Upgrader_Skin {
	var $plugin = '';
	var $plugin_active = false;
	var $plugin_network_active = false;

	function __construct($args = array()) {
		$defaults = array( 'url' => '', 'plugin' => '', 'nonce' => '', 'title' => __('Downloading Wysija Premium',WYSIJA) );
		$args = wp_parse_args($args, $defaults);

		$this->plugin = $args['plugin'];

		$this->plugin_active = is_plugin_active( $this->plugin );
		$this->plugin_network_active = is_plugin_active_for_network( $this->plugin );

		parent::__construct($args);
	}

	function after() {
		$this->plugin = $this->upgrader->plugin_info();
		if ( !empty($this->plugin) && !is_wp_error($this->result) && $this->plugin_active ){
			echo '<iframe style="border:0;overflow:hidden" width="100%" height="170px" src="' . wp_nonce_url('update.php?action=activate-plugin&networkwide=' . $this->plugin_network_active . '&plugin=' . $this->plugin, 'activate-plugin_' . $this->plugin) .'"></iframe>';
		}

		$update_actions =  array(
			'activate_plugin' => '<a href="admin.php?page=wysija_campaigns&action=install_wjp" title="' . esc_attr__('Activate this plugin') . '" >' . __('Activate Plugin') . '</a>',
		);
		if ( $this->plugin_active )
			unset( $update_actions['activate_plugin'] );
		if ( ! $this->result || is_wp_error($this->result) )
			unset( $update_actions['activate_plugin'] );

		$update_actions = apply_filters('update_plugin_complete_actions', $update_actions, $this->plugin);
		if ( ! empty($update_actions) )
			$this->feedback(implode(' | ', (array)$update_actions));
	}

	function before() {
		if ( $this->upgrader->show_before ) {
			echo $this->upgrader->show_before;
			$this->upgrader->show_before = '';
		}
	}
}