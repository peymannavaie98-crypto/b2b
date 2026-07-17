<?php
namespace HaminShop\B2BSuite\Infrastructure\Persistence\Migrations;

class Installer {

    public static function runMigrations(): void {
        $migrations = [
            new Migration_3_0_0_InitialSchema(),
        ];

        $current_version = get_option('haminshop_b2b_db_version', '0.0.0');

        foreach ($migrations as $migration) {
            if (version_compare($current_version, $migration->version(), '<')) {
                $migration->up();
                update_option('haminshop_b2b_db_version', $migration->version());
            }
        }
    }
}
