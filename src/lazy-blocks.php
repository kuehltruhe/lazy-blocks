<?php
/**
 * Plugin Name:  Lazy Blocks
 * Description:  Gutenberg blocks visual constructor. Custom meta fields or blocks with output without hard coding.
 * Version:      @@plugin_version
 * Author:       nK
 * Author URI:   https://nkdev.info/
 * License:      GPLv2 or later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  @@text_domain
 *
 * @package lazyblocks
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LazyBlocks Class
 */
class LazyBlocks {

    /**
     * The single class instance.
     *
     * @var null
     */
    private static $_instance = null;

    /**
     * Main Instance
     * Ensures only one instance of this class exists in memory at any one time.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
            self::$_instance->init();
        }
        return self::$_instance;
    }

    /**
     * The base path to the plugin in the file system.
     *
     * @var string
     */
    public $plugin_path;

    /**
     * URL Link to plugin
     *
     * @var string
     */
    public $plugin_url;

    /**
     * Plugin Name: LazyBlocks
     *
     * @var string
     */
    public $plugin_name;

    /**
     * Plugin Version.
     *
     * @var string
     */
    public $plugin_version;

    /**
     * Creator of the plugin.
     *
     * @var string
     */
    public $plugin_author;

    /**
     * Slug of the plugin.
     *
     * @var string
     */
    public $plugin_slug;

    /**
     * I18n friendly version of Plugin Name.
     *
     * @var string
     */
    public $plugin_name_sanitized;

    /**
     * Blocks class object.
     *
     * @var object
     */
    private $blocks;

    /**
     * Templates class object.
     *
     * @var object
     */
    private $templates;

    /**
     * LazyBlocks constructor.
     */
    public function __construct() {
        /* We do nothing here! */
    }

