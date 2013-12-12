<?php
    /**
 * Plugin Name: Revenginet
 * Plugin URI: http://revengi.net
 * Description: Wordpress plugin for a cross platform social media experience.
 * Version: 0.1
 * Author: Jesse Conner
 * Author URI: http://m-i-rite.com
 * License: GPL2
 */

 global $pre;
 global $wpdb;
 $pre = $wpdb->get_prefix();
 
require('twitter.php');
$revenginet=new revenginet();

class revenginet {

    function __construct () {
        register_activation_hook( __FILE__, 'makeTables' );
    }
    
    function makeTables() {
        global $wpdb;
        $sql = "CREATE TABLE revenginet (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
          content tinytext NOT NULL,
          source text NOT NULL
        );";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    function generate() {
        global $pre;
        global $wpdb;
    
        $t=$pre . "revenginet";
    
        $wpdb->get_results("SELECT * FROM $t");
    
        foreach($post as $post)
        {
    
            $new_post = array(
            'post_title' => 'From Revenginet',
            'post_content' => $post->content,
            'post_status' => 'publish',
            'post_date' => $post->date,
            'post_author' => $post->source,
            'post_type' => 'post',
            'post_category' => array(0)
            );
        
            $post_id = wp_insert_post($new_post);

        }
    
    }
    
    function add($content, $source, $date) {
        global $wpdb;
        $id=$wpdb->insert("$wpdb->prefix.revenginet", array("content"=>$content, "date"=>$date, "source"=>source));
        return $id;
    }
}
?>