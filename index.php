<?php
/**
 * Plugin Name: WordPress Google Analytics
 * Plugin URI:  https://github.com/pontusab/WordPress-Google-Analytics.git
 * Text Domain: wpga
 * Domain Path: /lang/
 * Description: Show your Google Analytics statistics in WordPress Dashboard
 * Version:     2013.01.29
 * Author:      Pontus Abrahamsson <pontus.abrahamsson@netrelations.se>
 * Author URI:  http://pontusab.se
 * License:     MIT
 * License URI: http://www.opensource.org/licenses/mit-license.php
 */

require( 'gapi.class.php' );

class Wpga {


	// Store some data in Object
	private 
		$settings,
		$user,
		$pass,
		$profile,
		$gapi,
		$expire;

	public function __construct() {
		global $pagenow;

		// Only run in dashboard-view
		if( $pagenow == 'index.php' ) {
			$this->expire = mktime( 0, 0, 0, date('n'), date('j') + 1 );
			$this->load_settings();
		}
	}



	// Register the dashboard
	public function add_analytics() {
		wp_add_dashboard_widget( 'analytics', __('Google Analytics', 'wpga'), array( $this, 'print_analytics' ) );	
	} 

	public function enqueue_scripts() {

        wp_register_style( 'stats', plugins_url( '/assets/css/stats.css', __FILE__ ) );
        wp_register_script( 'flot-main', plugins_url( '/assets/js/jquery.flot.js', __FILE__ ), array('jquery') );
        wp_register_script( 'app', plugins_url( '/assets/js/app.js', __FILE__ ), array('jquery') );

        wp_enqueue_style( 'stats' );


        if( $this->settings ) {
	        wp_enqueue_script( 'flot-main' );
	        wp_enqueue_script( 'app' );


	        // Add data to app.js //Page Views
			wp_localize_script( 'app', 'data', array(
				'stats'		  => $this->get_js_data(),
				'page_views'  => __( 'Page Views', 'wpga' )
			));       
		} 
    }

    public function save_settings() {
    	$savings = ( isset( $_POST['wpga'] ) ? $_POST['wpga'] : '' );

		if( $savings ) {

			foreach ( $savings as $save => $key ) {
				$data[ $save ] = $key;
			}

			update_option( 'wpga', $data );

			$this->load_settings();
		}
    }

    private function load_settings() {
    	$this->settings = get_option( 'wpga' );
		$this->user     = $this->settings['user'];
		$this->pass     = $this->settings['pass'];
		$this->profile  = $this->settings['profile'];


		add_action( 'wp_dashboard_setup', array( $this, 'add_analytics' ) );
		add_action( 'admin_init', array( $this, 'save_settings') );
		add_action( 'admin_init', array( $this, 'enqueue_scripts') );


		if( $this->settings ) {
			$this->gapi = new gapi( 
				$this->user, 
				$this->pass 
			);

			$start_date = date('Y-m-d', strtotime( date('Y-m-d', strtotime( date('Y-m-d') ) ) . '- 1 month' ) );
			$end_date = date('Y-m-d');

			$this->gapi->requestReportData(
				$this->profile, 
				array( 									// Dimensions
					'date',
					'year',
					'month',
					'day'
				),  						
				array(									// Metrics
					'visits',
					'visitors',
				 	'pageviews', 
				 	'avgTimeOnSite', 
				 	'newVisits'
				),
				'-visits',
				null,
				$start_date,
				$end_date
			);
		}
    }


    // Get data from Google Analytics
	public function get_report_data() {

		if( $this->gapi ) {

			$transient = get_transient( 'wpga_report_data' );
			
			if ( empty( $transient ) ) {

				$report_data = $this->gapi->getResults();

			   // we have a transient return/assign the results
			   set_transient( 'wpga_report_data', $report_data, $this->expire );
			} else {
				$report_data = get_transient( 'wpga_report_data' );
			}
		
			return $report_data;
		}
	}

