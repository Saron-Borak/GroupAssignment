<?php

namespace App\Http\Requests\Admin;

use App\Enums\Gender;
use App\Enums\GuardianRelationship;
use App\Enums\StudentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Validation for the Student Profile module.
 *
 * The same rules serve create and update; the unique checks ignore the record
 * being edited, so re-saving an unchanged profile does not fail.
 */
class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // the route is already gated by role middleware
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('student')?->id;
        $minAge = (int) config('mis.min_age_years');
        $maxAge = (int) config('mis.max_age_years');

        return [
            'student_id_no' => [
                'required', 'string', 'max:30', 'regex:/^[A-Z0-9\-]+$/',
                Rule::unique('students', 'student_id_no')->ignore($id)->withoutTrashed(),
            ],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'gender' => ['required', new Enum(Gender::class)],
            'date_of_birth' => [
                'required', 'date',
                'before:'.now()->subYears($minAge)->toDateString(),
                'after:'.now()->subYears($maxAge)->toDateString(),
            ],
            'nationality' => ['nullable', 'string', 'max:60'],
            'national_id' => [
                'nullable', 'string', 'max:40',
                Rule::unique('students', 'national_id')->ignore($id)->withoutTrashed(),
            ],

            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('students', 'email')->ignore($id)->withoutTrashed(),
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\s\-]{6,30}$/'],
            'photo' => [
                'nullable', 'image',
                'mimes:'.implode(',', config('mis.photo_mimes')),
                'max:'.config('mis.photo_max_kb'),
            ],
            'remove_photo' => ['boolean'],

            'program_id' => ['required', 'exists:programs,id'],
            'intake_year' => ['required', 'integer', 'min:2000', 'max:'.(date('Y') + 1)],
            'admission_date' => ['required', 'date'],
            'status' => ['required', new Enum(StudentStatus::class)],

            'addresses' => ['array', 'max:5'],
            'addresses.*.address_type_id' => ['required_with:addresses.*.line1', 'nullable', 'exists:address_types,id'],
            'addresses.*.line1' => ['required_with:addresses.*.address_type_id', 'nullable', 'string', 'max:255'],
            'addresses.*.line2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['required_with:addresses.*.line1', 'nullable', 'string', 'max:80'],
            'addresses.*.province' => ['nullable', 'string', 'max:80'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:20'],
            'addresses.*.country' => ['nullable', 'string', 'max:80'],

            'guardians' => ['array', 'max:5'],
            'guardians.*.full_name' => ['required_with:guardians.*.phone', 'nullable', 'string', 'max:160'],
            'guardians.*.relationship' => ['required_with:guardians.*.full_name', 'nullable', new Enum(GuardianRelationship::class)],
            'guardians.*.phone' => ['required_with:guardians.*.full_name', 'nullable', 'string', 'max:30'],
            'guardians.*.email' => ['nullable', 'email', 'max:255'],
            'guardians.*.occupation' => ['nullable', 'string', 'max:120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'student_id_no.regex' => 'The student number may contain capital letters, digits and hyphens only.',
            'date_of_birth.before' => 'A student must be at least '.config('mis.min_age_years').' years old.',
            'date_of_birth.after' => 'Please check the date of birth; it looks implausible.',
            'phone.regex' => 'The phone number may contain digits, spaces, brackets, plus and hyphen only.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'student_id_no' => strtoupper(trim((string) $this->input('student_id_no'))),
            'email' => strtolower(trim((string) $this->input('email'))),
            'remove_photo' => $this->boolean('remove_photo'),
        ]);
    }

    /**
     * Address rows the registry left blank are dropped rather than failing
     * validation, so an unused row in the form is simply ignored.
     *
     * @return array<int, array<string, mixed>>
     */
    public function addressRows(): array
    {
        return collect($this->input('addresses', []))
            ->filter(fn ($r) => filled($r['line1'] ?? null) && filled($r['address_type_id'] ?? null))
            ->values()
            ->map(fn ($r, $i) => [
                'address_type_id' => (int) $r['address_type_id'],
                'line1' => $r['line1'],
                'line2' => $r['line2'] ?? null,
                'city' => $r['city'],
                'province' => $r['province'] ?? null,
                'postal_code' => $r['postal_code'] ?? null,
                'country' => filled($r['country'] ?? null) ? $r['country'] : 'Cambodia',
                // The first address supplied is treated as the primary one.
                'is_primary' => $i === 0,
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function guardianRows(): array
    {
        return collect($this->input('guardians', []))
            ->filter(fn ($r) => filled($r['full_name'] ?? null))
            ->values()
            ->map(fn ($r, $i) => [
                'full_name' => $r['full_name'],
                'relationship' => $r['relationship'],
                'phone' => $r['phone'],
                'email' => $r['email'] ?? null,
                'occupation' => $r['occupation'] ?? null,
                // The first guardian listed is the emergency contact.
                'is_emergency_contact' => $i === 0,
            ])
            ->all();
    }
}
