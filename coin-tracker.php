<?php
/*
Plugin Name: Dolphin MLM Coin Tracker
Plugin URI: https://www.famethemes.com/
Version: 1.0.0
Description: Coin Tracker
Author: Shrimp2t
Author URI: https://www.famethemes.com/
*/


function coin_tracker_get_config(){
    $config = array(
        'BCC' => array(
            array(
                'min'=> 100,
                'max' => 1000,
                'daily_interest' => 0,
                'capital_back' => 299
            ),
            array(
                'min'=> 1010,
                'max' => 5000,
                'daily_interest' => 0.1,
                'capital_back' => 239
            ),
            array(
                'min'=> 5010,
                'max' => 10000,
                'daily_interest' => 0.2,
                'capital_back' => 239
            ),
            array(
                'min'=> 10010,
                'max' => 100000,
                'daily_interest' => 0.25,
                'capital_back' => 120
            )
        ),
        'HEX' => array(
            array(
                'min'=> 100,
                'max' => 1000,
                'daily_interest' => 0,
                'capital_back' => 239
            ),
            array(
                'min'=> 1010,
                'max' => 5000,
                'daily_interest' => 0.15,
                'capital_back' => 179
            ),
            array(
                'min'=> 5010,
                'max' => 10000,
                'daily_interest' => 0.25,
                'capital_back' => 120
            ),
            array(
                'min'=> 10010,
                'max' => 100000,
                'daily_interest' => 0.30,
                'capital_back' => 120
            ),
            array(
                'min'=> 100010,
                'max' => null,
                'daily_interest' => 0.35,
                'capital_back' => 120
            ),
        ),
        'REC' => array(
            array(
                'min'=> 100,
                'max' => 9990,
                'daily_interest' => 0, // 1% every 11 days
                'every' => 11,
                'capital_back' => 99
            ),
            array(
                'min'=> 10000,
                'max' => 24990,
                'daily_interest' => 0.90909, // 1% every 11 days
                'every' => 11,
                'capital_back' => 99
            ),
            array(
                'min'=> 25000,
                'max' => 49990,
                'daily_interest' => 0.11818, // 1.3% every 11 days
                'every' => 11,
                'capital_back' => 99
            ),
            array(
                'min'=> 50000,
                'max' => 100000,
                'daily_interest' => 0.14546, // 1.6% every 11 days
                'every' => 11,
                'capital_back' => 99
            ),
        )

    );

    return $config;
}



function coin_tracker_install() {
    global $wpdb;
    $db_version = '1.0';

    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . 'coin_tracker';

    $sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		coin_code tinytext NOT NULL,
		add_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		interest_rate FLOAT(6,4),
		status VARCHAR (10) DEFAULT '',
		PRIMARY KEY  (id)
	) $charset_collate;";

    $table_name2 = $wpdb->prefix . 'coins';
    $sql2 = "CREATE TABLE $table_name2 (
		coin_code VARCHAR(10) NOT NULL,
		coin_name VARCHAR(60) DEFAULT '' NOT NULL,
		PRIMARY KEY  (coin_code)
	) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    dbDelta( $sql2 );

    add_option( 'coin_tracker', $db_version );
}

register_activation_hook( __FILE__, 'coin_tracker_install' );




class Coin_Tracker_Options {

    public $errors = array();
    private $cap ='edit_posts';
    private $url;

