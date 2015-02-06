<?php

    // Definitions
    define('NBL_PLUGIN_FILE', __FILE__);
    define('NBL_PLUGIN_BASENAME', plugin_basename(__FILE__));
    define('NBL_PLUGIN_PATH', trailingslashit(dirname(__FILE__)));
    define('NBL_PLUGIN_DIR', plugins_url('/', __FILE__));
    if ( !defined( 'WP_PLUGIN_DIR' ) )
        define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

    global $wp_network_blog_listing;
    
    class wp_network_blog_listing{

        protected $options;
        public $menupos;

        function wp_network_blog_listing(){
            $this->menupos = 0.995;
            add_action( 'admin_init', array( &$this, 'nbl_settings_init'));
            add_action( 'admin_head' , array( &$this, 'screen_icon' ) );
            add_action( 'admin_menu' , array( &$this, 'nbl_menu' ), 10 );
            add_action( 'admin_menu' , array( &$this, 'nbl_edit_menu' ), 100 );
            add_action( 'admin_print_scripts-toplevel_page_nbl_options', array(&$this,'nbl_admin_scripts'));
            add_action( 'admin_print_styles-toplevel_page_nbl_options', array(&$this,'nbl_admin_styles'));
            add_action( 'wp_print_styles', array(&$this, 'nbl_styles'));
        }

        function shortcodize($options=null){
            $this->options = array(
            'exclude' => 1,
            'include' => '',
            'sort' => 'blog_name',
            'num' => 'all',
            'start' => 0,
            'order' => 'ASC',
            'post_orderby' => 'modified',
            'post_order' => 'ASC',
            'latest_post' => '1'
            );    
            if ($options){
                $this->options = wp_parse_args($options, $this->options);
            }
            return $this->get_blog_posts();
        }

        function nbl_admin_scripts() {
            wp_enqueue_script('media-upload');
            wp_enqueue_script('thickbox');
            wp_enqueue_script('jquery');
        }        

        function nbl_admin_styles() {
            $style_dir = NBL_PLUGIN_DIR . 'css/';
            wp_enqueue_style('nbl-admin-stylesheet', $style_dir . 'nbl-admin-style.css');
            wp_enqueue_style('thickbox');
        }
        
        function nbl_styles(){
            $style_dir = NBL_PLUGIN_DIR . 'css/';
            wp_enqueue_style('nbl-stylesheet', $style_dir . 'nbl-style.css');
        }

        function nbl_menu(){
            $this->page = add_menu_page( "Network Blog Listing Settings", "NBL Options", "manage_options", "nbl_options", array(&$this, "nbl_page"), NBL_PLUGIN_DIR . 'images/icon_16.png', $this->menupos);
        }

        function nbl_edit_menu(){
            global $menu, $submenu;
            if (isset($menu[$this->menupos])){
                $menu[$this->menupos][2] = "options-general.php?page=nbl_options";
            }
        }

        function nbl_page(){
            if (!function_exists('is_admin')) {
                header('Status: 403 Forbidden');
                header('HTTP/1.1 403 Forbidden');
                exit();
            }
            $action = '';
            $location = "admin.php?page=nbl_options"; // based on the location of your sub-menu page

            $message = 'Settings Updated.';
            $options = get_option('nbl_options');
        ?>
        <script language="JavaScript">
            jQuery(document).ready(function() {
                jQuery('.upload_button').click(function() {
                    uploadID = jQuery('input.nbl_upload'); // grabs the correct field
                    spanID = jQuery(this).closest('FORM').find('span#previewimg1'); // grabs the correct span
                    formfield = jQuery('.nbl_upload').attr('name');
                    tb_show('', 'media-upload.php?type=image&TB_iframe=true');
                    return false;
                });
                                                     
                window.send_to_editor = function(html) {
                    spanID.html(html); // sends the IMG tag to the preview span
                    imgurl = spanID.find('IMG').attr('src'); // grabs the image URL from the IMG tag
                    uploadID.val(spanID.find('IMG').attr('src')); // sends the image URL to the hidden input field
                    tb_remove();
                }
                
                jQuery("#nbl_avatar_reset").click(function(){
                    jQuery('span#previewimg1').html('');
                    jQuery('input.nbl_upload').val('');
                    return false;
                });

            });
        </script>
        <?php            
            screen_icon('nbl_options');
            echo "<h1>Network Blog Listing Settings</h1>\n";
            echo "<div class=\"wrap\">\n";
        ?>     
        <form name="nbl_options" id="nbl_options" action="options.php" method="post">
            <?php settings_fields('nbl_options'); ?>
            <div class="nbl_avatar_wrap nbl_wrap">
                <?php do_settings_sections('nbl_avatar'); ?>
            </div>
            <div class="nbl_details_wrap nbl_wrap">
                <?php do_settings_sections('nbl_details'); ?>
            </div>
            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
            </p>
        </form>
        <?php
            echo "</div>\n";
        }

        function screen_icon() { ?>
        <style type="text/css">
            #icon-nbl_options { background-image: url('<?php echo NBL_PLUGIN_DIR . '/images/icon_32.png'; ?>'); background-repeat: no-repeat; }
        </style>
        <?php
        }

        function nbl_settings_init() {

            register_setting(
            'nbl_options',                                           // settings page
            'nbl_options',                                           // option name
            array(&$this, 'nbl_options_validate')                    // validation
            );

            add_settings_section(
            'nbl_settings_avatar',                                      // section name
            'Blog Avatar',                                              // description
            array(&$this, 'nbl_settings_avatar_callback'),              // callback
            'nbl_avatar');                                              // page

            add_settings_section(
            'nbl_settings_details',                                     // section name
            'Blog Details',                                             // description
            array(&$this, 'nbl_settings_details_callback'),             // callback
            'nbl_details');                                             // page

            add_settings_field(
            'nbl_settings_avatar_image',                            // id
            'Upload Avatar Image',                                  // setting title
            array(&$this, 'nbl_settings_avatar_image_callback'),    // display callback
            'nbl_avatar',                                           // settings page
            'nbl_settings_avatar'                                   // settings section
            );

            add_settings_field(
            'nbl_settings_details_city',                                // id
            'Blog City',                                                // setting title
            array(&$this, 'nbl_settings_details_city_callback'),        // display callback
            'nbl_details',                                              // settings page
            'nbl_settings_details'                                      // settings section
            );

            add_settings_field(
            'nbl_settings_details_state',                               // id
            'Blog State',                                              // setting title
            array(&$this, 'nbl_settings_details_state_callback'),     // display callback
            'nbl_details',                                              // settings page
            'nbl_settings_details'                                      // settings section
            );

            add_settings_field(
            'nbl_settings_details_website',                             // id
            'Website URL',                                              // setting title
            array(&$this, 'nbl_settings_details_website_callback'),     // display callback
            'nbl_details',                                              // settings page
            'nbl_settings_details'                                      // settings section
            );

            add_settings_field(
            'nbl_settings_details_richtext',                            // id
            'Blog Details',                                             // setting title
            array(&$this, 'nbl_settings_details_richtext_callback'),    // display callback
            'nbl_details',                                              // settings page
            'nbl_settings_details'                                      // settings section
            );

        }

        function nbl_options_validate($input){
            $options = get_option( 'nbl_options' );
            if (! empty($input['nbl_settings_details_richtext'])){
                if (! is_serialized($input['nbl_settings_details_richtext'])){
                    $tmp = $input['nbl_settings_details_richtext'];
                    $input['nbl_settings_details_richtext'] = serialize($tmp);
                }
            }
            return $input;
        }

        function nbl_settings_avatar_callback() {
            echo '<h4>Avatar Settings</h4>';
        }

        function nbl_settings_details_callback() {
            echo '<h4>Blog Information</h4>';
        }

        function nbl_settings_avatar_image_callback() {
            $options = get_option('nbl_options');
            $image_url = $options['nbl_settings_avatar_image'];
            $image = (empty($image_url)) ? "" : "<img src='" . $image_url . "' />";
            $output  = "<table>\n";
            $output .= "<tr valign='top'>\n";
            $output .= "     <th scope='row'>Upload Image</th>\n";
            $output .= "     <td><label for='upload_image'>\n";
            $output .= "         <span id='previewimg1'>$image</span>\n";
            $output .= "         <div id='nbl_avatar_buttons'>\n";
            $output .= "            <input id='upload_image' type='hidden' name='nbl_options[nbl_settings_avatar_image]' class='nbl_upload' value='$image_url' />\n";
            $output .= "            <input id='upload_image_button' type='button' value='Upload Image' class='upload_button' />\n";
            $output .= "            <input type='button' id='nbl_avatar_reset' value='Clear Image' class='reset_button' />\n";
            $output .= "         </div>\n";
            $output .= "     </label></td>\n";
            $output .= "</tr>\n";
            $output .= "</table>\n";
            echo $output;
        }

        function nbl_settings_details_city_callback() {
            $options = get_option('nbl_options');
            echo '<input name="nbl_options[nbl_settings_details_city]" id="nbl_settings_details_city" type="text" value="' . $options['nbl_settings_details_city'] . '" class="regular-text" /> Enter Blog City';
        }

        function nbl_settings_details_state_callback() {
            $options = get_option('nbl_options');
            echo '<input name="nbl_options[nbl_settings_details_state]" id="nbl_settings_details_state" type="text" value="' . $options['nbl_settings_details_state'] . '" class="regular-text" /> Enter Blog State';
        }

        function nbl_settings_details_website_callback() {
            $options = get_option('nbl_options');
            echo '<input name="nbl_options[nbl_settings_details_website]" id="nbl_settings_details_website" type="text" value="' . $options['nbl_settings_details_website'] . '" class="regular-text" /> Enter website URL (http(s):// ...)';
        }

        function nbl_settings_details_richtext_callback() {
            $options = get_option('nbl_options');
            $tmp = @$options['nbl_settings_details_richtext'];
            if (is_serialized($tmp)){
                $richtext = unserialize($tmp);
            } else{
                if(!isset($options['nbl_settings_details_richtext'])){
                    $richtext = "";
                } else {
                    $richtext = @$options['nbl_settings_details_richtext'];
                }
            }
            echo '<textarea name="nbl_options[nbl_settings_details_richtext]" id="nbl_settings_details_richtext" style="height: 450px; width: 100%;">' . $richtext . '</textarea>';
            echo '<div style="clear:both; width: 100%; height: 0px;"></div>';
        }

        function get_blog_list(){
            global $wpdb;
            $options = $this->options;
            extract( $options, EXTR_SKIP );
            $select = "SELECT blog_id, domain, path, registered, last_updated FROM $wpdb->blogs ";
            $where = "WHERE site_id = %d AND public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0'";
            if (! empty($exclude)) {
                $ex_arry = preg_split("/[,\s]/", $exclude);
                $exclude = implode(",", $ex_arry);
                $where .= " AND blog_id NOT IN ($exclude)";
            }
            if (! empty($include)) {
                $inc_arry = preg_split("/[,\s]/", $include);
                $include = implode(",", $inc_arry);
                $where .= " AND blog_id NOT IN ($include)";
            }
            $order = " ORDER BY registered DESC";
            $query = $select . $where . $order;
            $blogs = $wpdb->get_results( $wpdb->prepare($query, $wpdb->siteid), ARRAY_A );

            foreach ( (array) $blogs as $details ) {
                $blog_list[ $details['blog_id'] ] = $details;
                $blog_list[ $details['blog_id'] ]['postcount'] = $wpdb->get_var( "SELECT COUNT(ID) FROM " . $wpdb->get_blog_prefix( $details['blog_id'] ). "posts WHERE post_status='publish' AND post_type='post'" );
            }
            unset( $blogs );
            $blogs = $blog_list;

            if ( false == is_array( $blogs ) )
                return array();

            if ( $num == 'all' )
                return array_slice( $blogs, $start, count( $blogs ) );
            else
                return array_slice( $blogs, $start, $num );
        }

        function sort_blogs($blog_array, $sort='blog_name', $order='ASC'){
            $sort_options = array('blogid', 'blog_name', 'blog_domain', 'blog_path', 'registered', 'last_updated');
            $sort = strtolower($sort);
            $order = strtoupper($order);
            if (! in_array($sort, $sort_options)) $sort = 'blog_name';
            $sort_order = ($order == 'ASC') ? SORT_ASC : SORT_DESC;
            $ordered_array = array();
            foreach ($blog_array as $key => $row) {
                $ordered_array[$key]  = $row[$sort];
            }
            array_multisort($ordered_array, $sort_order, $blog_array);
            return $blog_array;
        }

        function get_blogs_details(){
            $blog_list = $this->get_blog_list();
            $i = 0;
            $blog_array = array();
            $options = $this->options;
            $sort = $options['sort'];
            $order = $options['order'];
            
            foreach ($blog_list as $blog) {
                $b_id=$blog['blog_id'];
                $b_name=get_blog_option($b_id, 'blog_name');
                $b_domain = $blog['domain'];
                $b_path = $blog['path'];
                $use_name = ltrim($b_name);
                $blog_array[$i]["blogid"] = $b_id;
                $blog_array[$i]["blog_name"] = $use_name;
                $blog_array[$i]["blog_domain"] = $b_domain;
                $blog_array[$i]["blog_path"] = $b_path;
                $blog_array[$i]["registered"] = $blog['registered'];
                $blog_array[$i]["last_updated"] = $blog['last_updated'];
                $i ++;
            }
            
            $sorted_blog_array = $this->sort_blogs($blog_array, $sort, $order);
            return $sorted_blog_array;
        }

        function get_latest_child_post($blog_id) {
            global $current_blog, $current_site, $post;
            $saved_post = $post;
            $options = $this->options;
            $post_order = (strtolower($options['post_order']) == 'asc') ? 'ASC' : 'DESC';
            $post_orderby = strtolower($options['post_orderby']);
            $orderby_options = array('modified', 'rand', 'title', 'menu_order');
            $display_posts = $options['latest_post'];
            if (! in_array($post_orderby, $orderby_options)) $post_orderby = 'modified';

            $curr_blog_id = $current_blog->blog_id;
            if ($blog_id != $curr_blog_id) {
                switch_to_blog($blog_id);
            }
            $nbl_options = get_option('nbl_options');
            $image_src = empty($nbl_options['nbl_settings_avatar_image']) ? plugins_url('network-blog-listing/images/default.jpg', dirname(__FILE__)) : $nbl_options['nbl_settings_avatar_image'];
            $city = empty($nbl_options['nbl_settings_details_city']) ? false : $nbl_options['nbl_settings_details_city'];
            $state = empty($nbl_options['nbl_settings_details_state']) ? false : $nbl_options['nbl_settings_details_state'];
            $website = empty($nbl_options['nbl_settings_details_website']) ? false : $nbl_options['nbl_settings_details_website'];
            $details = is_serialized($nbl_options['nbl_settings_details_richtext']) ? unserialize($nbl_options['nbl_settings_details_richtext']) : (empty($nbl_options['nbl_settings_details_richtext']) ? false : $nbl_options['nbl_settings_details_richtext']);

            $blogurl = home_url('/');
            $blogname = get_bloginfo('name');

            $timthumb = plugins_url('network-blog-listing/includes/timthumb.php?zc=2&w=80&h=80&src=', dirname(__FILE__));
            $image_src = $timthumb . str_replace(trailingslashit(home_url()).'files/', network_site_url('/').'wp-content/blogs.dir/'.$blog_id.'/files/', $image_src);;
            $output = "";
            $sticky = get_option('sticky_posts');
            $args = array('post__not_in'=>$sticky, 'showposts'=>1, 'orderby' => $post_orderby, 'order' => $post_order);
            $child_loop = new WP_Query($args);
            if ($child_loop->have_posts()) : while($child_loop->have_posts()) :
                    $child_loop->the_post();
                    $output .= "<div id=\"postblock-" . get_the_ID() . "\" class=\"childblog\">\n";
                    if ( ! empty($image_src)) $output .= "<div class=\"blog_avatar\"><a target=\"_blank\" href=\"$blogurl\"><img src=\"$image_src\" alt=\"$blogname\" title=\"$blogname\" /></a></div>\n";
                    $output .= "<div class=\"wrap\"" . ( ! empty($image_src) ? " style='padding-left: 100px;'" : "") . ">\n";
                    $output .= "<div class=\"posthead\"><h2>$blogname" . "<span class='location'>" . ( ! empty($city) ? $city . ", " : false) . ( ! empty($state) ? $state : false) .  "</span></h2>\n";
                    $output .= "<ul class=\"sitelinks\"><li><a target=\"_blank\" href=\"$blogurl\">Blog</a></li>\n";
                    if (! empty($website)) $output .= "<li>|</li><li><a target=\"_blank\" href=\"$website\">Website</a></li>\n";        
                    $output .= "</ul>\n";
                    if (! empty($details)) $output .= "<div class='nbl_details'><p>$details</p></div>\n";        
                    $output .= "</div>\n";
                    if ($display_posts == '1') {
                        $output .= "<div class=\"postbody\">\n";
                        $output .= "<div class=\"posttitle\">\n";
                        $output .= "<h3><a target=\"_blank\" href=\"" . get_permalink() . "\">" . get_the_title() . "</a></h3>\n";
                        $output .= "</div>\n";
                        $output .= "<div class=\"excerpt\">\n";
                        #$output .= apply_filters('the_excerpt', get_the_excerpt());
                        $output .= wpautop(apply_filters('get_the_excerpt', $post->post_excerpt));
                        $output .= "<br style='width: 100%; height: 0px; clear: both;' /><a target='_blank' href='" . get_permalink() . "'>Read More</a>";
                        $output .= "</div>\n";
                        $output .= "</div>\n";
                    }
                    $output .= "</div>\n";
                    $output .= "<div style='clear:both; width: 100%; height: 0px;'></div>\n";
                    $output .= "</div>\n";
                    endwhile; endif;
            restore_current_blog();
            wp_reset_query();
            $post = $saved_post; 
            return $output;
        } 

        function get_blog_posts(){
            $sorted_blog_array = $this->get_blogs_details();
            $blogs_post_array = array();
            foreach ($sorted_blog_array as $blog){
                $blogs_post_array[] = $this->get_latest_child_post($blog['blogid']);
            }
            return $blogs_post_array;
        }
        
        function get_ms_option($blogID, $option_name) {
            global $wpdb;

            $select_statement = "SELECT *
                    FROM `".DB_NAME."`.`".$wpdb->get_blog_prefix($blogID)."options`
                    WHERE `option_name` LIKE '".$option_name."'";
            $wpdb->show_errors();
            
            $sql = $wpdb->prepare($select_statement);
            $option_value = $wpdb->get_var( $sql, 3 );
            return $option_value;
        }        

    }

?>