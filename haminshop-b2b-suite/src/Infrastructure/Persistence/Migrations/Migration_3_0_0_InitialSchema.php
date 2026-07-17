<?php
namespace HaminShop\B2BSuite\Infrastructure\Persistence\Migrations;

class Migration_3_0_0_InitialSchema implements MigrationInterface {

    public function up(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'haminshop_b2b_customers';

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            business_type ENUM('petshop','clinic','pharmacy','shelter','distributor'),
            business_name VARCHAR(255),
            legal_name VARCHAR(255),
            national_id VARCHAR(255), /* Will be encrypted in App layer */
            economic_code VARCHAR(20),
            business_license_id VARCHAR(50),
            vat_certificate_url VARCHAR(500),
            manager_national_id VARCHAR(255),

            credit_limit DECIMAL(15,2) DEFAULT 0,
            credit_used DECIMAL(15,2) DEFAULT 0,
            wallet_balance DECIMAL(15,2) DEFAULT 0,
            cashback_balance DECIMAL(15,2) DEFAULT 0,
            cashback_expires_at DATETIME,
            guarantee_documents JSON,

            rfm_grade ENUM('A+','A','B','C','D') DEFAULT 'D',
            rfm_score JSON,
            rfm_calculated_at DATETIME,

            o2o_enabled TINYINT(1) DEFAULT 0,
            o2o_lat DECIMAL(10,7),
            o2o_lng DECIMAL(10,7),
            o2o_address TEXT,
            o2o_working_hours JSON,
            o2o_phone VARCHAR(20),

            assigned_sales_rep BIGINT UNSIGNED,
            status ENUM('pending','docs_incomplete','approved','suspended','blacklisted'),
            docs_deadline DATETIME,

            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_grade (rfm_grade),
            INDEX idx_geo (o2o_lat, o2o_lng),
            INDEX idx_status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function down(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'haminshop_b2b_customers';
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    }

    public function version(): string {
        return '3.0.0';
    }
}
