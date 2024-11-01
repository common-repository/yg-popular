<?php
class yg_popular_admin {

	/**
	 * @access   protected
	 * @var      string    $version
	 */
	protected $version;

	/**
     * Array to store duration values
     * @access   protected
     * @var      array    $options
     */
	protected $options;

	/**
     * Overide all duration functionality
     * @access   protected
     * @var      string    $no_duration
     */
	protected $no_duration;

	public function __construct($version,$options,$tablename,$meta_view,$meta_view_dur1,$meta_view_dur2) {
		$this->version = $version;
		$this->options = $options;
		$this->no_duration = !yg_popular::yg_pop_hasDuration();
		$this->tablename = $tablename;
		$this->meta_view = $meta_view;
		$this->meta_view_dur1 = $meta_view_dur1;
		$this->meta_view_dur2= $meta_view_dur2;

		add_filter('is_protected_meta',array(&$this,'yg_protected_meta_filter'), 10, 2);
		add_action('admin_menu',array(&$this, 'yg_add_plugin_page'));
        add_action('admin_init',array(&$this, 'yg_pop_page_init'));
        add_action('wp_dashboard_setup',array(&$this,'yg_pop_dashboard_widget'));

		add_action('wp_ajax_yg_update_post_count', array(&$this,'yg_update_post_count'));
        add_action('wp_ajax_yg_re_rec_from', array(&$this,'yg_re_rec_from'));
        add_action('wp_ajax_yg_get_cpt_terms', array(&$this,'yg_get_cpt_terms'));
        add_action('wp_ajax_yg_get_terms_vals', array(&$this,'yg_get_terms_vals'));
        add_action('wp_ajax_yg_dashwg_get_res', array(&$this,'yg_dashwg_get_res'));
        add_action('wp_ajax_yg_pop_get_posts', array(&$this,'yg_pop_get_posts'));
        add_action('wp_ajax_yg_pop_clr_wdgtcache', array(&$this,'yg_pop_clr_wdgtcache'));
	}

