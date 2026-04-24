<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AccountingSetting extends Model
{
    use BelongsToTenant;

    protected $table = 'acc_settings';

    protected $fillable = [
        'tenant_id', 'company', 'fiscal_year_start_month', 'base_currency',
        'tax_registration_number', 'tax_type',
        'invoice_prefix', 'credit_note_prefix', 'bill_prefix', 'po_prefix',
        'journal_prefix', 'payment_prefix', 'receipt_prefix',
        'next_invoice_number', 'next_credit_note_number', 'next_bill_number',
        'next_po_number', 'next_journal_number', 'next_payment_number', 'next_receipt_number',
        'default_payment_terms_days', 'default_tax_code_id',
        'default_sales_account_id', 'default_purchase_account_id', 'retained_earnings_account_id',
        'enable_multi_currency', 'ai_provider', 'ai_api_key', 'ai_model', 'ollama_base_url',
    ];

    protected $casts = [
        'enable_multi_currency' => 'boolean',
        'ai_api_key'           => 'encrypted',
    ];

    protected $hidden = ['ai_api_key'];

    public function defaultTaxCode()        { return $this->belongsTo(TaxCode::class, 'default_tax_code_id'); }
    public function defaultSalesAccount()   { return $this->belongsTo(ChartOfAccount::class, 'default_sales_account_id'); }
    public function defaultPurchaseAccount(){ return $this->belongsTo(ChartOfAccount::class, 'default_purchase_account_id'); }

    /**
     * Resolve the best AccountingSetting to use for AI features.
     * Priority: company-specific with key → any record with key → company-specific without key → first record
     */
    public static function resolveForAi(?string $company): ?self
    {
        if ($company) {
            $specific = self::where('company', $company)->first();
            if ($specific && ($specific->getAttributes()['ai_api_key'] ?? null)) {
                return $specific;
            }
        }
        // Fall back to any settings record that has an API key configured
        $withKey = self::whereNotNull('ai_api_key')->first();
        if ($withKey) {
            return $withKey;
        }
        // Last resort: company-specific or first record (no key, but has other settings)
        return $company
            ? self::where('company', $company)->first()
            : self::first();
    }

    /** True if an AI API key is stored (uses raw attribute, same as settings page check). */
    public function hasAiApiKey(): bool
    {
        return (bool) ($this->getAttributes()['ai_api_key'] ?? null);
    }

    public function getNextNumber(string $type): string
    {
        $prefixCol = $type . '_prefix';
        $numberCol = 'next_' . $type . '_number';
        $prefix = $this->{$prefixCol};
        $number = $this->{$numberCol};
        $this->increment($numberCol);
        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
