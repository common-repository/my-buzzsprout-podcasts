<?php
/*
  Plugin Name: My Buzzsprout Podcasts
  Description: This plugin creates a podcast page where you can import all your podcasts hosted on Buzzsprout through Buzzsprout feed URL. It also fetches description and transcript of the podcast. 
  Version: 1.0.0
  Author: Aveosoft
  Author URI: https://aveosoft.com
 */

if (!defined('ABSPATH')) {
    exit;
}

define( 'MY_BUZZSPROUT_PODCASTS_VERSION', '1.0.0' );

    /**
     * @desc add view of all podcast
     *
     */
    function mbpl_my_custom_post_podcast() {
        $labels = array(
            'name' => _x('Podcasts', 'post type general name'),
            'singular_name' => _x('Podcast', 'post type singular name'),
            'add_new' => _x('Add New', 'podcast'),
            'add_new_item' => __('Add New Podcast'),
            'edit_item' => __('Edit Podcast'),
            'new_item' => __('New Podcast'),
            'all_items' => __('All Podcast'),
            'view_item' => __('View Podcast'),
            'search_items' => __('Search Podcast'),
            'not_found' => __('No podcasts found'),
            'not_found_in_trash' => __('No podcasts found in the Trash'),
            'parent_item_colon' => '',
            'menu_name' => 'Podcast'
        );
        $args = array(
            'labels' => $labels,
            'description' => 'Podcast',
            'public' => true,
            'menu_position' => 5,
            'supports' => array('title', 'thumbnail'),
            'has_archive' => true,
            'rewrite' => array('slug' => 'podcast'),
        );
        register_post_type('podcast', $args);
    }
    /**
     * Register our stylesheet and script.
     */
    function mbpl_my_buzz_podcast_style() {
            wp_register_style('podcast',plugins_url('css/podcast.css',__FILE__ ));
            wp_enqueue_style('podcast');
            wp_register_script('podcast_js',plugins_url('js/podcast.js',__FILE__ ));
            wp_enqueue_script('podcast_js');
    }

class MBPL_Buzzsprout_Podcast{

