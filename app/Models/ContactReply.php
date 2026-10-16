<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// CONTACT-06: a reply sent from the admin inbox.
#[Fillable(['contact_message_id', 'user_id', 'body'])]
class ContactReply extends Model
{
    /** @return BelongsTo<ContactMessage, $this> */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ContactMessage::class, 'contact_message_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