    /**
     * Init.
     */
    public function init() {
        $this->plugin_path = plugin_dir_path( __FILE__ );
        $this->plugin_url  = plugin_dir_url( __FILE__ );

        // get current plugin data.
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $data                 = get_plugin_data( __FILE__ );
        $this->plugin_name    = $data['Name'];
        $this->plugin_version = $data['Version'];
        $this->plugin_author  = $data['Author'];

        $this->plugin_slug           = plugin_basename( __FILE__ );
        $this->plugin_name_sanitized = basename( __FILE__, '.php' );

        $this->load_text_domain();
        $this->add_actions();
        $this->include_dependencies();

        $this->controls  = new LazyBlocks_Controls();
        $this->blocks    = new LazyBlocks_Blocks();
        $this->templates = new LazyBlocks_Templates();
        $this->tools     = new LazyBlocks_Tools();

        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    /**
     * Sets the text domain with the plugin translated into other languages.
     */
    public function load_text_domain() {
        load_plugin_textdomain( '@@text_domain', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Actions.
     */
    public function add_actions() {
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
    }

    /**
     * Set plugin Dependencies.
     */
    private function include_dependencies() {
        require_once $this->plugin_path . '/classes/class-controls.php';
        require_once $this->plugin_path . '/classes/class-blocks.php';
        require_once $this->plugin_path . '/classes/class-templates.php';
        require_once $this->plugin_path . '/classes/class-tools.php';
        require_once $this->plugin_path . '/classes/class-rest.php';
    }

    /**
     * Get lazyblocks controls object.
     */
    public function controls() {
        return $this->controls;
    }

    /**
     * Get lazyblocks blocks object.
     */
    public function blocks() {
        return $this->blocks;
    }

    /**
     * Get lazyblocks templates object.
     */
    public function templates() {
        return $this->templates;
    }

    /**
     * Add lazyblocks block.
     *
     * @param array $data - block data.
     */
    public function add_block( $data ) {
        return $this->blocks()->add_block( $data );
    }

    /**
     * Add lazyblocks template.
     *
     * @param array $data - template data.
     */
    public function add_template( $data ) {
        return $this->templates()->add_template( $data );
    }

    /**
     * Admin menu.
     */
    public function admin_menu() {
        // Documentation menu link.
        add_submenu_page(
            'edit.php?post_type=lazyblocks',
            esc_html__( 'Documentation', '@@text_domain' ),
            esc_html__( 'Documentation', '@@text_domain' ),
            'manage_options',
            'https://lazyblocks.com/documentation/getting-started/'
        );
    }

    /**
     * Enqueue admin styles and scripts.
     */
    public function admin_enqueue_scripts() {
        global $post;
        global $post_type;
        global $wp_locale;

        if ( 'lazyblocks' === $post_type ) {
            wp_enqueue_script(
                'lazyblocks-constructor',
                $this->plugin_url . 'assets/admin/constructor/index.min.js',
                array( 'wp-blocks', 'wp-editor', 'wp-block-editor', 'wp-i18n', 'wp-element', 'wp-components', 'lodash', 'jquery' ),
                '@@plugin_version'
            );
            wp_localize_script( 'lazyblocks-constructor', 'lazyblocksConstructorData', array(
                'post_id'            => isset( $post->ID ) ? $post->ID : 0,
                'allowed_mime_types' => get_allowed_mime_types(),
                'controls'           => $this->controls()->get_controls(),
            ) );

            wp_enqueue_style( 'lazyblocks-constructor', $this->plugin_url . 'assets/admin/constructor/style.min.css', array(), '@@plugin_version' );
        }

        wp_enqueue_script( 'date_i18n', $this->plugin_url . 'vendor/date_i18n/date_i18n.js', array(), '1.0.0', true );

        $month_names       = array_map( array( &$wp_locale, 'get_month' ), range( 1, 12 ) );
        $month_names_short = array_map( array( &$wp_locale, 'get_month_abbrev' ), $month_names );
        $day_names         = array_map( array( &$wp_locale, 'get_weekday' ), range( 0, 6 ) );
        $day_names_short   = array_map( array( &$wp_locale, 'get_weekday_abbrev' ), $day_names );

        wp_localize_script( 'date_i18n', 'DATE_I18N', array(
            'month_names'       => $month_names,
            'month_names_short' => $month_names_short,
            'day_names'         => $day_names,
            'day_names_short'   => $day_names_short,
        ) );

        wp_enqueue_style( 'lazyblocks-admin', $this->plugin_url . 'assets/admin/css/style.min.css', '', '@@plugin_version' );
    }
}

/**
 * The main cycle of the plugin.
 *
 * @return null|LazyBlocks
 */
function lazyblocks() {
    return LazyBlocks::instance();
}
add_action( 'plugins_loaded', 'lazyblocks' );

/**
 * Function to get meta value with some improvements for Lazyblocks metas.
 *
 * @param string   $name - metabox name.
 * @param int|null $id - post id.
 *
 * @return array|mixed|object
 */
function get_lzb_meta( $name, $id = null ) {
    $control_data = null;

    if ( null === $id ) {
        global $post;
        $id = $post->ID;
    }

    // Find control data by meta name.
    $blocks = lazyblocks()->blocks()->get_blocks();
    foreach ( $blocks as $block ) {
        if ( isset( $block['controls'] ) && is_array( $block['controls'] ) ) {
            foreach ( $block['controls'] as $control ) {
                if ( $control_data || 'true' !== $control['save_in_meta'] ) {
                    continue;
                }

                $meta_name = false;

                if ( isset( $control['save_in_meta_name'] ) && $control['save_in_meta_name'] ) {
                    $meta_name = $control['save_in_meta_name'];
                } elseif ( $control['name'] ) {
                    $meta_name = $control['name'];
                }

                if ( $meta_name && $meta_name === $name ) {
                    $control_data = $control;
                }
            }
        }
    }

    $result = get_post_meta( $id, $name, true );

    // set default.
    if ( ! $result && isset( $control_data['default'] ) && $control_data['default'] ) {
        $result = $control_data['default'];
    }

    return apply_filters( 'lzb/get_meta', $result, $name, $id, $control_data );
}
