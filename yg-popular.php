<?php
/**
 *
 * @link              http://www.ysgdesign.com/
 * @since             1.0.0
 * @package           Pop-u-lar
 *
 * @wordpress-plugin
 * Plugin Name:       Pop-u-lar post
 * Plugin URI:        http://www.ysgdesign.com/
 * Description:       Sort posts by popularity based on view counts, comments or tags. You can sort on all time most popular, or set 2 custom date ranges (most popular in the last 10 days etc.)
 * Version:           1.0.2
 * Author:            Yair Gelb
 * Author URI:        http://www.ysgdesign.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

/*  Copyright 2016 yair gelb
*/

class yg_popular {
    /**
     * @access   protected
     * @var      string    $plugin_name
     */
    protected $plugin_name;

    /**
     * @access   protected
     * @var      string    $version
     */
    protected $version;

    /**
     * Array to store duration values
     * @access   protected
     * @var      array    $options('pop_duration_disabl'=>,'pop_w_duration'=>,'pop_w_duration2'=>,'cache_widget'=>)
     */
    protected $options;

    /**
     * Array to store duration values
     * @access   protected static
     * @var      array    $_options
     */
    protected static $_options;

    /**
     * Bypass all duration functionality
     * @access   protected
     * @var      boolean    $no_duration
     */
    protected $no_duration;

    /**
     * @access   protected
     * @var      string    $tablename
     */
    protected static $tablename;

    const yg_view_meta_name = 'views_total';

    const yg_dur1_meta_name = 'views_last_x_day';

    const yg_dur2_meta_name = 'views_last_y_day';

    public function __construct() {
        global $wpdb;
        $this->plugin_name = 'pop-U-lar post';
        $this->version = '1.0.0';
        $this->options = get_option('yg_pop_dur_options');
        self::$_options = $this->options;
        $this->no_duration = (isset($this->options['pop_duration_disabl']) && $this->options['pop_duration_disabl']=='yes');
        
        self::$tablename = $wpdb->prefix . 'postview';

        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $curr_ver = get_option( "pop-u-lar_version" );
        if($curr_ver != $this->version) {
            $table_name = self::$tablename;
            $sql = "CREATE TABLE $table_name (
              id bigint(20) NOT NULL AUTO_INCREMENT,
              post_id bigint(20) NOT NULL,
              count bigint(16) NOT NULL default 1,
              date datetime NOT NULL default '0000-00-00 00:00:00',
              UNIQUE KEY id (id)
              );";
            
            dbDelta($sql);
            update_option("pop-u-lar_version",$this->version);
        }

