<?php
/*
Plugin Name: Post to Dev.to
Plugin URI: https://wordpress.org/plugins/post-to-dev-to/
Description: Convert a post to markdown, formatted for Dev.to
Version: 0.0.2
Author: Richard Keller
Author URI: https://richardkeller.net 
License: GPL2
Tags: markdown
*/
require 'vendor/autoload.php';
use League\HTMLToMarkdown\HtmlConverter;

class RBK_Post_Two_Dev_To {
    public function __construct() {
      add_action('init', array($this, 'init'));
    }
    public function init() {
        if(!is_admin() && is_user_logged_in() && current_user_can('publish_posts')) {
            add_action('wp_enqueue_scripts', array($this, 'plugin_assets'));
            add_action('rest_api_init', array($this, 'plugin_register_markdown'));
            add_action('wp_footer', array($this, 'plugin_add_elements'));
        }   
    }
    public function plugin_assets() {
        $cache = '?t='.rand();
        wp_enqueue_script('rbk-markdown-popup', plugin_dir_url( __FILE__ ) . 'markdown.popup.js' . $cache, array(), true);
        wp_enqueue_style('rbk-markdown-popup', plugin_dir_url( __FILE__ ). 'markdown.popup.css' . $cache);
        wp_localize_script('rbk-markdown-popup', 'rbkMarkdownJs', array(
            'post_id' => get_the_id(),
            'post_type' => get_post_type()
        ));
    }

    public function plugin_convert($request_data) {
        $params = $request_data->get_params();
        $id = $params['id'];
        $request = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $id );
        $response = rest_do_request( $request );
        $server = rest_get_server();
        $data = $server->response_to_data( $response, false );
        // $json = wp_json_encode($data);
        // $response = new WP_REST_Response($data);
        // $response->header("Content-Type", "application/json");
        // return $response;
        $html = $data['content']['rendered'];
        $converter = new HtmlConverter(array('strip_tags' => true));
        $converter->getConfig()->setOption('hard_break', true);
        $markdown = $converter->convert($html);
        $markdown =  str_replace('<pre class="wp-block-code">```', '', $markdown);
        $markdown =  preg_replace('/```\n/', '```', $markdown);
        $markdown =  preg_replace('/``````/', "```\n\n", $markdown);

        $title = strip_tags($data['title']['rendered']);
        $excerpt = trim(strip_tags($data['excerpt']['rendered']));
        $url = $data['link'];
        $tags = [];
        $image = "";
        $image_url = wp_get_attachment_image_src( get_post_thumbnail_id($id), 'full' );
        if (isset($image_url[0])) {
            $image = $image_url[0];
        } 

        foreach($data['tags'] as $id) {
            $term = get_term($id);
            if ($term) {
                $tags[] = $term->name;
            }
        }
        foreach($data['categories'] as $id) {
            $term = get_term($id);
            if ($term) {
                $tags[] = $term->name;
            }
        }

        $tags = implode(", ", $tags);


        $markdown_header = "
---
title: $title 
published: true
description: $excerpt
tags: $tags
canonical_url: $url
cover_image: $image
---\n\n";

        $markdown = $markdown_header . $markdown;
        $response = new WP_REST_Response(['markdown' => trim($markdown)]);
        $response->header("Content-Type", "application/json");
        $response->set_status(200);
        return $response;
    }

    public function plugin_register_markdown() {
        register_rest_route('markdown', '(?P<id>[\\d]+)', array(
            'methods'  => ['GET'],
            'callback' => array($this, 'plugin_convert'),
            'args' => ['id']
        ));
    }
    public function plugin_add_elements() {
        echo '
            <div class="rbk-markdown">
                <div class="rbk-markdown--button">DEV</div>
                <div class="rbk-markdown--button rbk-markdown--button--close">&times;</div>
                <textarea class="rbk-markdown--popup"></textarea>
            </div>
        ';
    }
}
new RBK_Post_Two_Dev_To();

?>