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

register_activation_hook( __FILE__, 'makeTables');
add_action('revenginet_add_post', 'add', 1, 3);
add_action( 'plugins_loaded', 'generate' );

function makeTables() {
    global $wpdb;
    $t=$wpdb->prefix . "revenginet";
    $sql = "CREATE TABLE $t (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        content tinytext NOT NULL,
        source text NOT NULL,
        UNIQUE KEY id (id)
    );";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

function generate() {
    global $wpdb;
    
    $t=$wpdb->prefix. "revenginet";
    
    $wpdb->get_results("TRUNCATE $t");
    
    do_action("revenginet_update");
  
    
    $posts=$wpdb->get_results("SELECT * FROM $t");
    
   /* foreach($posts as $post)
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

    }*/
    
}
    
function add($content, $source, $date) {
  $date = date('Y-m-d H:i:s', strtotime(str_replace('-', '/', $date)));
    global $wpdb;
    $t=$wpdb->prefix . "revenginet";
    $id=$wpdb->insert($t, array("content"=>$content, "date"=>$date, "source"=>$source));
    return $id;
}

?>