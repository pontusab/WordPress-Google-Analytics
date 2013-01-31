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
		$gapi;

	public function __construct() {
		global $pagenow;

		// Only run in dashboard-view
		if( $pagenow == 'index.php' ) {
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

		$this->user    = $this->settings['user'];
		$this->pass    = $this->settings['pass'];
		$this->profile = $this->settings['profile'];


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
				array( 'date' ),  						// Dimensions
				array(									// Metrics
					'visits',
					'visitors',
				 	'pageviews', 
				 	'uniquePageviews', 
				 	'exitRate', 
				 	'avgTimeOnPage', 
				 	'entranceBounceRate'
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
			return $this->gapi->getResults();
		}
	}

	// Put the generated js_data to $stats
	// Uses wp_localize_script
	public function get_js_data() {

		foreach( $this->get_report_data() as $data ) {
			#print_r( date( 'Y-m-d',strtotime( $data->getDate() ) ) );
			$stats[] = '[new Date('. date( 'Y',strtotime( $data->getDate() ) ) .', '. date( 'm',strtotime( $data->getDate() ) ) .', '. date( 'd',strtotime( $data->getDate() ) ) .'),'. number_format( $data->getVisits() ) .']';
		}

		// Return the data 
		return implode( ',', $stats );
	}

	// Print the Graph and stats 
	public function print_analytics() {

		$output = '<div class="stats-holder">';

			if( $this->gapi ) {

				$output .= '<div class="stats-container">';
					$output .= '<div class="stats"></div>';
				$output .= '</div>';

				$output .= '<div class="stats-footer">';

					$output .= '<span>';
						$output .= '<h2>' . __( 'Total Visits', 'wpga' ) . '</h2>';
						$output .= number_format( $this->gapi->getVisitors() );
					$output .= '</span>';

					
					$output .= '<span>';
						$output .= '<h2>' . __( 'Unique Visitors', 'wpga' ) . '</h2>';
						#$output .= number_format( $this->gapi->getUniquevisitors() );
					$output .= '</span>';

					$output .= '<span>';
						$output .= '<h2>' . __( 'Total Pageviews', 'wpga' ) . '</h2>';
						$output .= number_format( $this->gapi->getPageviews() );
					$output .= '</span>';

					$output .= '<span>';
						$output .= '<h2>' . __( 'Avg Time on Page', 'wpga' ) . '</h2>';
						$output .= $this->gapi->getAvgtimeonpage() .' '. __( 'Seconds', 'wpga' );
					$output .= '</span>';

					$output .= '<span>';
						$output .= '<h2>' . __( 'Bounce Rate', 'wpga' ) . '</h2>';
						#$output .= $this->gapi->getAvgtimeonpage() .' '. __( 'Seconds', 'wpga' );
					$output .= '</span>';

					$output .= '<span>';
						$output .= '<h2>' . __( 'New Visits', 'wpga' ) . '</h2>';
						#$output .= $this->gapi->getAvgtimeonpage() .' '. __( 'Seconds', 'wpga' );
					$output .= '</span>';

				$output .= '</div>';
			} else {

				$output .= '<form action="" class="save-analytics" method="post">';

					$output .= '<div class="row">';
						$output .= '<input type="text" placeholder="'. __('Google Analytics Username', 'wpga') .'" name="wpga[user]">';
					$output .= '</div>';
					
					$output .= '<div class="row">';
						$output .= '<input type="text" placeholder="'. __('Google Analytics Password', 'wpga') .'" name="wpga[pass]">';
					$output .= '</div>';
					
					$output .= '<div class="row">';
						$output .= '<input type="text" placeholder="'. __('Google Analytics Profile-id', 'wpga') .'" name="wpga[profile]">';
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