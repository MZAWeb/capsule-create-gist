<?php
/**
 * Plugin Name: Capsule create Gist
 * Plugin URI:  https://github.com/MZAWeb/capsule-create-gist
 * Description: Allows you to export a Capsule entry to an anonymous Gist
 * Author:      Daniel Dvorkin
 * Plugin URI:  http://danieldvork.in
 * Version:     0.1
 * Text Domain: capsule-create-gist
 * Domain Path: /languages/
 */


class Capsule_Create_Gist {
	/**
	 * Path to this plugin's folder
	 * @var
	 */
	private $path;

	/**
	 * URL of this plugin's folder
	 * @var
	 */
	private $url;

	/**
	 *  Class constructor. Dah!
	 */
	public function __construct() {
		$this->path = trailingslashit( plugin_dir_path( __FILE__ ) );
		$this->url  = trailingslashit( plugin_dir_url(  __FILE__ ) );
		$this->hooks();
	}

	/**
	 * All the add_action and add_filter calls to make this
	 * plugin work.
	 */
	protected function hooks() {
		add_action( 'wp_enqueue_scripts',             array( $this, 'scripts'         ), 15    );
		add_action( 'capsule_post_menu_after',        array( $this, 'add_github_icon' ), 10    );
		add_action( 'capsule_controller_action_post', array( $this, 'ajax_handler'    ), 10, 1 );
	}

	/**
	 *  Enqueue the JavaScript and CSS files.
	 */
	public function scripts() {
		wp_enqueue_script( 'capsule-create-gist', $this->url . 'assets/capsule-create-gist.js', array( 'capsule' ) );
		wp_enqueue_style(  'capsule-create-gist', $this->url . 'assets/capsule-create-gist.css'                    );
	}

	/**
	 * Adds the GitHub logo as a link in the post-menu nav links
	 */
	public function add_github_icon() {
		echo sprintf( "<a href='%s' class='%s' title='%s'></a>", "#", 'capsule-create-gist-icon', __( 'Create a Gist', 'capsule-create-gist' ) );
	}

	/**
	 * Checks if the passed action is ours, and if so handles the gist upload.
	 * @param string $action
	 */
	public function ajax_handler( $action ) {
		if ( $action !== 'create_gist' )
			return;

		$post_id  = intval( $_POST['post_id'] );
		$post     = get_post( $post_id );
		$gist_url = $this->create_gist( $post->post_title, $post->post_content );

		$result = 'success';
		if ( empty( $gist_url ) ) {
			$result   = 'error';
			$gist_url = __( 'Something went wrong...', 'capsule-create-gist' );
		}

		$response = array( 'post_id' => $post_id,
		                   'result'  => $result,
		                   'msg'     => $gist_url,
		                   'html'    => '',
		);
		header( 'Content-type: application/json' );
		echo json_encode( $response );
		die();
	}

	/**
	 * Makes a POST to the GitHub API to create an anonymous Gist
	 * and returns the Gist URL.
	 *
	 * @param string $title
	 * @param string $content
	 *
	 * @return bool|string
	 */
	protected function create_gist( $title = '', $content ) {

		if ( empty( $title ) )
			$title = get_bloginfo( 'name' );

		$api_url = 'https://api.github.com/gists';

		$args = array(
			'redirection' => 0,
			'timeout' => 30,
			'body' => json_encode( array(
				'description' => $title,
				'public'      => true,
				'files'       => array(
					sanitize_title( $title ) => array(
						'content' => $content
					)
				)
			) )
		);

		$response = wp_remote_post( $api_url, $args );

		if ( is_wp_error( $response ) || empty( $response['response']['code'] ) || $response['response']['code'] != 201 )
			return false;

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		return esc_url( $body->html_url );
	}

	/**
	 * Provides a series
	 * @param $content
	 *
	 * @return mixed
	 */
	 protected function style_content_for_gist( $content ) {

		return $content;
	}

}


/**
 *  Loads the plugin after Capsule is set up.
 *  Bails out if the current theme is not Capsule.
 */
function capsule_create_gist_load() {
	if ( wp_get_theme()->get( 'Name' ) != 'Capsule' )
		return;

	new Capsule_Create_Gist();
}
add_action('after_setup_theme', 'capsule_create_gist_load' );


