<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public const BUSINESS_ROLES = [
        'admin',
        'guru',
        'orang_tua',
        'siswa',
        'loket',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'job_title',
        'avatar',
        'status',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status === 'active' && $this->hasAnyRole(self::BUSINESS_ROLES);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public static function roleLabel(string $role): string
    {
        return match ($role) {
            'admin' => 'Admin / Kepala Sekolah',
            'guru' => 'Guru',
            'orang_tua' => 'Orang Tua',
            'siswa' => 'Siswa',
            'loket' => 'Petugas Loket',
            default => $role,
        };
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function parentProfile()
    {
        return $this->hasOne(ParentProfile::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function accessibleStudents(): Builder
    {
        $query = Student::query();

        if ($this->isAdmin() || $this->hasRole('loket')) {
            return $query;
        }

        if ($this->hasRole('guru')) {
            return $query->whereIn(
                'class_id',
                $this->accessibleClasses()->select('classes.id'),
            );
        }

        if ($this->hasRole('orang_tua')) {
            return $query->whereHas('parent', fn (Builder $query) => $query->where('user_id', $this->id));
        }

        if ($this->hasRole('siswa')) {
            return $query->where('user_id', $this->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function accessibleClasses(): Builder
    {
        $query = SchoolClass::query();

        if ($this->isAdmin() || $this->hasRole('loket')) {
            return $query;
        }

        if ($this->hasRole('guru')) {
            return $query->where(function (Builder $query): void {
                $query
                    ->whereHas('teacher', fn (Builder $query) => $query->where('user_id', $this->id))
                    ->orWhereHas('assistantTeacher', fn (Builder $query) => $query->where('user_id', $this->id))
                    ->orWhereHas('teachingAssignments.teacher', fn (Builder $query) => $query->where('user_id', $this->id));
            });
        }

        if ($this->hasAnyRole(['orang_tua', 'siswa'])) {
            return $query->whereIn(
                'classes.id',
                $this->accessibleStudents()
                    ->whereNotNull('class_id')
                    ->select('students.class_id'),
            );
        }

        return $query->whereRaw('1 = 0');
    }

    public function managedClasses(): Builder
    {
        $query = SchoolClass::query();

        if ($this->isAdmin() || $this->hasRole('loket')) {
            return $query;
        }

        if ($this->hasRole('guru')) {
            return $query->where(function (Builder $query): void {
                $query
                    ->whereHas('teacher', fn (Builder $query) => $query->where('user_id', $this->id))
                    ->orWhereHas('assistantTeacher', fn (Builder $query) => $query->where('user_id', $this->id));
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public function managedStudents(): Builder
    {
        if (! $this->hasRole('guru')) {
            return $this->accessibleStudents();
        }

        return Student::query()->whereIn(
            'class_id',
            $this->managedClasses()->select('classes.id'),
        );
    }

    public function canAccessStudent(Student|int $student): bool
    {
        $studentId = $student instanceof Student ? $student->getKey() : $student;

        return $this->accessibleStudents()->whereKey($studentId)->exists();
    }

    public function canAccessActivity(string $activityType, int $activityId): bool
    {
        if (! in_array($activityType, [SchoolActivity::class, HomeActivity::class], true)) {
            return false;
        }

        $studentId = $activityType::query()->whereKey($activityId)->value('student_id');

        if ($this->hasRole('guru') && $activityType === HomeActivity::class) {
            return $studentId && $this->managedStudents()->whereKey($studentId)->exists();
        }

        return $studentId && $this->canAccessStudent((int) $studentId);
    }

    public function activityComments()
    {
        return $this->hasMany(ActivityComment::class);
    }

    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class);
    }

    public function scheduleResponses()
    {
        return $this->hasMany(ScheduleResponse::class);
    }

    public function recordedArrivals()
    {
        return $this->hasMany(StudentArrival::class, 'recorded_by');
    }

    public function recordedAttendances()
    {
        return $this->hasMany(AttendanceRecord::class, 'recorded_by');
    }

    public function reviewedParentSubmissions()
    {
        return $this->hasMany(ParentSubmission::class, 'reviewed_by');
    }

    public function passwordResetRequests()
    {
        return $this->hasMany(PasswordResetRequest::class);
    }
}
