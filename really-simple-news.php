<?php
/*
Plugin Name: Really Simple News
Version: v1.0
Plugin URI:
Author: Oscar Ciutat
Author URI: http://oscarciutat.com/code/
Description: A really simple list of external links
*/

class Really_Simple_News {

	/**
	 * Plugin instance.
	 *
	 * @since 1.0
	 *
	 */
	protected static $instance = null;


	/**
	 * Access this plugin’s working instance
	 *
	 * @since 1.0
	 *
	 */
	public static function get_instance() {
		
		if ( !self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

	
	/**
	 * Used for regular plugin work.
	 *
	 * @since 1.0
	 *
	 */
	public function plugin_setup() {

  		$this->includes();

	        add_action( 'init', array( $this, 'load_language' ) );
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'save_post', array( $this, 'save_article' ) );

		//columns
		add_filter( 'manage_article_posts_columns', array( $this, 'article_columns' ) );
		add_action( 'manage_article_posts_custom_column',  array( $this, 'article_custom_column' ), 5, 2 );

		
		add_shortcode( 'articles', array( $this, 'shortcode_articles' ) );
	
	}

	
	/**
	 * Constructor. Intentionally left empty and public.
	 *
	 * @since 1.0
	 *
	 */
	public function __construct() {}
	
	
 	/**
	 * Includes required core files used in admin and on the frontend.
	 *
	 * @since 1.0
	 *
	 */
	protected function includes() {}


	/**
	 * Loads language
	 *
	 * @since 1.0
	 *
	 */
	function load_language() {
		load_plugin_textdomain( 'really-simple-news', '', dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	
	/**
	 * enqueue_scripts
	 */
	function enqueue_scripts() {
		wp_enqueue_style( 'really-simple-news-css', plugins_url( '/style.css', __FILE__), array( 'dashicons' ) );
	}


	/**
	 * admin_enqueue_scripts
	 */
	function admin_enqueue_scripts() {
		wp_enqueue_media();
		wp_enqueue_script( 'really-simple-news', plugins_url( '/js/backend.js', __FILE__), array( 'jquery' ), false, true );
	}


	/*
	 * register_post_type
	 *
	 * @since 1.0
	 */
    function register_post_type() {
		
		$labels = array(
			'name' => __( 'Articles', 'really-simple-news' ),
			'singular_name' => __( 'Article', 'really-simple-news' ),
			'add_new' => __( 'Add New Article', 'really-simple-news' ),
			'add_new_item' => __( 'Add New Article', 'really-simple-news' ),
			'edit_item' => __( 'Edit Article', 'really-simple-news' ),
			'new_item' => __( 'New Article', 'really-simple-news' ),
			'view_item' => __( 'View Article', 'really-simple-news' ),
			'search_items' => __( 'Search Articles', 'really-simple-news' ),
			'not_found' => __( 'No Articles found', 'really-simple-news' ),
			'not_found_in_trash' => __( 'No Articles found in Trash', 'really-simple-news' )
		);
      
		$args = array(
			'query_var' => false,
			'rewrite' => false,
			'public' => true,
			'exclude_from_search' => true,
			'publicly_queryable' => false,
			'show_in_nav_menus' => false,
			'show_ui' => true,
			'menu_position' => 5,
			'menu_icon' => 'dashicons-admin-links',
			'supports' => array( 'title' ), 
			'labels' => $labels,
			'register_meta_box_cb' => array( $this , 'add_article_meta_boxes' )
		);

		register_post_type( 'article', $args);
		
	}


	/*
	* add_article_meta_boxes
	*/

	function add_article_meta_boxes() {
		add_meta_box( 'article-links', __( 'Links', 'really-simple-news' ), array( $this , 'article_links_meta_box' ), 'article', 'normal', 'low' );
	}


	/*
	* article_links_meta_box
	*/
  
	function article_links_meta_box() {
		global $post;
		$article_url = ( get_post_meta( $post->ID, '_rsn_article_url', true ) ) ? get_post_meta( $post->ID, '_rsn_article_url', true ) : '';
		$article_pdf = ( get_post_meta( $post->ID, '_rsn_article_pdf', true ) ) ? get_post_meta( $post->ID, '_rsn_article_pdf', true ) : '';
	?>

		<input type="hidden" id="metabox_nonce" name="metabox_nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ); ?>" />

		<table class="form-table article-url">
		<tr>
			<th scope="row"><label for="article_url"><?php _e( 'URL', 'really-simple-news' ); ?></label></th>
			<td>
			<input class="large-text" id="article_url" type="url" name="article_url" placeholder="http://" value="<?php echo esc_url( $article_url ); ?>" />
			<p class="description"><?php _e( 'Link to the article', 'really-simple-news' ); ?></p>
			</td>
		</tr>
		</table>
		<table class="form-table article-pdf">
		<tr>
			<th scope="row"><label for="article_pdf"><?php _e( 'PDF', 'really-simple-news' ); ?></label></th>
			<td>
			<input class="regular-text" id="article_pdf" type="url" name="article_pdf" placeholder="http://" value="<?php echo esc_url( $article_pdf ); ?>" />
			<input class="button" id="select_pdf" type="button" data-choose="<?php esc_attr_e( 'Media Library' ); ?>" value="<?php _e( 'Select File', 'really-simple-news' ); ?>" />
			<p class="description"><?php _e( 'Link to an additional PDF file', 'really-simple-news' ); ?></p>
			</td>
		</tr>
		</table>
	<?php
	}

	
	/*
	* save_article
	*/
 
	function save_article( $post_id ) {
		// verify nonce
		if ( isset( $_POST['metabox_nonce'] ) && !wp_verify_nonce( $_POST['metabox_nonce'], basename(__FILE__) ) ) {
			return $post_id;
		}
	
		// is autosave?
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// check permissions
		if ( isset( $_POST['post_type'] ) ) {
			if ( 'page' == $_POST['post_type'] ) {
				if ( !current_user_can( 'edit_page', $post_id ) ) {
					return $post_id;
				}
			} elseif ( !current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}

		if ( isset( $_POST['post_type'] ) && ( 'article' == $_POST['post_type'] ) ) {
			
			$article_url = isset( $_POST['article_url'] ) ? sanitize_text_field( $_POST['article_url'] ) : '';
			$article_pdf = isset( $_POST['article_pdf'] ) ? sanitize_text_field( $_POST['article_pdf'] ) : '';

			update_post_meta( $post_id, '_rsn_article_url', $article_url );
			update_post_meta( $post_id, '_rsn_article_pdf', $article_pdf );
			
		}
		
	}

	
	/**
	 * shortcode_articles
	 */
	function shortcode_articles( $atts ) {
		$html = $this->shortcode_atts( $atts );
		return $html;
	}

	
	/**
	 * shortcode_atts
	 */
	function shortcode_atts( $atts ) {
		global $post;
		
		extract( shortcode_atts( array(
			'number' => '-1',
			'format' => 'm/Y'
		), $atts ) );
		
		$html = '';
		
	    $args = array(
			'post_type' => 'article',
			'numberposts' => $number,
			'post_status' => null,
			'orderby' => 'date',
			'order' => 'DESC'
		);
		$articles = get_posts( $args );
		
		if ( $articles ) {
			
			$html .= '<ul class="articles">';
			
			foreach ( $articles as $article ) {
				$article_url = ( get_post_meta( $article->ID, '_rsn_article_url', true ) ) ? get_post_meta( $article->ID, '_rsn_article_url', true ) : '';
				$article_pdf = ( get_post_meta( $article->ID, '_rsn_article_pdf', true ) ) ? get_post_meta( $article->ID, '_rsn_article_pdf', true ) : '';
				$html .= '<li>
					<span class="article-date">' . get_the_date( $format, $article->ID ) . '</span> - ';
				if ( $article_url ) {
					$html .= '<a class="article-link" target="_blank" rel="bookmark" href="'. esc_url( $article_url ) .'">' . get_the_title( $article->ID ) . '</a>';
				} else {
					$html .= '<span class="article-title">' . get_the_title( $article->ID ) . '</span>';
				}
				if ( $article_pdf ) {
					$html .= ' (+<a class="article-pdf" target="_blank" rel="bookmark" href="'. esc_url( $article_pdf ) .'">PDF</a>)';
				}

				$html .= '</li>';
			}
			
			$html .= '</ul>';
			
		}
		
		return $html;
	}
	
	
	/*
	* article_columns
	*/

	function article_columns( $columns ) {
		$new = array();
		foreach( $columns as $key => $value ) {
			if ( $key == 'date' ) {
				// Put the URL column before the Date column
				$new['url'] = __( 'URL', 'really-simple-news' );
			}
			$new[$key] = $value;
		}
		return $new;
	}


	/*
	* article_custom_column
	*/

	function article_custom_column( $column, $post_id ) {
		switch ( $column ) {
			case 'url':
				$article_url = ( get_post_meta( $post_id, '_rsn_article_url', true ) ) ? get_post_meta( $post_id, '_rsn_article_url', true ) : '';
				echo '<a target="_blank" rel="bookmark" href="'. esc_url( $article_url ) .'">' . esc_url( $article_url ) . '</a>';
				break;
		}
	}

}

add_action( 'plugins_loaded', array ( Really_Simple_News::get_instance(), 'plugin_setup' ) );