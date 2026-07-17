<?php
namespace HaminShop\B2BSuite\Infrastructure\Persistence\Migrations;

interface MigrationInterface {
    public function up(): void;
    public function down(): void;
    public function version(): string;
}
