<?php
/**
 * IP Geo Block - Register all actions and filters for the plugin
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2016 tokkonopapa
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 */
class IP_Geo_Block_Loader {

	/**
	 * The array of actions registered with WordPress.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      array $actions The actions registered with WordPress to fire when the plugin loads.
	 */
	protected $actions;

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      array $filters The filters registered with WordPress to fire when the plugin loads.
	 */
	protected $filters;

	/**
	 * Initialize the collections used to maintain the actions and filters.
	 *
	 * @since    3.0.0
	 */
	public function __construct() {

		$this->actions = array();
		$this->filters = array();

	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @since    3.0.0
	 * @param    string $hook          The name of the WordPress action that is being registered.
	 * @param    object $component     A reference to the instance of the object on which the action is defined.
	 * @param    string $callback      The name of the function definition on the $component.
	 * @param    int    $priority      Optional. he priority at which the function should be fired. Default is 10.
	 * @param    int    $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @since    3.0.0
	 * @param    string $hook          The name of the WordPress filter that is being registered.
	 * @param    object $component     A reference to the instance of the object on which the filter is defined.
	 * @param    string $callback      The name of the function definition on the $component.
	 * @param    int    $priority      Optional. he priority at which the function should be fired. Default is 10.
	 * @param    int    $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1
	 */
	public function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $callback, $priority, $accepted_args );
	}

	public function apply_filters( $hook, $value ) {
		$args = func_get_args();

		foreach ( $this->filters as $index => $filter ) {
			if ( $filter['hook'] === $hook ) {
				$args[1] = $value;
				$value = call_user_func_array(
					$filter['callback'], array_slice( $args, 1, (int)$filter['accepted_args'] )
				);
			}
		}

		return $value;
	}

	/**
	 * A utility function that is used to register the actions and hooks into a single
	 * collection.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @param    array  $hooks         The collection of hooks that is being registered (that is, actions or filters).
	 * @param    string $hook          The name of the WordPress filter that is being registered.
	 * @param    object $component     A reference to the instance of the object on which the filter is defined.
	 * @param    string $callback      The name of the function definition on the $component.
	 * @param    int    $priority      The priority at which the function should be fired.
	 * @param    int    $accepted_args The number of arguments that should be passed to the $callback.
	 * @return   array                 The collection of actions and filters registered with WordPress.
	 */
	private function add( $hooks, $hook, $callback, $priority, $accepted_args ) {

		$hooks[] = array(
			'hook'          => $hook,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args
		);

		return $hooks;

	}

	/**
	 * Register the filters and actions with WordPress.
	 *
	 * @since    3.0.0
	 */
	public function run() {

		/**
		 * This part will be executed after loading this plugin.
		 * Register all the rest of the action and filter hooks.
		 */
		if ( IP_Geo_Block_Util::may_be_logged_in() ) {
			foreach ( $this->filters as $hook ) {
				add_filter( $hook['hook'], $hook['callback'], $hook['priority'], $hook['accepted_args'] );
			}

			foreach ( $this->actions as $hook ) {
				add_action( $hook['hook'], $hook['callback'], $hook['priority'], $hook['accepted_args'] );
			}
		}

		/**
		 * This part will be executed at the very beginning of WordPress core.
		 * Execute callbacks that are specified by the component with 'init'.
		 */
		else {
			foreach ( $this->actions as $index => $hook ) {
				if ( in_array( $hook['hook'], array( 'init', 'wp_loaded' ) ) ) {
					// Execute callback directly
					call_user_func( $hook['callback'], $hook['accepted_args'] );

					// To avoid duplicated execution, delete this hook
					unset( $this->actions[ $index ] );
				}
			}
		}

	}

}