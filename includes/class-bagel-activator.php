<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Bagel
 * @subpackage Bagel/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Bagel
 * @subpackage Bagel/includes
 * @author     Your Name <email@example.com>
 */
class Bagel_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
    public static function activate() {

        self::createTable();
        self::registerBagelUser();
    }

    private static function registerBagelUser() {

    }

    private static function createTable()
    {
        global $wpdb;

        $bagel_ai_table = Bagel_AI_Admin::get_tablename();

        $wpdb->query( "DROP TABLE IF EXISTS " . $bagel_ai_table );

        $charset_collate_new = $wpdb->get_charset_collate();
        $query = "
CREATE TABLE IF NOT EXISTS $bagel_ai_table (
id bigint(20) PRIMARY KEY AUTO_INCREMENT,
api_key varchar(250) UNIQUE,
subscription_description varchar(500),    
created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) $charset_collate_new;
";

        $wpdb->query( $query );
    }

}
