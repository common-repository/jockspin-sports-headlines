<?php
/*
 Plugin Name: Jockspin Sports Headlines
 Plugin URI: http://jockspin.com
 Description: A plugin that adds a widget to your blog that shows sports headlines from different categories.
 Version: 0.5
 Author: James Hawkins
 Author URI: http://jockspin.com
 License: GPL2

 Release notes: Version 0.3 Initial release.

 Copyright 2012 James Hawkins (email: dever@jockspin.com)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License, version 2, as
 published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

class JockspinSportsHeadlines extends WP_Widget {

	function JockspinSportsHeadlines() {
		$widget_ops = array('classname' => 'jockspin-sports-headlines', 'description' => __( 'The most recent sports headlines on your site') );
		$this->WP_Widget('JockspinSportsHeadlines', __('Jockspin Sports Headlines'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract( $args );
		$title = $instance['title'];
		echo $before_widget;
		echo $before_title . $title . $after_title;
		?>

		<?php
		$settingsChanged = 0;
		$updateHeadlines = dirname(__FILE__).'/cache/updated.txt';
		if(file_exists($updateHeadlines)){
			$updateInfo = file_get_contents($updateHeadlines);
			$updateInfo = unserialize($updateInfo);
			if( (isset($updateInfo['numposts']) && $updateInfo['numposts'] != $updateInfo['old_numposts']) || (isset($updateInfo['cat']) && $updateInfo['cat'] != $updateInfo['old_cat']) || (isset($updateInfo['newwindow']) && $updateInfo['newwindow'] != $instance['old_newwindow'])){
				$settingsChanged = 1;
				$updateInfo['old_cat'] = $updateInfo['cat'];
				$updateInfo = serialize($updateInfo);
				file_put_contents(dirname(__FILE__)."/cache/updated.txt", print_r($updateInfo, true));
			}
		}
		$cacheHeadlines = dirname(__FILE__).'/cache/jockspin-headlines.txt';
		if(file_exists($cacheHeadlines) && (time() - filemtime($cacheHeadlines) <= 3600) && $settingsChanged == 0){
			$headlines = file_get_contents($cacheHeadlines);
		} else{
			$post_data = array (
				"category_id" => $instance['cat'],
				"num_posts"   => $instance['numposts'],
				"new_window"  => $instance['newwindow'],
				"action"      => "getSportsHeadlines"
			);
			$sourceUrl = 'http://jockspin.com/jockspin-sports-headlines-custom.php';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $sourceUrl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_REFERER, $_SERVER['REQUEST_URI']);
			$headlines = curl_exec($ch);
			curl_close($ch);
			if(!is_dir(dirname(__FILE__).'/cache')){
				mkdir(dirname(__FILE__).'/cache', 0777);
			}
			if(is_dir(dirname(__FILE__).'/cache')){
				file_put_contents($cacheHeadlines, $headlines);
			}
		}
		if ($headlines !== FALSE) {
			echo $headlines;
		}
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['numposts'] = $new_instance['numposts'];
		$instance['cat'] = $new_instance['cat'];
		$instance['newwindow']      = $new_instance['newwindow'];
		$oldValues['old_title'] = $old_instance['title'];
		$oldValues['old_numposts'] = $old_instance['numposts'];
		$oldValues['old_cat'] = $old_instance['cat'];
		$oldValues['old_newwindow'] = $old_instance['newwindow'];
		$instance_changes = array_merge($new_instance, $oldValues);
		$instance_changes = serialize($instance_changes);
		file_put_contents(dirname(__FILE__)."/cache/updated.txt", print_r($instance_changes, true));
		return $instance;
	}
	function form( $instance ) {
		// Widget defaults
		if(empty($instance['old_numposts']))
			$instance['old_numposts'] = 5;

		if(empty($instance['cat']))
			$instance['cat'] = "";

		if(empty($instance['old_cat']))
			$instance['old_cat'] = "113-cat";


		$MAX_HEADLINES_ALLOWED = 10;
		$settingsChanged = 0;
		$updateHeadlines = dirname(__FILE__).'/cache/updated.txt';
		if(file_exists($updateHeadlines)){
			$updateInfo = file_get_contents($updateHeadlines);
			$updateInfo = unserialize($updateInfo);
			if( (isset($updateInfo['numposts']) && $updateInfo['numposts'] != $instance['old_numposts']) || (isset($updateInfo['cat']) && $updateInfo['cat'] != $instance['old_cat']) || (isset($updateInfo['newwindow']) && $updateInfo['newwindow'] != $instance['old_newwindow'])){
				$settingsChanged = 1;
			}
		}
		$cacheCategoryList = dirname(__FILE__).'/cache/jockspin-categories.txt';
		if(file_exists($cacheCategoryList) && (time() - filemtime($cacheCategoryList) <= 3600) && $settingsChanged == 0){
			$categoryList = file_get_contents($cacheCategoryList);
		} else{
			$post_data = array (
				"category_id" => $instance['cat'],
				"dropdown_name" => $this->get_field_name('cat'),
			    "action" => "getCategoryList"
			);
			$sourceUrl = 'http://jockspin.com/jockspin-sports-headlines-custom.php';
			$ch1 = curl_init();
			curl_setopt($ch1, CURLOPT_URL, $sourceUrl);
			curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch1, CURLOPT_POST, 1);
			curl_setopt($ch1, CURLOPT_POSTFIELDS, $post_data);
			curl_setopt($ch1, CURLOPT_HEADER, 0);
			curl_setopt($ch1, CURLOPT_REFERER, $_SERVER['REQUEST_URI']);
			$categoryList = curl_exec($ch1);
			curl_close($ch1);
			if(!is_dir(dirname(__FILE__).'/cache')){
				mkdir(dirname(__FILE__).'/cache', 0777);
			}
			if(is_dir(dirname(__FILE__).'/cache')){
				file_put_contents($cacheCategoryList, $categoryList);
			}
		}

		$instance = wp_parse_args( (array) $instance, array(
			'title' => 'Jockspin Sports',
			'numposts' => 5,
			'cat' => 0,
			'newwindow' => 'off'
		) ); ?>
		<?php if($instance['newwindow'] == 'on'){
			$set_window_options = 'checked = "checked"';
		} else{
			$set_window_options = '';
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('numposts'); ?>"><?php _e('Number of headlines to show:'); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('numposts'); ?>" name="<?php echo $this->get_field_name('numposts'); ?>">
				<?php for ($i=1; $i <= $MAX_HEADLINES_ALLOWED; $i++) { ?>
					<option value="<?php echo $i; ?>" <?php if($i == $instance['numposts']) echo "selected"; ?>><?php echo $i; ?></option>
				<?php } ?>
			</select>
		</p>


		<p>
			<label for="<?php echo $this->get_field_id('cat'); ?>"><?php _e('Choose Category: '); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('cat'); ?>" name="<?php echo $this->get_field_name('cat'); ?>">

			<?php
				if ($categoryList !== FALSE) {
					echo $categoryList;
				}
				?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('newwindow'); ?>"><?php _e('Open links in new window:'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('newwindow'); ?>" name="<?php echo $this->get_field_name('newwindow'); ?>" type="checkbox" <?php checked(isset($instance['newwindow']) ? $instance['newwindow'] : 0); echo $set_window_options; ?> />
		</p>
		<?php
	}
}

function jockspin_sports_headlines() {
	register_widget('JockspinSportsHeadlines');
}

add_action('widgets_init', 'jockspin_sports_headlines');


?>