    function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        $this->url = admin_url( 'admin.php?page=coin_tracker' );
        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
            add_action( 'init', array( $this, 'actions' ) );
        } else {
            add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
        }
    }

    function frontend_scripts(){
        wp_enqueue_style( 'coin_tracker', plugins_url( 'style.css', __FILE__ ) );
        wp_enqueue_script( 'coin_tracker', plugins_url( 'js.js', __FILE__ ), array( 'jquery' ) );
        wp_localize_script( 'coin_tracker', 'coin_tracker', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ) );
    }

    function actions(){
        if (!  current_user_can( $this->cap ) ) {
            return;
        }

        if ( isset( $_POST['coin_tracker_action'] ) ){
            switch ( $_POST['coin_tracker_action']  ) {
                case 'add_coin':
                    $code = sanitize_text_field( $_POST['coin_code'] );
                    $name = sanitize_text_field( $_POST['coin_name'] );
                    $this->add_coin( $code, $name );
                    break;

                    case 'add_rate':
                     $this->add_rate( );
                    break;
            }
        }

        if ( isset( $_GET['del_coin_id'] ) ) {
            global $wpdb;
            $wpdb->delete( $wpdb->prefix.'coins', array( 'coin_code' => sanitize_text_field( $_GET['del_coin_id'] ) ), array( '%s' ) );
            wp_redirect( $this->url );
            die();
        }
    }

    function add_rate(){
        global $wpdb;
        $rate = floatval( $_POST['interest_rate'] );
        $coin_code = sanitize_text_field( $_POST['coin_code'] );
        $date_add = sanitize_text_field( $_POST['date_add'] );

        if ( ! $date_add ) {
            $date_add = date_i18n( 'Y-m-d' );
        }

        $table = $wpdb->prefix.'coin_tracker';

        $data = array(
            'coin_code' => $coin_code,
            'interest_rate' => $rate,
            'add_date' => $date_add,
        );

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE date( add_date ) =  %s", $date_add ) );

        $id = false;
        if ( $row ) {
            $id = $row->id;
        }

        if ( $id ) {
            $wpdb->update( $table,  $data, array( 'id' => $id ) );
        } else {
            $wpdb->insert( $table,  $data );
        }



    }

    function add_coin( $code, $name ){
        global $wpdb;
        $code = strtoupper( $code );
        $code = trim( $code );
        $name = trim( $name );

        $table = $wpdb->prefix.'coins';

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE coin_code = %s", $code ) );


        if ( $row ) {
            $this->errors['add_coin'] = 'This code already exists.';
            return false;
        }

        $wpdb->insert(
            $table,
            array(
                'coin_code' => $code,
                'coin_name' => $name
            ),
            array(
                '%s',
                '%s'
            )
        );

        $id = $wpdb->insert_id;

        return $id;

    }



    function scripts(){
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'coin_tracker', plugins_url( 'coin_tracker.css', __FILE__ ) );
        wp_enqueue_script( 'coin_tracker', plugins_url( 'coin_tracker.js', __FILE__ ), array( 'jquery' ) );
    }

    function admin_menu() {
        add_menu_page(
            'Coin Tracker',
            'Coin Tracker',
            $this->cap,
            'coin_tracker',
            array(
                $this,
                'settings_page'
            ),
            'dashicons-chart-line'
        );
    }

    function  settings_page() {
        $now = current_time('timestamp');
        $last_30_day = $now - MONTH_IN_SECONDS;

        $now = date( 'Y-m-d H:i:s', $last_30_day );
        global $wpdb;

        $rows  = $wpdb->get_results( $wpdb->prepare(
            "
                SELECT *
                FROM {$wpdb->prefix}coin_tracker
                WHERE add_date >= %s
                ORDER BY add_date DESC
            ",
            $now
        ) );

        $coins = $wpdb->get_results( "  SELECT *
                FROM {$wpdb->prefix}coins
                ORDER BY coin_code ASC " );

        $url = $this->url;

        ?>
        <div class="wrap coin_tracker_wrap">
            <h2>Coin Tracker</h2>

            <div class="left">

            <div class="coin_tracker-area">
               <div class="add_coin_tracker">
                   <form class="coin_tracker_form" action="<?php echo esc_url( $url ); ?>" method="post">
                       <h3>Add Interest Rate</h3>
                        <p>
                            <label>
                                <span>Interest Rate</span><br/>
                                <input type="text" maxlength="10" name="interest_rate">
                            </label>
                        </p>
                        <p>
                            <label>
                            <span>Coin</span><br/>
                            <select name="coin_code">
                            <?php foreach ( $coins as $r ) { ?>
                            <option value="<?php echo esc_attr( $r->coin_code ) ?>"><?php echo esc_html( $r->coin_code.' - '.$r->coin_name ); ?></option>
                            <?php } ?>
                            </select>
                            </label>
                        </p>

                        <label>
                            <span>Date</span><br/>
                            <input type="hidden" id="coin_tracker_date_add" name="date_add">
                            <div type="text" class="datepicker"></div>
                        </label>
                       <br/>
                        <input type="submit" class="button button-primary" value="Add Interest Rate">
                        <input type="hidden" name="coin_tracker_action" value="add_rate">

                   </form>
               </div>

            </div>

            <div class="coins_area">
                <?php

                if ( isset( $this->errors['add_coin'] ) ) {
                    echo '<div class="coin_tracker_error">'.esc_html( $this->errors['add_coin']  ).'</div>';
                }

                ?>

                <form class="coin_tracker_form" action="<?php echo esc_url( $url ); ?>" method="post">
                    <h3>Add Coin</h3>
                   <p>
                       <label>
                           <span>Coin Code</span><br/>
                           <input type="text" name="coin_code">
                       </label>
                   </p>

                    <p>
                        <label>
                            <span>Coin Name</span><br/>
                            <input type="text" name="coin_name">
                        </label>
                    </p>

                    <input type="submit" class="button button-primary" value="Add Coin">

                    <input type="hidden" name="coin_tracker_action" value="add_coin">
                </form>

                <h3>All Coins</h3>
                <table class="coin_tracker wp-list-table widefat fixed striped posts">
                    <thead>
                    <tr>
                        <th class="manage-column">Coin</th>
                        <th id="author" class="manage-column">Name</th>
                        <th id="author" class="manage-column">Action</th>
                    </tr>
                    </thead>
                    <tbody id="the-list">
                    <?php foreach ( $coins as $row ) { ?>
                        <tr>
                            <td class=""><?php echo strtoupper( $row->coin_code ); ?></td>
                            <th class=""><?php echo strtoupper( $row->coin_name ); ?></th>
                            <th id="author" class="manage-column">
                                <a onclick="return confirm('Delete ?')" href="<?php echo add_query_arg( array( 'del_coin_id' => $row->coin_code ), $url ) ?>">Delete</a>
                            </th>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>

            </div>

            <div class="right">
            <h3>Interest Rate Last 30 Days</h3>
            <table class="coin_tracker wp-list-table widefat fixed striped posts">
                <thead>
                <tr>
                    <th class="manage-column">Coin</th>
                    <th id="author" class="manage-column">Interest Rate</th>
                    <th id="author" class="manage-column">Date</th>
                </tr>
                </thead>
                <tbody id="the-list">
                <?php foreach ( $rows as $row ) { ?>
                    <tr>
                        <td class=""><?php echo strtoupper( $row->coin_code ); ?></td>
                        <th class=""><?php echo strtoupper( $row->interest_rate ); ?></th>
                        <th class=""><?php echo date( 'Y-m-d', strtotime( $row->add_date ) ) ?></th>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            </div>

        </div>
        <?php

    }

}

new Coin_Tracker_Options;


include dirname( __FILE__ ).'/api.php';


function coin_tracker_get_data( $num_days, $code = 'BCC' ){
    $num_days = intval( $num_days );
    $now_timestamp = current_time('timestamp');
    $last_n_days = $now_timestamp - $num_days*DAY_IN_SECONDS;
    $now = date( 'Y-m-d', $last_n_days );
    global $wpdb;

    $rows  = $wpdb->get_results( $wpdb->prepare(
        "
                SELECT *
                FROM {$wpdb->prefix}coin_tracker
                WHERE coin_code = %s
                ORDER BY add_date DESC
                LIMIT  %d
            ",
        $code,
        $num_days
    ) );

    if ( ! is_array( $rows ) ) {
        $rows = array();
    }

    $new_data = array();


    foreach ( $rows as $index => $r ) {
        $r_date = strtotime( $r->add_date );
        $n = $english_format_number = number_format( $r->interest_rate, 2, '.', '');
        $date = date( 'd-m-Y', $r_date );
        $new_data[ $index ] = array(
            'date' => $date,
            'status' => $r_date > $now_timestamp ? 'Đang chờ': 'Chấp nhận',
            'interest_rate_format' => $n,
            'interest_rate' =>  $r->interest_rate,
        );
    }
    return $new_data;
}


function coin_tracker_last_days( $atts ) {
    $a = shortcode_atts( array(
        'days' => 5,
        'coin' => 'BCC',
    ), $atts );
    $content = '';


    $rows = coin_tracker_get_data( $a['days'], $a['coin'] );

    foreach ( $rows as $r ) {
        $content .= '<div class="ct_history_item text-center">';
            $content .='<div class="ct_history_item_inner">';
            $content .= '<div class="ct_rate"><strong>'.$r['interest_rate_format'].'%</strong></div>';

            $content .= '<div class="ct_date"><i class="fa fa-calendar"></i> '.( $r['date'] == date_i18n( 'd-m-Y' )  ? 'Hôm nay' : $r['date'] ) .'</div>';

            if (  $r['status'] == 'Chấp nhận' ) {
                $content .= '<div class="ct_status ct_approved"><i class="fa fa-check-circle fa-lg"></i> '.ucfirst( $r['status'] ).' </div>';

            } else {
                $content .= '<div class="ct_status ct_pending"><i class="fa fa-clock-o text-warning fa-lg"></i> '.ucfirst( $r['status'] ).' </div>';
            }

            $content .= '</div>';
        $content .= '</div>';
    }

    if ( $content ) {
        $content = '<div class="coin_tracker_history">'.$content.'</div>';
    }

   return $content;
}
add_shortcode( 'coin_tracker', 'coin_tracker_last_days' );


function coin_tracker_interest_rate_last_30_days( $atts ){
    $a = shortcode_atts( array(
        'days' => 30,
        'coin' => 'BCC',
    ), $atts );
    $content = '';

    $packages = coin_tracker_get_config();
    if ( ! isset( $packages[ $a['coin'] ] ) ) {
        return '';
    }
    $rows = coin_tracker_get_data( 30, $a['coin'] );

    $data = array();
    foreach ( $packages[ $a['coin'] ] as $index => $plan ) {
        $rate = 0;
        if ( ! $plan['max'] ) {
            $data[ $index ]['plan_name'] = '$'.number_format( $plan['min'], 0, '.', ',' ).' Above';
        } else {
            $data[ $index ]['plan_name'] = '$'.number_format( $plan['min'], 0 ).' - $'.number_format( $plan['max'], 0 );
        }

        foreach ( $rows as $r ) {
            $rate += $r['interest_rate'];
            if ( isset( $plan['daily_interest']  ) ) {
                $rate += $plan['daily_interest'];
            }
        }

        $data[ $index ]['rate'] = $rate;

    }


    foreach ( $data as $k=> $d ) {
        $content.= '<div class="coin_tracker_rate_package">';
            $content .= '<div class="ctp_name" >'.$d['plan_name'].'</div>';
            $content .= '<div class="ctp_value">'.$d['rate'].'%</div>';
        $content.= '</div>';
    }

    if ( $content ) {
        $content= '<div class="coin_tracker_rate_packages">'.$content.'</div>';
    }

    return $content;
}

add_shortcode( 'coin_tracker_interest_rate_30', 'coin_tracker_interest_rate_last_30_days' );



add_action( 'wp_ajax_coin_tracker_load_content', 'wp_ajax_coin_tracker_load_content' );

function wp_ajax_coin_tracker_load_content() {
    global $wpdb; // this is how you get access to the database
    $url = esc_url( $_GET['url'] );

    $time = 5*60; // 5 mins
    $key = 'cache_'.$url;
    if( get_transient( $key ) !== false ) {
        echo get_transient( $key );
        return ;
    }
    $r = wp_remote_get( $url );
    $html = wp_remote_retrieve_body( $r );

    preg_match("/<body[^>]*>(.*?)<\/body>/is", $html, $matches);
    $content = '';
    if( $matches ) {
        $content = $matches[1];
        echo $content;
    }

    set_transient( $key, $content, $time );

    wp_die(); // this is required to terminate immediately and return a proper response
}

add_shortcode( 'coin_btc_to_usd', 'coin_tracker_btc_to_usd' );

function coin_tracker_btc_to_usd( $atts ){
    return '<span class="coin_btc_to_usd">......</span>';
}
add_shortcode( 'coin_bcc_to_usd', 'coin_tracker_bcc_to_usd' );
function coin_tracker_bcc_to_usd( $atts ){
    return '<span class="coin_bcc_to_usd">......</span>';
}

add_shortcode( 'coin_date', 'coin_tracker_coin_date' );
function coin_tracker_coin_date( $atts ){
    return date_i18n('H:i:s d-m-Y');
}