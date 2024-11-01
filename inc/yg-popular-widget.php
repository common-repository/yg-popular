<?php
class yg_pop_most_pop_post_widget extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$widget_ops = array( 
			'classname' => 'yg_pop_most_pop_post_widget',
			'description' => 'Show most popular posts by views, comments or tags',
		);
		parent::__construct( 'yg_pop_most_pop_post_widget', 'Pop-u-lar post', $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget($args, $instance){
		extract($args);
        $title = apply_filters('widget_title', $instance['title']);
        echo $before_widget;
        if($title)
			echo $before_title . $title . $after_title;
		?>
		<ul>
		<?php
			$rows = (isset($instance['num_of_posts']))?$instance['num_of_posts']:3;
			$ptype = (isset($instance['ptype_select']))?$instance['ptype_select']:'any';
			$pop_tax = (isset($instance['pop_tax']))?$instance['pop_tax']:'';
			$pop_tax_val = (isset($instance['pop_tax_val']))?$instance['pop_tax_val']:'';
			if(false !== ($posts = get_transient($this->id))){

			}else{
				switch ($instance['select_by']) {
					case 'views':
						$posts = yg_popular::yg_getPopularPosts($rows,$instance['dur_select'],$ptype,$pop_tax,$pop_tax_val);
						break;
					case 'comments':
						$posts = yg_popular::yg_getPopularPostsComments($rows,$ptype,$instance['dur_select'],$pop_tax,$pop_tax_val);
						break;
					case 'tags':
						$posts = yg_popular::yg_getPopularPostsTags($rows,$ptype);
						break;
					case 'most recent':
						$posts = yg_popular::yg_getPopularPosts($rows,'recent',$ptype,$pop_tax,$pop_tax_val);
						break;
					default:
						$posts = yg_popular::yg_getPopularPosts($rows,false,$instance['dur_select'],$ptype,$pop_tax,$pop_tax_val);
						break;
				}
				$cacheDur = yg_popular::yg_getCacheDuration();
				if($cacheDur!=0)
					set_transient($this->id,$posts, $cacheDur * 60);
			}
			foreach ($posts as $post){
		?>
			<li>
				<a href="<?php echo $post->guid ?>"><?php echo $post->post_title ?></a>
			</li>
		<?php
		}
		?>
		</ul>
		<?php
		echo $after_widget;
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form($instance){
		wp_register_style('yg_pop_admin_css', plugin_dir_url( dirname(__FILE__) ) . 'css/yg_popular_admin.min.css');
        wp_enqueue_style('yg_pop_admin_css');
		wp_register_script('yg_pop_admin_js', plugin_dir_url( dirname(__FILE__) ) . 'js/yg_pop_admin_widget.min.js', array('jquery'));
		wp_enqueue_script('yg_pop_admin_js');
		$title = esc_attr($instance['title']);
		?>
		<p>
          <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
          <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <?php
        $select_by = esc_attr($instance['select_by']);
		?>
		<p>
        <label for="<?php echo $this->get_field_id('select_by'); ?>"><?php _e('Select most popular by'); ?></label>
        <select name="<?php echo $this->get_field_name('select_by'); ?>" id="<?php echo $this->get_field_id('select_by'); ?>" class="widefat pop_select_by">
            <?php
            $select_by_array = array('views','comments','tags','most recent');
            foreach ($select_by_array as $slct_option){
                echo '<option value="' . $slct_option . '"', $select_by == $slct_option ? ' selected="selected"' : '', '>', $slct_option, '</option>';
            }
            ?>
        </select>
    	</p>
    	<?php
		$durEnabled = yg_popular::yg_pop_hasDuration();
		$durOne = yg_popular::yg_getDuration();
		$durTwo = yg_popular::yg_getDuration2();
		if($durEnabled && ($durOne>0 || $durTwo>0)){//only if duration is available
			$dur_select = esc_attr($instance['dur_select']);
		?>
		<p>
        <label for="<?php echo $this->get_field_id('dur_select'); ?>"><?php _e('Select most popular in last x days'); ?></label>
        <select name="<?php echo $this->get_field_name('dur_select'); ?>" id="<?php echo $this->get_field_id('dur_select'); ?>" class="widefat pop_dur_select"<?php if($select_by=='tags' || $select_by=='most recent') echo ' disabled'; ?>>
            <?php
            $options[yg_popular::yg_view_meta_name] = 'all time';
            if($durOne>0)
            	$options[yg_popular::yg_dur1_meta_name] = ($durOne>1)?'last ' . $durOne . ' days':'last day';
            if($durTwo>0)
            	$options[yg_popular::yg_dur2_meta_name] = ($durTwo>1)?'last ' . $durTwo . ' days':'last day';
            foreach ($options as $key=>$option) {
                echo '<option value="' . $key . '"', $dur_select == $key ? ' selected="selected"' : '', '>', $option, '</option>';
            }
            ?>
        </select>
    	</p>
    	<?php
    	}
    	$ptype_select = esc_attr($instance['ptype_select']);
    	$ptype_excld = array('attachment','revision','nav_menu_item');
		?>
		<p>
        <label for="<?php echo $this->get_field_id('ptype_select'); ?>"><?php _e('Select post type to show'); ?></label>
        <select name="<?php echo $this->get_field_name('ptype_select'); ?>" id="<?php echo $this->get_field_id('ptype_select'); ?>" class="widefat pop_ptype_select" pop_nonce="<?php echo wp_create_nonce('pop-u-lar-cpt-select2468') ?>">
            <?php
            echo '<option value="any"', $ptype_select == 'any' ? ' selected="selected"' : '', '>', 'All post types including pages', '</option>';
            foreach (get_post_types('','names' ) as $post_type){
            	if(!in_array($post_type,$ptype_excld))
                echo '<option value="' . $post_type . '"', $ptype_select == $post_type ? ' selected="selected"' : '', '>', $post_type, '</option>';
            }
            ?>
        </select>
    	</p>
    	<?php
    	$pop_tax = esc_attr($instance['pop_tax']);
    	$taxnms = yg_popular::yg_pop_get_terms_by_cpt($ptype_select);
		?>
		<div class="pop-tax-slct">
		<p>Select taxonomy below to further filter popular posts. Like top posts in a specific category. Note this will only work if you chose both a taxonomy and value.</p>
		<p>
        <label for="<?php echo $this->get_field_id('pop_tax'); ?>"><?php _e('Taxonomy'); ?></label>
        <select name="<?php echo $this->get_field_name('pop_tax'); ?>" id="<?php echo $this->get_field_id('pop_tax'); ?>" class="widefat pop_tax_select" pop_nonce="<?php echo wp_create_nonce('pop-u-lar-txnm-select2468') ?>"<?php if($select_by=='tags') echo ' disabled'; ?>>
            <?php
            echo '<option value=""', $ptype_select == '' ? ' selected="selected"' : '', '>', 'None', '</option>';
            foreach ($taxnms as $taxnm){
                echo '<option value="' . $taxnm->taxonomy . '"', $pop_tax == $taxnm->taxonomy ? ' selected="selected"' : '', '>', $taxnm->taxonomy, '</option>';
            }
            ?>
        </select>
    	</p>
    	<?php
    	require_once plugin_dir_path( __FILE__ ) . 'yg-class-walker-tax.php';
    	$pop_tax_val = esc_attr($instance['pop_tax_val']);
    	$val_array = (isset($instance['pop_tax']) && $instance['pop_tax']!='')?get_terms($instance['pop_tax'],array()):array();
		?>
		<p>
        <label for="<?php echo $this->get_field_id('pop_tax_val'); ?>"><?php _e('Taxonomy value'); ?></label>
        <select name="<?php echo $this->get_field_name('pop_tax_val'); ?>" id="<?php echo $this->get_field_id('pop_tax_val'); ?>" class="widefat pop_taxval_select"<?php if($select_by=='tags') echo ' disabled'; ?>>
            <?php
            echo '<option value=""', $ptype_select == '' ? ' selected="selected"' : '', '>', 'None', '</option>';
            if(!empty($val_array)){
	            $walker = new YG_Walker_Category;
	    		print_r($walker->walk($val_array,0,array('style'=>'ddlist','selected'=>$pop_tax_val)));
	    	}
            ?>
        </select>
    	</p>
    	</div>
    	<?php
    	$num_of_posts = intval($instance['num_of_posts']);
		?>
		<p>
          <label for="<?php echo $this->get_field_id('num_of_posts'); ?>"><?php _e('Number of posts to show:'); ?></label>
          <input class="tiny-text" id="<?php echo $this->get_field_id('num_of_posts'); ?>" name="<?php echo $this->get_field_name('num_of_posts'); ?>" type="number" value="<?php echo $num_of_posts; ?>" step="1" min="1" />
        </p>
        <?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update($new_instance, $old_instance){
		global $wpdb;
		$instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['select_by'] = strip_tags($new_instance['select_by']);
        $instance['dur_select'] = strip_tags($new_instance['dur_select']);
        $instance['ptype_select'] = strip_tags($new_instance['ptype_select']);
        $instance['pop_tax'] = strip_tags($new_instance['pop_tax']);
        $instance['pop_tax_val'] = strip_tags($new_instance['pop_tax_val']);
        $instance['num_of_posts'] = intval($new_instance['num_of_posts']);
        //cleare the widget's transient if exists
        delete_transient($this->id);
        return $instance;
	}
}

function register_yg_pop_most_pop_post() {
  register_widget('yg_pop_most_pop_post_widget');
}
add_action('widgets_init', 'register_yg_pop_most_pop_post');
?>