    /**
     * @desc Initializes the plugin
     *
     */
    public static function initialize(){
        add_shortcode("podcast_list", array( __CLASS__,"get_podcast_list"));
        add_filter('template_include', array( __CLASS__,'my_plugin_templates'));
        add_action('add_meta_boxes', array( __CLASS__,'wporg_add_custom_box'));
        add_action('save_post', array( __CLASS__,'podcast_save_custom_metabox'));
        add_action('admin_menu',array( __CLASS__, 'podcast_setting_menu'));
        add_action('admin_init',array( __CLASS__,'register_settings'));
        add_action('admin_notices',array( __CLASS__,'podcast_admin_notice'));
    }
     /**
     * @desc add setting menu
     *
     */
    public static function podcast_setting_menu() {
        add_submenu_page("edit.php?post_type=podcast", "Setting", "Setting", 'manage_options', "podcast-setting", array( __CLASS__,'options_page_content'));
    }
    /**
     * @desc register setting 
     *
     */
    public static function register_settings(){
        register_setting( 'podcast-setting', 'podcast-setting', array( __CLASS__,'podcasting_options_validate' ));
        add_settings_section( 'podcasting_settings','Settings',array( __CLASS__,'podcasting_settings_section_cb'),'podcast-setting');
        add_settings_field( 'podcasting_feed_address', 'Buzzsprout RSS feed' ,array( __CLASS__,'podcasting_feed_address_cb') , 'podcast-setting', 'podcasting_settings' );
    }
    /**
     * @desc validate input url setting 
     *
     */
     public static function podcasting_options_validate($input){
        $new_input = array();
        $new_input['feed-uri'] = esc_url_raw(strip_tags( $input['feed-uri'] ) );
        return $new_input;
    }
    /**
     * @desc callback function setting_section 
     *
     */
    public static function podcasting_settings_section_cb(){
            return '';
    }
    /**
     * @desc callback function setting_field 
     *
     */
    public static function podcasting_feed_address_cb(){
        $podcasting_options = get_option('podcast-setting');
        ?>
        <input style="width: 300px" type="text" name="<?php echo esc_attr( 'podcast-setting[feed-uri]'); ?>" value="<?php echo esc_attr($podcasting_options['feed-uri']); ?>" />        
        <?php }
        /**
     * @desc validate feed url setting 
     *
     */
    public static function is_feed_valid($url){
        if (!trim($url)) return false;
        return preg_match('|^http(s)?://(feeds\.)?buzzsprout\.com/[0-9]+\.rss$|i', $url);
        }
    /**
     * @desc admin notice message  
     *
     */
    public static function podcast_admin_notice(){
        global $pagenow;
        if ($pagenow == 'edit.php' && $_GET['page'] == 'podcast-setting' && $_GET['post_type'] == 'podcast') {
            if ( (isset($_GET['updated']) && $_GET['updated'] == 'true') || (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') ) {

            $errors = get_settings_errors();

            $podcasting_options = get_option('podcast-setting');
            if(!self::is_feed_valid($podcasting_options['feed-uri'])){
                $podcasting_options['feed-uri'] = '';
                update_option('podcast-setting', $podcasting_options);
                $error_message = __('Invalid Buzzsprout Feed URL');
                add_settings_error('podcast-setting', 'settings_updated', $error_message, 'error');
                } else {
                $original_message = $errors[0]['message'];
                add_settings_error('podcast-setting', 'settings_updated', $original_message, 'updated');
                }
            }
        }
    }
    /**
     * @desc setting page content  
     *
     */
    public static function options_page_content() { ?> 
        <div class="wrap">
            <form action="options.php" method="post">
                <?php settings_fields('podcast-setting'); 
                do_settings_sections('podcast-setting'); ?>
                <p>* You can find this feed url at Directories > RSS feed in your Buzzsprout dashboard.</p>
                <p class="submit">
                    <input class="button-primary" name="submit" type="submit" value="Save" />
                </p>
            </form>
        </div>
       
     <?php }
    /**
     * @desc add content of add newpage  
     *
     */
    public static function wporg_add_custom_box() {
        $screens = ['podcast'];
        foreach ($screens as $screen) {
            add_meta_box(
                    'podcast_embed_id', // Unique ID
                    'Buzzsprout Podcast Name', // Box title
                     // Content callback, must be of type callable
                    array( __CLASS__,'podcast_xml_load'), 
                    $screen  // Post type
            );

            add_meta_box(
                    'podcast_discription', // Unique ID
                    'Podcast Discription', // Box title
                    // Content callback, must be of type callable
                    array( __CLASS__,'podcast_discription_load'), 
                    $screen  // Post type
            );

            add_meta_box(
                    'podcast_transcript', // Unique ID
                    'Podcast Transcript', // Box title
                    // Content callback, must be of type callable
                    array( __CLASS__,'podcast_transcript_load'), 
                    $screen // Post type
            );
        }
    }
     /**
     * @desc fetch data on add newpage  
     */
    public static function podcast_xml_load() {
        global $post;
        $buzzsprout_options = get_option('podcast-setting');
        $podcast_id = get_post_meta($post->ID, 'podcast_embed_id', true);

        $rss = fetch_feed($buzzsprout_options['feed-uri'] . '?' . strtotime("now"));
        if (is_wp_error($rss)) {
            $rss = new SimplePie();
            $rss->set_feed_url($buzzsprout_options['feed-uri'] . '?' . strtotime("now"));
            $rss->force_feed(true); // Force feed to fix MIME type errors
            $rss->init();
            $rss->handle_content_type();
        }
        $maxitems = $rss->get_item_quantity();
        $items = $rss->get_items(0, $maxitems);
        $podcasts = array();
        $select_options = "";
        foreach ($items as $item) {
            $transcript = $item->get_item_tags("https://podcastindex.org/namespace/1.0", "transcript");
            $podcasts[$item->get_id()] = array(
                "Id" => $item->get_id(),
                "Name" => $item->get_title(),
                "Discription" => $item->get_description(),
                "Link" => $item->get_enclosure()->get_link(),
                "Transcript" => $transcript[0]["attribs"][""]["url"]
            );
            if ($podcast_id == $item->get_id()) {
                $select_options .= '<option value="' . $item->get_id() . '" selected="selected">' . $item->get_title() . '</option>';
            } else {
                $select_options .= '<option value="' . $item->get_id() . '">' . $item->get_title() . '</option>';
            }
        }
        echo "<script>var podcastlist=" . json_encode($podcasts) . "</script>";
        ?>
        <select name="podcast_embed" id="podcast_embed" onchange="load_content();">
            <option value="">---Select Podcast Episode---</option>
            <?php echo $select_options; ?>
        </select>
        <?php
    }
    /**
     * @desc fetch discription on add newpage   
     */
    public static function podcast_discription_load($post) {
        $text = get_post_meta($post->ID, 'podcast_discription_text', true);
        wp_editor(htmlspecialchars($text), 'podcast_discription_text', $settings = array('textarea_name' => 'podcast_discription_text', "textarea_rows" => 5));
    }
    /**
     * @desc fetch transcript on add newpage  
     */
    public static function podcast_transcript_load($post) {
        $text = get_post_meta($post->ID, 'podcast_transcript_text', true);
        wp_editor(htmlspecialchars($text), 'podcast_transcript_text', $settings = array('textarea_name' => 'podcast_transcript_text', "textarea_rows" => 5));
    }

    public static function podcast_save_custom_metabox() {
        global $post;
        if (isset($_POST["podcast_discription_text"])):
            update_post_meta($post->ID, 'podcast_discription_text', wp_kses($_POST["podcast_discription_text"], $allowedposttags));
        endif;
        if (isset($_POST["podcast_transcript_text"])):
            update_post_meta($post->ID, 'podcast_transcript_text', wp_kses($_POST["podcast_transcript_text"], $allowedposttags));
        endif;
        if (isset($_POST["podcast_embed"])):
            update_post_meta($post->ID, 'podcast_embed_id', sanitize_text_field($_POST["podcast_embed"]));
        endif;
    }
     /**
     * @desc add single_podcast file  
     */
    public static function my_plugin_templates($template) {
        $post_types = get_post_type();
        if ($post_types == 'podcast' && file_exists(plugin_dir_path(__FILE__) . 'templates/single_podcast.php')) {
            $template = plugin_dir_path(__FILE__) . 'templates/single_podcast.php';
        }
        return $template;
    }
     /**
     * @desc add podcast_list file
     */
    public static function get_podcast_list() {
        ob_start();
        include(plugin_dir_path(__FILE__) . 'templates/podcast_list.php');
        $content = ob_get_clean();
        return $content;
    }
}

add_action('init', 'mbpl_my_custom_post_podcast');
add_action('init', array('MBPL_Buzzsprout_Podcast', 'initialize'));
add_action('init','mbpl_my_buzz_podcast_style');