        add_action('wp', array(&$this,'yg_pop_add_view'));
        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(&$this,'yg_add_action_links'));
        if(is_admin()){
            require_once plugin_dir_path( __FILE__ ) . 'admin/yg-popular-admin.php';
            $plugin_admin = new yg_popular_admin($this->version,$this->options,self::$tablename,self::yg_view_meta_name,self::yg_dur1_meta_name,self::yg_dur2_meta_name);
        }

        require_once plugin_dir_path( __FILE__ ) . 'inc/yg-popular-widget.php';

        /**
         * set cron to update post meta
         */
        if (!wp_next_scheduled('yg_updatePostDurMeta_hook')){
            wp_schedule_event( time(), 'hourly', 'yg_updatePostDurMeta_hook' );
        }

        add_action( 'yg_updatePostDurMeta_hook', array(&$this,'yg_updatePostDurMeta_cron'));
    }

    /**
     * Add setting link
     */
    public function yg_add_action_links ($links){
        $stLink = array('<a href="' . admin_url( 'options-general.php?page=yg_pop_settings' ) . '">Settings</a>');
        return array_merge( $links, $stLink );
    }
    /**
     * Adds view data on single view
     */
    public function yg_pop_add_view(){
        global $post,$wpdb;
        if((is_single() || is_page()) && is_main_query()){
            $views = (get_post_meta($post->ID, self::yg_view_meta_name,true)!='')?get_post_meta($post->ID, self::yg_view_meta_name,true):0;
            $views++;
            update_post_meta($post->ID,self::yg_view_meta_name,$views);
            if(!$this->no_duration){
                $table_name = self::$tablename;
                $wpdb->insert($table_name, array('post_id' => $post->ID, 'date' => current_time('mysql'), 'count' => 1));
                if(isset($this->options['pop_w_duration']) && $this->options['pop_w_duration']>0){
                    $views = (get_post_meta($post->ID, self::yg_dur1_meta_name,true)!='')?get_post_meta($post->ID, self::yg_dur1_meta_name,true):0;
                    $views++;
                    update_post_meta($post->ID,self::yg_dur1_meta_name,$views);
                }
                if(isset($this->options['pop_w_duration2']) && $this->options['pop_w_duration2']>0){
                    $views = (get_post_meta($post->ID, self::yg_dur2_meta_name,true)!='')?get_post_meta($post->ID, self::yg_dur2_meta_name,true):0;
                    $views++;
                    update_post_meta($post->ID,self::yg_dur2_meta_name,$views);
                }
            }
        }
    }

    public function yg_updatePostDurMeta_cron() {
        $dur1 = (isset(self::$_options['pop_w_duration']) && self::$_options['pop_w_duration']>0)?self::$_options['pop_w_duration']:0;
        $dur2 = (isset(self::$_options['pop_w_duration2']) && self::$_options['pop_w_duration2']>0)?self::$_options['pop_w_duration2']:0;
        self::yg_updatePostDurMeta($dur1,$dur2);
    }

    /**
     * Helper functions
     */

    /**
     * Gets most popular posts of all time or in a period via post meta data
     *
     * @param int $rows number of records to return.
     * @param string $key which post meta to base query on.
     * @param string $ptype post type to filter.
     * @param string $tax_query taxonomy term to filter.
     * @param string $tax_query_val taxonomy value to filter.
     * @param string $cache whether to cache query, should be true unless placed in transient for widgets.
     * @param string $from if you only want posts from a certain date.
     *
     */
    public static function yg_getPopularPosts($rows,$key=self::yg_view_meta_name,$ptype='any',$tax_query='',$tax_query_val='',$cache=true,$from=false){
        $args = array(
            'posts_per_page' => $rows,
            'post_type' => $ptype,
            'post_status' => 'publish',
            'orderby' => 'meta_value_num',
            'meta_key' => $key,
            'meta_value' => 0,
            'meta_compare' => '>',
            'order' => 'DESC',
            'no_found_rows' => true,
            'cache_results' => $cache,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => $cache
        );
        if($key=='recent'){
            $args['orderby'] = 'date';
            unset($args['meta_key']);
            unset($args['meta_value']);
            unset($args['meta_compare']);
        }
        if($from){
            list($y,$m,$d) = preg_split( '/[-\.\/ ]/', $from );
            if(checkdate($m,$d,$y)){
                $args['date_query'] = array('after' => $from);
            }
        }
        if($tax_query!=''){
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $tax_query,
                    'field' => 'slug',
                    'terms' => $tax_query_val,
                )
            );
        }
        return get_posts($args);
    }

    /**
     * Gets most popular posts of all time or in a period via number of comments
     *
     * @param int $rows number of records to return.
     * @param string $ptype post type to filter.
     * @param string $dur if set to one of duration meta values it will look back only that number of days.
     * @param string $pop_tax taxonomy term to filter.
     * @param string $pop_tax_val taxonomy value to filter.
     *
     */
    public static function yg_getPopularPostsComments($rows,$ptype='any',$dur='',$pop_tax='',$pop_tax_val=''){
        global $wpdb;
        $q = "SELECT p.ID,p.post_title,p.guid FROM $wpdb->comments `c`,$wpdb->posts `p`";
        if($pop_tax!='' && $pop_tax_val!=''){
            $q .= " INNER JOIN wp_clean.wp_term_relationships `tr` ON tr.object_id = p.ID 
            INNER JOIN wp_clean.wp_term_taxonomy `tt` ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy='" . $pop_tax ."'
            INNER JOIN wp_clean.wp_terms `t` ON t.term_id = tt.term_id AND t.slug='" . $pop_tax_val . "'";
        }
        $q .= " WHERE p.ID=c.comment_post_ID AND c.comment_approved=1 AND p.post_status='publish'";
        if($ptype!='any')
            $q .= " AND p.post_type='".$ptype."'";
        if($dur==self::yg_dur1_meta_name){
            $_dur = date("Y-m-d H:i:s", time() - (intval(yg_popular::yg_getDuration())*24*60*60));
            $q .= " AND c.comment_date>'".$_dur."'";
        }elseif($dur==self::yg_dur2_meta_name){
            $_dur = date("Y-m-d H:i:s", time() - (intval(yg_popular::yg_getDuration2())*24*60*60));
            $q .= " AND c.comment_date>'".$_dur."'";
        }
        $q .= " GROUP BY c.comment_post_ID ORDER BY COUNT(c.comment_ID) DESC LIMIT $rows;";

        return $wpdb->get_results($q);
    }

    /**
     * Gets most popular posts via number tags
     *
     * @param int $rows number of records to return.
     * @param string $ptype post type to filter.
     *
     */
    public static function yg_getPopularPostsTags($rows,$ptype='any'){
        global $wpdb;
        $q = "SELECT tr.object_id,p.post_title,p.guid
        FROM $wpdb->term_taxonomy `tt`,$wpdb->term_relationships `tr`,$wpdb->posts `p`
        WHERE tt.term_taxonomy_id=tr.term_taxonomy_id AND p.ID=tr.object_id AND tt.taxonomy='post_tag' AND p.post_status='publish'";
        if($ptype!='any')
            $q .= " AND p.post_type='".$ptype."'";
        $q .= " GROUP BY tr.object_id order by COUNT(tt.term_taxonomy_id) DESC LIMIT $rows;";

        return $wpdb->get_results($q);
    }

    /**
     * Gets terms used by any post type
     *
     * @param array $cpost_types array of post type names.
     *
     */
    public static function yg_pop_get_terms_by_cpt($cpost_types=array()){
        global $wpdb;

        $post_types = (array) $cpost_types;
        $where = " ";
        if(!empty($post_types)){
            $post_types_str = implode(',',$post_types);
            $where.= $wpdb->prepare(" AND p.post_type IN(%s)", $post_types_str);
        }
        //$where .= $wpdb->prepare(" AND tt.taxonomy = %s",$taxonomy);
        $q = "SELECT tt.term_taxonomy_id,tt.taxonomy FROM $wpdb->terms `t`
        INNER JOIN $wpdb->term_taxonomy `tt` ON t.term_id = tt.term_id 
        INNER JOIN $wpdb->term_relationships `r` ON r.term_taxonomy_id = tt.term_taxonomy_id 
        INNER JOIN $wpdb->posts `p` ON p.ID = r.object_id 
        $where GROUP BY tt.taxonomy";

        $results = $wpdb->get_results($q);
        return $results;
    }

    /**
     * Counts views and updates post meta for up to two durations
     *
     * @param int $dur1 array of post type names.
     * @param int $dur2 array of post type names.
     *
     */
    public static function yg_updatePostDurMeta($dur1 = 0,$dur2 = 0){
        global $wpdb;
        //cleare old date
        $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key='%s' OR meta_key='%s'",self::yg_dur1_meta_name,self::yg_dur2_meta_name));

        if($dur1 > 0 || $dur2 > 0){
            $earliestDate = max($dur1,$dur2);
            $table_name = self::$tablename;
            $q = "SELECT post_id";
            if($dur1 != 0){
                $dt = date("Y-m-d H:i:s", time() - (intval($dur1)*24*60*60));
                $q .= ",sum(case when date>'".$dt."' then count else 0 end) as `dur1`";
            }
            if($dur2 != 0){
                $dt = date("Y-m-d H:i:s", time() - (intval($dur2)*24*60*60));
                $q .= ",sum(case when date>'".$dt."' then count else 0 end) as `dur2`";
            }
            $mdt = date("Y-m-d H:i:s", time() - (intval($earliestDate)*24*60*60));
            $q .= " FROM $table_name WHERE date>'".$mdt."' group by post_id;";
            $views = $wpdb->get_results($q);

            foreach ($views as $postView) {
                if(isset($postView->dur1) && $postView->dur1>0)
                    update_post_meta($postView->post_id, self::yg_dur1_meta_name, $postView->dur1);
                if(isset($postView->dur2) && $postView->dur2>0)
                    update_post_meta($postView->post_id, self::yg_dur2_meta_name, $postView->dur2);
            }
        }
    }

    public static function yg_pop_hasDuration(){
        return !(isset(self::$_options['pop_duration_disabl']) && self::$_options['pop_duration_disabl']=='yes');
    }

    public static function yg_getDuration(){
        $dur = 0;
        if(isset(self::$_options['pop_w_duration']) && self::$_options['pop_w_duration']>0)
            $dur = self::$_options['pop_w_duration'];
        return $dur;
    }

    public static function yg_getDuration2(){
        $dur = 0;
        if(isset(self::$_options['pop_w_duration2']) && self::$_options['pop_w_duration2']>0)
            $dur = self::$_options['pop_w_duration2'];
        return $dur;
    }
    public static function yg_getCacheDuration(){
        $dur = 0;
        if(isset(self::$_options['cache_widget']) && self::$_options['cache_widget']>0)
            $dur = self::$_options['cache_widget'];
        return $dur;
    }
}
$yg_popular = new yg_popular();