	/* hide from custom field view */
	public function yg_protected_meta_filter($protected, $meta_key) {
    	if ( $this->meta_view_dur1 == $meta_key || $this->meta_view_dur2 == $meta_key || $this->meta_view == $meta_key ) return true;
        return $protected;
	}
	/**
     * Add options page
     */
    public function yg_add_plugin_page(){
        // This page will be under "Settings"
        add_options_page(
            'Pop-u-lar post settings', 
            'Pop-u-lar post settings', 
            'manage_options', 
            'yg_pop_settings', 
            array( $this, 'create_yg_pop_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_yg_pop_admin_page(){
        wp_register_style('yg_pop_admin_css', plugin_dir_url( dirname(__FILE__) ) . 'css/yg_popular_admin.min.css');
        wp_enqueue_style('yg_pop_admin_css');
        wp_register_style('ui_datepicker_css', plugin_dir_url( dirname(__FILE__) ) . 'css/jquery-ui.min.css');
        wp_enqueue_style('ui_datepicker_css');
		wp_register_script('yg_pop_admin_js', plugin_dir_url( dirname(__FILE__) ) . 'js/yg_pop_admin.min.js', array('jquery'));
		wp_enqueue_script('yg_pop_admin_js');
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('jquery-ui-sortable', array('jquery'));
        wp_enqueue_script('jquery-ui-autocomplete', array('jquery'));
        ?>
        <div class="wrap option-wrap">
            <h2 class="pop-main-ttl"><div class="pop-logo"></div>Pop-u-lar post settings</h2>
            <h3 class="red">General settings</h3>
            <p>Post view table is <?php echo $this->yg_getTableSize($this->tablename); ?> MB, date range: <?php echo $this->yg_getTableDateRange($this->tablename); ?></p>
            <p>Post view table is used for most popular in a certain date range. It doesn’t need data that is further then your largest date range.</p>
            <p>Delete all records before <input type="text" name="class_date_value" value="" class="datepick" id="rec_date" /></p>
            <a href="" class="button button-primary" id="remove_rec" pop_nonce="<?php echo wp_create_nonce('pop-u-lar-remove-rec2468') ?>">Delete records</a>
            <form method="post" action="options.php">
            <?php
                // Settings for durations
                settings_fields( 'yg_pop_options' );
                echo '<input type="hidden" id="cache_widget" name="yg_pop_dur_options[cache_widget]" value="'.esc_attr( $this->options['cache_widget']).'" />';
                echo '<input type="hidden" id="pop_from_dur" name="pop_from_dur" value="true" />';
                do_settings_sections( 'yg_pop_settings' );
                submit_button();
            ?>
            </form>
            <h3 class="yellow">Post view settings</h3>
            <div class="pop_tbl">
                <p>Most popular posts</p>
                <div id="most_pop_tbl_div">
                <?php echo $this->getPopularTblHtml(6); ?>
                </div>
            </div>
            <?php if(isset($this->options['pop_w_duration']) && $this->options['pop_w_duration']>0){ ?>
            <div class="pop_tbl">
                <p>Most popular posts in the last <?php echo ($this->options['pop_w_duration']>1)?$this->options['pop_w_duration'].' days':'day' ?></p>
                <div id="most_pop_tbl_div2">
                <?php echo $this->getPopularTblHtml(6,$this->meta_view_dur1); ?>
                </div>
            </div>
            <?php } ?>
            <?php if(isset($this->options['pop_w_duration2']) && $this->options['pop_w_duration2']>0){ ?>
            <div class="pop_tbl">
                <p>Most popular posts in the last <?php echo ($this->options['pop_w_duration2']>1)?$this->options['pop_w_duration2'].' days':'day' ?></p>
                <div id="most_pop_tbl_div3">
                <?php echo $this->getPopularTblHtml(6,$this->meta_view_dur2); ?>
                </div>
            </div>
            <?php } ?>
            <h2 style="clear:both;">Manually set post view count</h2>
            <?php if((isset($this->options['pop_w_duration']) && $this->options['pop_w_duration']>0) || (isset($this->options['pop_w_duration2']) && $this->options['pop_w_duration2']>0)){ ?>
            <p>If you have most popular in X days set up, it will count past views and enter the remaining views with todays date, leaving historical data untouched.<br />
            If you are setting views to a smaller number then current views it will erase historical data for selected post and enter new view count with todays date.</p>
            <?php } ?>
            <p>Select a post by typing it's title, you can limit results by setting post type.</p>
            <form id="update_count">
            <?php wp_nonce_field('pop-u-lar-add-views2468'); ?>
            <input type="hidden" id="yg_pop_add_v_post_id" name="yg_pop_add_v_post_id" value="">
            <input type="text" id="yg_pop_add_v_post_name" name="yg_pop_add_v_post_name" value="" ttl="" views="">
            <select id="yg_pop_filter_ptype">
            	<option value="">All post types</option>
            <?php
            $ptype_excld = array('attachment','revision','nav_menu_item');
            foreach (get_post_types('','names' ) as $post_type){
                if(!in_array($post_type,$ptype_excld))
                    echo '<option value="' . $post_type . '">', $post_type, '</option>';
            }
			/*$args = array(
				'posts_per_page' => -1,
				'post_type' => 'any',
				'post_status' => 'publish',
				'orderby' => 'title',
				'order' => 'ASC'
				);
			$allPosts = get_posts($args);
			foreach($allPosts as $apost){
				$views = (get_post_meta($apost->ID, $this->meta_view,true)!='')?get_post_meta($apost->ID, $this->meta_view,true):0; ?>
            	<option value="<?php echo $apost->ID ?>" p_count="<?php echo $views ?>" ttl="<?php echo str_replace('"','&quot;',$apost->post_title); ?>">
                    <?php echo $apost->post_type ?> id:<?php echo $apost->ID ?> - <?php echo $apost->post_title ?> <?php echo date('M. jS, Y',strtotime($apost->post_date)) ?> (<?php echo $views ?> views)
                </option>
            <?php }*/ ?>
            </select>
            <p>Enter view count<br /><input type="text" id="yg_pop_allpost_count" name="yg_pop_allpost_count" value="">
            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Set views"></p>
            </form>
            <form method="post" action="options.php" class="opt-form2">
            <?php
                // Settings for durations
                settings_fields( 'yg_pop_options' );
                echo '<input type="hidden" id="pop_duration_disabl" name="yg_pop_dur_options[pop_duration_disabl]" value="'.$this->options['pop_duration_disabl'].'" />';
                echo '<input type="hidden" id="pop_w_duration" name="yg_pop_dur_options[pop_w_duration]" value="'.esc_attr( $this->options['pop_w_duration']).'" />';
                echo '<input type="hidden" id="pop_w_duration2" name="yg_pop_dur_options[pop_w_duration2]" value="'.esc_attr( $this->options['pop_w_duration2']).'" />';
                do_settings_sections( 'yg_pop_cache_settings' );
                submit_button();
            ?>
            </form>
            <p>If you want to bust, remove all Pop-u-lar widget cache, click below</p>
            <p><a href="" class="button button-primary" id="clr_wdgt_cache" pop_nonce="<?php echo wp_create_nonce('pop-u-lar-clr-wdgt-cache2468') ?>">Cleare widget cache</a></p>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function yg_pop_page_init(){        
        register_setting(
            'yg_pop_options',
            'yg_pop_dur_options',
            array($this,'yg_pop_sanitize')
        );

        add_settings_section(
            'yg_pop_gen_options',
            'Most popular time range settings',
            array($this,'yg_pop_print_section_info'),
            'yg_pop_settings'
        );

        add_settings_section(
            'yg_pop_cache_options',
            'Widget cache settings',
            array($this,'yg_pop_print_cache_section_info'),
            'yg_pop_cache_settings'
        );

        add_settings_field(
            'pop_duration_disabl',
            'Disable duration',
            array($this,'yg_pop_duration_disabl_callback'),
            'yg_pop_settings',
            'yg_pop_gen_options'
        );
        add_settings_field(
            'pop_w_duration',
            'Set day range 1',
            array($this,'yg_pop_w_duration_callback'),
            'yg_pop_settings',
            'yg_pop_gen_options'
        );
		add_settings_field(
            'pop_w_duration2',
            'Set day range 2',
            array($this,'yg_pop_w_duration_two_callback'),
            'yg_pop_settings',
            'yg_pop_gen_options'
        );
        add_settings_field(
            'cache_widget',
            'Set cache duration',
            array($this,'yg_pop_cache_widget_callback'),
            'yg_pop_cache_settings',
            'yg_pop_cache_options'
        );
    }

    /**
     * Sanitize duration options
     */
    public function yg_pop_sanitize($input){
        $new_input = array();
        if( isset( $input['pop_duration_disabl'] ) && $input['pop_duration_disabl']=='yes' ){
            $new_input['pop_duration_disabl'] = $input['pop_duration_disabl'];
            $input['pop_w_duration'] = 0;
            $input['pop_w_duration2'] = 0;
            if($_REQUEST['pop_from_dur'] == 'true')
                add_settings_error('pop_duration_disabl','duration-disable','You turned off duration. View dates will not be recorded.');
        }

        if( isset( $input['pop_w_duration'] ) )
            $new_input['pop_w_duration'] = absint( $input['pop_w_duration'] );

		if( isset( $input['pop_w_duration2'] ) )
            $new_input['pop_w_duration2'] = absint( $input['pop_w_duration2'] );

        if( isset( $input['cache_widget'] ) )
            $new_input['cache_widget'] = absint( $input['cache_widget'] );

        if($_REQUEST['pop_from_dur'] == 'true'){//update meta only if we came from duration section
            $dur1 = (isset($input['pop_w_duration']) && $input['pop_w_duration']!='')?$input['pop_w_duration']:0;
            $dur2 = (isset($input['pop_w_duration2']) && $input['pop_w_duration2']!='')?$input['pop_w_duration2']:0;
            yg_popular::yg_updatePostDurMeta($dur1,$dur2);
        }
        return $new_input;
    }
    
    public function yg_pop_print_section_info(){
        print 'Enter range for most popular in the last X days.<br />You can set two different settings. Or leave empty or set to 0 to not use.<br />Disable duration (most popular in last X days) to make application work faster. Note that you will not be recording view dates.<br />Storing and recording view dates will start only once duration is enabled.';
    }

    public function yg_pop_print_cache_section_info(){
        print 'If you want to cache widgets on your site enter duration in minutes.<br />Leave empty or set to 0 for no caching.';
    }

    public function yg_pop_duration_disabl_callback(){
        $chcked = (isset($this->options['pop_duration_disabl']) && $this->options['pop_duration_disabl']=='yes')?' checked="checked"':'';
        echo '<input type="checkbox" id="pop_duration_disabl" name="yg_pop_dur_options[pop_duration_disabl]" value="yes"'.$chcked.' />';
    }

    public function yg_pop_w_duration_callback(){
        $disabled = (isset($this->options['pop_duration_disabl']) && $this->options['pop_duration_disabl']=='yes');
        printf(
            '<input type="text" id="pop_w_duration" name="yg_pop_dur_options[pop_w_duration]" value="%s"%s />',
            (isset($this->options['pop_w_duration']) && !$disabled) ? esc_attr( $this->options['pop_w_duration']) : '',
            ($disabled)?' disabled':''
        );
    }
	public function yg_pop_w_duration_two_callback(){
        $disabled = (isset($this->options['pop_duration_disabl']) && $this->options['pop_duration_disabl']=='yes');
        printf(
            '<input type="text" id="pop_w_duration2" name="yg_pop_dur_options[pop_w_duration2]" value="%s"%s />',
            (isset($this->options['pop_w_duration2']) && !$disabled) ? esc_attr( $this->options['pop_w_duration2']) : '',
            ($disabled)?' disabled':''
        );
    }
    public function yg_pop_cache_widget_callback(){
        printf(
            '<input type="number" class="tiny-text" id="cache_widget" name="yg_pop_dur_options[cache_widget]" value="%s" min="0" step="1" />',
            (isset($this->options['cache_widget'])) ? esc_attr( $this->options['cache_widget']) : '');
    }

    /**
     * Dashboard widget
     */

    public function yg_pop_dashboard_widget(){
        global $wp_meta_boxes;
        wp_add_dashboard_widget('yg_pop_dashboard_widget','Pop-u-lar post',array(&$this,'yg_pop_show_dboard_widget'));

        $normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
        $example_widget_backup = array('yg_pop_dashboard_widget' => $normal_dashboard['yg_pop_dashboard_widget']);
        unset($normal_dashboard['yg_pop_dashboard_widget']);

        $sorted_dashboard = array_merge( $example_widget_backup, $normal_dashboard );

        $wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
    }

    public function yg_pop_show_dboard_widget(){
        wp_register_style('yg_pop_admin_dashbrd_css', plugin_dir_url( dirname(__FILE__) ) . 'css/yg_popular_admin_dashbrd.min.css');
        wp_enqueue_style('yg_pop_admin_dashbrd_css');
        wp_register_script('yg_pop_admin_dshbrd_js', plugin_dir_url( dirname(__FILE__) ) . 'js/yg_pop_admin_dshbrd.min.js', array('jquery'));
        wp_enqueue_script('yg_pop_admin_dshbrd_js');

        echo '<p class="cnvs_ttl">Most popular posts</p>'."\n";
        echo '<script type="text/javascript">
        var postsJsObj ={';

        $popPost = yg_popular::yg_getPopularPosts(5,yg_popular::yg_view_meta_name);
        $count = array();
        $ttl = array();
        foreach ($popPost as $ppost) {
            $count[] = get_post_meta($ppost->ID,yg_popular::yg_view_meta_name,true);
            $ttl[] = strip_tags($ppost->post_title);
        }
        echo 'all:{count:'.json_encode($count).',ttl:'.json_encode($ttl).'}';
        echo '}
        </script>';

        $ptype_excld = array('attachment','revision','nav_menu_item');
        ?>
        <p id="dw_frm">Show most popular posts of type 
        <select name="dw_ptype" id="dw_ptype">
            <?php
            echo '<option value="any">', 'All post types', '</option>';
            foreach (get_post_types('','names') as $post_type){
                if(!in_array($post_type,$ptype_excld))
                echo '<option value="' . $post_type . '"', '>', $post_type, '</option>';
            }
            ?>
        </select><br />
        in the last 
        <input name="dw_dur" id="dw_dur" type="number" value="0" step="1" min="0" class="tiny-text" />
        days (set to 0 for all time most popular).<br />
        <a href="" class="button button-primary" id="dw_getres" pop_nonce="<?php echo wp_create_nonce('pop-u-lar-dashboardwig2468') ?>">Submit</a></p>
        <?php
    }

    /**
     * Helper functions
     */

    public function yg_getTableSize($tbl){
		global $wpdb;
		$tableSize = $wpdb->get_results(
			"SELECT table_name,round(((data_length + index_length) / 1024 / 1024), 2) 'size'
			FROM information_schema.TABLES 
			WHERE table_schema = '".$wpdb->dbname."'
			AND table_name = '".$tbl."';"
		);
		return $tableSize[0]->size;
	}
    
    public function yg_getTableDateRange($tbl){
        global $wpdb;
        $q="SELECT MIN(date) AS `from`,MAX(date) AS `to` FROM $this->tablename;";
        $res = $wpdb->get_results($q);
        $fromTo = (is_array($res) && $res[0]->from!='' && $res[0]->from!=null)?date('M. jS, Y',strtotime($res[0]->from)).' to '.date('M. jS, Y',strtotime($res[0]->to)):'';
        return $fromTo;
    }

    public function getPopularTblHtml($rows,$key='views_total'){
		$html='<table id="trans_tbl" class="widefat importers">
            	<tbody>
                	<tr>
                        <th>ID</th>
                    	<th>Type</th>
                        <th>Title</th>
                        <th>Views</th>
                    </tr>';
            
		$popPost = yg_popular::yg_getPopularPosts($rows,$key);
		$i=0;
		foreach($popPost as $ppost){
			$class=($i%2==0)?' class="alternate"':'';
			$html.='<tr'. $class .'>
                        <th>'.$ppost->ID.'</th>
                        <th>'.$ppost->post_type.'</th>
                    	<th>'.$ppost->post_title.'</th>
                        <th>'.get_post_meta($ppost->ID, $key,true).'</th>
                    </tr>'; 
			$i++;
		}
        $html.='</tbody>
            </table>';
		return $html;
	}

    /**
     * AJAX functions
     */

    public function yg_update_post_count(){
        global $wpdb;
		$a=array();
		if(isset($_REQUEST['postid']) && isset($_REQUEST['view_count']) && $_REQUEST['postid']!='' && $_REQUEST['view_count']!=''){
            $postid = intval($_REQUEST['postid']);
			$views += $_REQUEST['view_count'];//make int
			update_post_meta($_REQUEST['postid'],$this->meta_view,$views);
            if((isset($this->options['pop_w_duration']) && $this->options['pop_w_duration']>0) || (isset($this->options['pop_w_duration2']) && $this->options['pop_w_duration2']>0)){
                $table_name = $this->tablename;
                $records = $wpdb->get_var("SELECT SUM(count) FROM $table_name WHERE post_id=$postid");
                //if new views>current views leave historic data, count it and add remainder with todays date/time
                //if new views<current erase historic data and add new views with todays date/time
                if($records<$views){
                    $dlta = $views - $records;
                    $wpdb->insert($table_name, array('post_id' => $postid, 'date' => current_time('mysql'), 'count' => $dlta));
                    yg_popular::yg_updatePostDurMeta($this->options['pop_w_duration'],$this->options['pop_w_duration2']);
                }elseif($records>$views){
                    $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE post_id=%d",$postid));
                    $wpdb->insert($table_name, array('post_id' => $postid, 'date' => current_time('mysql'), 'count' => $views));
                    yg_popular::yg_updatePostDurMeta($this->options['pop_w_duration'],$this->options['pop_w_duration2']);
                }
            }
			$a['mssg']='"'.stripslashes(str_replace('"','&quot;',$_REQUEST['postttl'])).'" has been updated';
			$a['tbl']=$this->getPopularTblHtml(6);
            if(isset($this->options['pop_w_duration']) && $this->options['pop_w_duration']>0)
                $a['tbl2']=$this->getPopularTblHtml(6,$this->meta_view_dur1);
            if(isset($this->options['pop_w_duration2']) && $this->options['pop_w_duration2']>0)
                $a['tbl3']=$this->getPopularTblHtml(6,$this->meta_view_dur2);
			print_r(json_encode($a));
		}
		exit;
	}
    public function yg_re_rec_from(){
        global $wpdb;
        if(isset($_REQUEST['refrom']) && $_REQUEST['refrom']!='' && check_admin_referer('pop-u-lar-remove-rec2468')){
            $from_date = date('Y-m-d',strtotime($_REQUEST['refrom']));
            $res = $wpdb->query($wpdb->prepare("DELETE FROM $this->tablename WHERE date<%s;",$from_date));
            echo ($res>0)?'Table updated':'No rows were updated';
        }
        exit;
    }
    public function yg_get_cpt_terms(){
        if(isset($_REQUEST['cpt']) && $_REQUEST['cpt']!='' && check_admin_referer('pop-u-lar-cpt-select2468')){
            echo json_encode(yg_popular::yg_pop_get_terms_by_cpt($_REQUEST['cpt']));
        }
        exit;
    }
    public function yg_get_terms_vals(){
        if(isset($_REQUEST['pop_term']) && $_REQUEST['pop_term']!='' && check_admin_referer('pop-u-lar-txnm-select2468')){
            require_once plugin_dir_path( __FILE__ ) . '../inc/yg-class-walker-tax.php';
            $walker = new YG_Walker_Category;
            $val_array = get_terms($_REQUEST['pop_term'],array());
            $dd = $walker->walk($val_array,0,array('style'=>'ddlist'));
            echo json_encode($dd);
        }
        exit;
    }
    public function yg_dashwg_get_res(){
        global $wpdb;
        if(check_admin_referer('pop-u-lar-dashboardwig2468')){
            $res = array();
            
            if(isset($_REQUEST['pdur']) && $_REQUEST['pdur']!='' && $_REQUEST['pdur']!=0){
                $table_name = $this->tablename;
                $dt = date("Y-m-d H:i:s", time() - (intval($_REQUEST['pdur'])*24*60*60));
                $res['days'] = ' in the last '  .  (($_REQUEST['pdur']>1)?$_REQUEST['pdur'] . ' days':'day');

                $q = "SELECT v.post_id `post_id`,p.post_title `ttl`,sum(v.count) as `views`";
                $q .= " FROM $table_name `v`,$wpdb->posts `p` WHERE p.ID=v.post_id AND p.post_status='publish' AND v.date>'".$dt."'";
                if($_REQUEST['ptype'] != 'any'){
                    $q .= " AND p.post_type='".$_REQUEST['ptype']."'";
                    $res['type'] = ' of type '.$_REQUEST['ptype'];
                }
                $q .= " GROUP BY v.post_id ORDER BY `views` DESC LIMIT 5;";
            }else{//if no date range just take post meta
                $q = "SELECT p.ID `post_id`,p.post_title `ttl`,m.meta_value `views`
                FROM $wpdb->posts `p`
                LEFT JOIN $wpdb->postmeta `m` ON p.ID=m.post_id AND m.meta_key='views_total'
                WHERE 1=1";
                if($_REQUEST['ptype'] != 'any'){
                    $q .= " AND p.post_type='".$_REQUEST['ptype']."'";
                    $res['type'] = ' of type '.$_REQUEST['ptype'];
                }
                $q .= " ORDER BY m.meta_value+0 DESC LIMIT 5;";
            }
            
            
            $views = $wpdb->get_results($q);
            foreach ($views as $ppost) {
                if($ppost->views>0){
                    $res['count'][] = intval($ppost->views);
                    $res['ttl'][] = $ppost->ttl;
                }
            }
            echo json_encode($res);
        }
        exit;
    }
    public function yg_pop_get_posts(){
        global $wpdb;
        $postArray=array();
        if(isset($_REQUEST['term']) && $_REQUEST['term']!=''){
            //$postArray=array('term'=>$_REQUEST['term'],'suggestions'=>array());
            $q = "SELECT ID,post_title,post_date FROM $wpdb->posts WHERE post_status='publish' AND post_title LIKE '%".$_REQUEST['term']."%'";
            if(isset($_REQUEST['fltr']) && $_REQUEST['fltr']!='')
                $q .= " AND post_type='".$_REQUEST['fltr']."'";
            $views = $wpdb->get_results($q);
            foreach($views as $ppost){
                $v = (get_post_meta($ppost->ID, $this->meta_view,true)!='')?get_post_meta($ppost->ID, $this->meta_view,true):0;
                $ttl = $ppost->post_title.' - '.date('m/d/y',strtotime($ppost->post_date)).' ('.$v.' views)';
                $postArray[] = array('label'=>$ttl,'value'=>$ppost->post_title,'id'=>$ppost->ID,'views'=>$v);
            }
            print_r(json_encode($postArray));
        }
        exit;
    }
    public function yg_pop_clr_wdgtcache(){
        global $wpdb;
        if(check_admin_referer('pop-u-lar-clr-wdgt-cache2468')){
            $q = "SELECT option_name FROM $wpdb->options WHERE option_name LIKE '_transient_yg_pop_most_pop_post_widget%';";
            $trans = $wpdb->get_col($q);
            $i = 0;
            foreach($trans as $tran){//use transient API for compatibility with memcache if enabled
                delete_transient(str_replace('_transient_', '', $tran));
                $i++;
            }
            echo 'Cache has been deleted for '.$i.' widgets';
        }
        exit;
    }
}