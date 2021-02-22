<?php

namespace WpRloutHtml;

use WpRloutHtml\Helpers;
use WpRloutHtml\Posts;
use WpRloutHtml\Modules\S3;
use WpRloutHtml\Modules\Ftp;
use WpRloutHtml\Essentials\Curl;

Class Terms Extends App {
	
	public function __construct(){
		
		// verifica alterações de terms
		add_action( 'create_term', array($this, 'create_folder'), 1, 1);
		add_action( 'edit_term', array($this, 'create_folder'), 1, 1);
		add_action( 'delete_term', array($this, 'delete_folder'), 1, 1);
	}
	
	public function create_folder($term_id){
		
		if($_POST['static_output_html']){

			$term = get_term($term_id);
			
			$taxonomies = explode(',', Helpers::getOption('taxonomies_rlout'));
			
			if(in_array($term->taxonomy, $taxonomies)){
				
				$slug_old = $term->slug;
				$slug_new = $_POST['slug'];
				
				if($slug_old!=$slug_new){
					
					$term->slug = $slug_new;
				}

				Curl::generate($term);

				Terms::api($term);
			}

		}
	}
	
	public function delete_folder($term_id){
		
		$term = get_term($term_id);
		
		$taxonomies = explode(',', Helpers::getOption('taxonomies_rlout'));
		
		if(in_array($term->taxonomy, $taxonomies)){
			
			$slug_old = $term->slug;
			$slug_new = $_POST['slug'];
			
			$url = str_replace(site_url(), '', get_term_link($term));
			
			if($slug_old!=$slug_new){
				
				$term->slug = $slug_new;
			}
			
			$dir_base = Helpers::getOption('path_rlout') . $url;
			
			unlink($dir_base . '/index.html');
			rmdir($dir_base);
			
			Ftp::remove_file($dir_base . '/index.html');
			S3::remove_file($dir_base . '/index.html');
			
			if(empty($deleted_term)){
				
				$objects = array($term);
				
				$this->deploy($objects);
			}

			Terms::api($term);
		}
	}
	
	static function api($term=null){
		
		header( "Content-type: application/json");
		
		$taxonomies = explode(",", Helpers::getOption('taxonomies_rlout'));
		
		$urls = array();
		
		foreach($taxonomies as $tax){
			
			$terms = get_terms(array("taxonomy"=>$tax, 'hide_empty' => false));
			
			$replace_url = Helpers::getOption('replace_url_rlout');
			if(empty($replace_url)){
				$replace_url = site_url().'/html';
			}
			
			foreach ($terms as $key => $term) {
				$term = Terms::object_term($term, false);
			}
			
			$response = json_encode($terms , JSON_UNESCAPED_SLASHES);
			
			$replace_uploads = Helpers::getOption('uploads_rlout');
			
			$uploads_url_rlout = Helpers::getOption('uploads_url_rlout'); 
			
			if($replace_uploads){
				
				$upload_url = wp_upload_dir();						
				
				$response = str_replace($upload_url['baseurl'], $replace_url.'/uploads', $response);
				
				sleep(0.5);
				
				if($uploads_url_rlout){
					$response = str_replace($uploads_url_rlout, $replace_url.'/uploads', $response);
				}
			}
			
			$dir_base =  Helpers::getOption('path_rlout');
			if( realpath($dir_base) === false ){
				mkdir($dir_base);
			}
			
			$file_raiz = $dir_base . '/'.$tax.'.json';
			
			$file = fopen($file_raiz, "w");
			
			fwrite($file, $response);
			
			$urls[] = str_replace($dir_base,$replace_url,$file_raiz);
		}
		
		return $urls;
	}

	static function object_term($object, $show_posts=true){
		
		$url = get_term_link($object);
		$ignore_json_rlout = explode(',' , Helpers::getOption('ignore_json_rlout'));
		if(empty(in_array($url, $ignore_json_rlout))){
			
			unset($object->term_group);
			unset($object->term_taxonomy_id);
			unset($object->parent);
			unset($object->filter);
			
			$object = Helpers::url_json_obj($object);
			
			$args_posts = array();
			$args_posts['post_type'] = explode(",", Helpers::getOption('post_types_rlout'));
			$args_posts['posts_per_page'] = -1;
			$args_posts['order'] = 'DESC';
			$args_posts['orderby'] = 'date';
			$args_posts['tax_query'][0]['taxonomy'] = $object->taxonomy;
			$args_posts['tax_query'][0]['terms'] = array($object->term_id);
			
			if($show_posts){
				$posts = get_posts($args_posts);
				foreach ($posts as $key_p => $post) {
					
					$post = Posts::new_params($post);
				}
				$object->posts = $posts;
			}
			
			$metas = get_term_meta($object->term_id);
			$metas_arr = array();
			foreach ($metas as $key_mm => $meta) {
				$thumb = wp_get_attachment_image_src($meta[0], 'full');
				if(!empty($thumb)){
					$sizes = get_intermediate_image_sizes();
					foreach ($sizes as $key_sz => $size) {
						$metas_arr[$key_mm][] = wp_get_attachment_image_src($meta[0], $size);
					}
				}else{
					$metas_arr[$key_mm] = $meta;
				}
			}
			
			$object->metas = $metas_arr;
			
			return $object;
		}
	}
}