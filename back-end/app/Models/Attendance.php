<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'office_location_id',
        'attendance_date',
        'check_in',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_distance',
        'check_out',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_distance',
        'status',
        'approval_status',
        'notes',
        'attachment',
        'rejection_reason',
        'approved_by',
        'approved_at',
    ];

    protected $appends = [
        'attachment_url',
        'attachment_type',
        'attachment_name',
    ];

    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment) {
            return null;
        }

        if (str_starts_with($this->attachment, 'http://') || str_starts_with($this->attachment, 'https://')) {
            return $this->attachment;
        }

        return url('/api/attendance/attachment/' . $this->id);
    }

    public function getAttachmentTypeAttribute(): ?string
    {
        if (!$this->attachment) {
            return null;
        }
        $ext = strtolower(pathinfo($this->attachment, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            return 'image';
        }
        if ($ext === 'pdf') {
            return 'pdf';
        }
        if (in_array($ext, ['doc', 'docx'])) {
            return 'doc';
        }
        return 'file';
    }

    public function getAttachmentNameAttribute(): ?string
    {
        if (!$this->attachment) {
            return null;
        }
        return basename($this->attachment);
    }

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withDefault([
            'name' => '-',
        ]);
    }

    public function officeLocation(): BelongsTo
    {
        return $this->belongsTo(OfficeLocation::class)->withDefault([
            'name' => '-',
        ]);
    }
}