	// Put the generated js_data to $stats
	// Uses wp_localize_script
	public function get_js_data() {

		$transient = get_transient( 'wpga_stats_data' );

		if ( empty( $transient ) ) {

			foreach( $this->get_report_data() as $data ) {
				$stats[] = '[new Date('. date( 'Y',strtotime( $data->getDate() ) ) .' -1, '. date( 'm',strtotime( $data->getDate() ) ) .' -1, '. date( 'd',strtotime( $data->getDate() ) ) .' ),'. number_format( $data->getVisits() ) .']';
			}

			$stats_data = implode( ',', $stats );

		   // we have a transient return/assign the results
		   set_transient( 'wpga_stats_data', $stats_data, $this->expire  );
		} else {
			$stats_data = get_transient( 'wpga_stats_data' );
		}
	
		return $stats_data;
	}

	// Print the Graph and stats 
	public function print_analytics() {

		$edit 	= ( isset( $_GET['edit'] ) ? $_GET['edit'] : '' );
		$cancel = ( isset( $_GET['cancel'] ) ? $_GET['cancel'] : '' );

		$output = '<div class="stats-holder">';

			if( !$edit && $this->gapi || $cancel && current_user_can( 'manage_options' ) ) {

				if( current_user_can( 'manage_options' ) ) {
					$output .= '<a class="edit-wpga" href="?edit=true">'. __('Edit Settings', 'wpga') .'</a>';
				}

				$output .= '<div class="stats-container">';
					$output .= '<div class="stats"></div>';
				$output .= '</div>';

				$output .= '<div class="stats-footer">';

					$output .= '<span>';
						$output .= '<h2>' . __( 'Total Visits', 'wpga' ) . '</h2>';
						$output .= number_format( $this->gapi->getVisits() );
					$output .= '</span>';

					$output .= '<span>';
						$output .= '<h2>' . __( 'Total Pageviews', 'wpga' ) . '</h2>';
						$output .= number_format( $this->gapi->getPageviews() );
					$output .= '</span>';

					$output .= '<span>';
						$output .= '<h2>' . __( 'Avg Time on Site', 'wpga' ) . '</h2>';
						$output .= number_format( $this->gapi->getAvgtimeonsite() ) .' '. __( 'Seconds', 'wpga' );
					$output .= '</span>';

					$output .= '<span>';
						$output .= '<h2>' . __( 'New Visits', 'wpga' ) . '</h2>';
						$output .= $this->gapi->getNewvisits();
					$output .= '</span>';

				$output .= '</div>';
			} else {

				$output .= '<a class="edit-wpga" href="?cancel=true">'. __('Cancel', 'wpga') .'</a>';

				$output .= '<form action="/wp-admin/" class="save-analytics" method="post">';

					$output .= '<div class="row">';
						$output .= '<input type="text" placeholder="'. __('Google Analytics Username', 'wpga') .'" '. ( isset( $this->user ) ? 'value="'. $this->user .'"' : '' ) .' name="wpga[user]">';
					$output .= '</div>';
					
					$output .= '<div class="row">';
						$output .= '<input type="password" placeholder="'. __('Google Analytics Password', 'wpga') .'" '. ( isset( $this->pass ) ? 'value="'. $this->pass .'"' : '' ) .' name="wpga[pass]">';
					$output .= '</div>';
					
					$output .= '<div class="row">';
						$output .= '<input type="text" placeholder="'. __('Google Analytics Profile-id', 'wpga') .'" '. ( isset( $this->profile ) ? 'value="'. $this->profile .'"' : '' ) .' name="wpga[profile]">';
					$output .= '</div>';

					$output .= '<input type="submit" name="Submit"  class="button-secondary" value="'. __('Save Settings', 'blocks') .'" />';
					$output .= '<input type="hidden" name="save-wpga" value="save" />';

				$output .= '</form>';
			}

		$output .= '</div>';

		echo $output;
	}
}

$wpga = new Wpga;