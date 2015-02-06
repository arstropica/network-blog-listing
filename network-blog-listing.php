<?php
    /*
    Plugin Name: WP Network Blog Listing
    Plugin URI: http://arstropica.com
    Description: List Child Blogs and Recent Posts in an MU
    Version: 1.0
    Author: ArsTropica <info@arstropica.com>
    Author URI: http://arstropica.com
    */
    /*ini_set('display_errors', 1);
    error_reporting(E_ALL);*/

    global $wp_network_blog_listing;
    
    require_once('network-blog-listing/class.php');
    
    function network_blog_listing_shortcode($atts){
        global $wp_network_blog_listing; 
        $output = "<div class='network_blog_listing'>\n";
        $blogs_arry = $wp_network_blog_listing->shortcodize($atts);
        if (! empty($blogs_arry)){
            $output .= implode("\n", $blogs_arry);
        } else {
            $output .="<p>No Blogs were found.</p>\n";
        }
        $output .= "</div>\n";
        return $output;
    }
    add_shortcode('list_blogs', 'network_blog_listing_shortcode');

    add_action( 'init', 'wp_network_blog_listing_init');

    function wp_network_blog_listing_init(){
        global $wp_network_blog_listing;
        if (class_exists("wp_network_blog_listing") && empty($wp_network_blog_listing)) {
            $wp_network_blog_listing = new wp_network_blog_listing();
        }    
    }
?>