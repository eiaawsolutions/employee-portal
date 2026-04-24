<?php

namespace App\Models\Accounting;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AiChatMessage extends Model
{
    use BelongsToTenant;

    protected $table = 'acc_ai_chat_messages';

    protected $fillable = ['tenant_id', 'session_id', 'role', 'content', 'metadata', 'tokens_used'];

    protected $casts = ['metadata' => 'array'];

    public function session() { return $this->belongsTo(AiChatSession::class, 'session_id'); }
}
