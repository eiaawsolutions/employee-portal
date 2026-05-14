<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TicketEditLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'ticket_id',
        'edited_by_user_id',
        'changes',
        'note',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by_user_id');
    }
}
