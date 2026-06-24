<?php

namespace App\Models;

use App\Enums\ExpenseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'created_by',
        'approved_by',
        'amount',
        'receipt_file',
        'ttd_file',
        'materai_file',
        'needs_materai',
        'bank_account_id',
        'status',
        'approved_at',
        'notes',
    ];

    protected $attributes = [
        'status'        => 'pending',
        'needs_materai' => false,
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'integer',
            'receipt_file'  => 'encrypted',
            'ttd_file'      => 'encrypted',
            'materai_file'  => 'encrypted',
            'needs_materai' => 'boolean',
            'status'        => ExpenseStatus::class,
            'approved_at'   => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
