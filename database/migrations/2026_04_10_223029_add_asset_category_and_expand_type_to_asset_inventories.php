<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add asset_category and invoice_documents columns
        Schema::table('asset_inventories', function (Blueprint $table) {
            $table->string('asset_category', 100)->nullable()->after('asset_tag');
            $table->json('invoice_documents')->nullable()->after('invoice_document');
        });

        // 2. Convert asset_type from enum to varchar to support expanded types
        DB::statement("ALTER TABLE asset_inventories MODIFY asset_type VARCHAR(100) NOT NULL DEFAULT 'other'");

        // 3. Backfill asset_category from existing asset_type values
        $typeToCategory = [
            'laptop'      => 'it_equipment',
            'monitor'     => 'it_equipment',
            'phone'       => 'it_equipment',
            'sim_card'    => 'it_equipment',
            'converter'   => 'it_equipment',
            'access_card' => 'it_equipment',
            'accessories' => 'it_equipment',
            'furniture'   => 'office_furniture',
            'equipment'   => 'office_equipment',
            'petty_cash'  => 'office_supplies',
            'other'       => 'others',
        ];

        foreach ($typeToCategory as $type => $category) {
            DB::table('asset_inventories')
                ->where('asset_type', $type)
                ->whereNull('asset_category')
                ->update(['asset_category' => $category]);
        }

        // Catch any remaining
        DB::table('asset_inventories')
            ->whereNull('asset_category')
            ->update(['asset_category' => 'others']);

        // 4. Migrate single invoice_document to invoice_documents JSON array
        $assets = DB::table('asset_inventories')
            ->whereNotNull('invoice_document')
            ->where('invoice_document', '!=', '')
            ->whereNull('invoice_documents')
            ->get(['id', 'invoice_document']);

        foreach ($assets as $asset) {
            DB::table('asset_inventories')
                ->where('id', $asset->id)
                ->update(['invoice_documents' => json_encode([$asset->invoice_document])]);
        }
    }

    public function down(): void
    {
        Schema::table('asset_inventories', function (Blueprint $table) {
            $table->dropColumn(['asset_category', 'invoice_documents']);
        });

        DB::statement("ALTER TABLE asset_inventories MODIFY asset_type ENUM('laptop','monitor','converter','phone','sim_card','access_card','other') NOT NULL DEFAULT 'other'");
    }
};
