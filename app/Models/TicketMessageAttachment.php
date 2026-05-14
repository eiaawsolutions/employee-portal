<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Files attached to a chat message. One row per file; all linked to the
 * same TicketMessage via message_id.
 */
class TicketMessageAttachment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'message_id', 'file_path', 'original_name', 'mime', 'size', 'is_image',
    ];

    protected $casts = [
        'is_image' => 'boolean',
        'size' => 'integer',
    ];

    public function message()
    {
        return $this->belongsTo(TicketMessage::class, 'message_id');
    }

    public function url(): string
    {
        return route('secure.file', $this->file_path);
    }
}
