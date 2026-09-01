<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\SeedsMis;
use Tests\TestCase;

/**
 * Photograph handling for the student profile.
 *
 * The fakes are built from an explicit MIME type rather than
 * UploadedFile::fake()->image(), because that helper needs ext-gd and this
 * deployment target does not have it.
 */
class PhotoUploadTest extends TestCase
{
    use RefreshDatabase, SeedsMis;

    public function test_a_photograph_is_stored_and_linked_to_the_profile(): void
    {
        Storage::fake('public');

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.students.store'), $this->studentPayload([
                'photo' => UploadedFile::fake()->create('portrait.jpg', 120, 'image/jpeg'),
            ]))
            ->assertSessionHasNoErrors();

        $student = Student::firstWhere('student_id_no', 'EAMU-2026-0001');

        $this->assertNotNull($student->photo_path);
        Storage::disk('public')->assertExists($student->photo_path);
    }

    public function test_a_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.students.store'), $this->studentPayload([
                'photo' => UploadedFile::fake()->create('payload.exe', 40),
            ]))
            ->assertSessionHasErrors('photo');

        $this->assertSame(0, Student::count());
    }

    public function test_an_oversized_photograph_is_rejected(): void
    {
        Storage::fake('public');

        $tooBig = config('mis.photo_max_kb') + 500;

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.students.store'), $this->studentPayload([
                'photo' => UploadedFile::fake()->create('huge.jpg', $tooBig, 'image/jpeg'),
            ]))
            ->assertSessionHasErrors('photo');
    }

    /**
     * Replacing a photograph must not leave the previous file behind.
     */
    public function test_replacing_a_photograph_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.students.store'), $this->studentPayload([
            'photo' => UploadedFile::fake()->create('first.jpg', 120, 'image/jpeg'),
        ]));

        $student = Student::firstWhere('student_id_no', 'EAMU-2026-0001');
        $original = $student->photo_path;

        $this->actingAs($admin)->put(route('admin.students.update', $student), $this->studentPayload([
            'student_id_no' => $student->student_id_no,
            'email' => $student->email,
            'photo' => UploadedFile::fake()->create('second.jpg', 120, 'image/jpeg'),
        ]));

        $student->refresh();

        $this->assertNotSame($original, $student->photo_path);
        Storage::disk('public')->assertMissing($original);
        Storage::disk('public')->assertExists($student->photo_path);
    }

    public function test_a_photograph_can_be_removed(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.students.store'), $this->studentPayload([
            'photo' => UploadedFile::fake()->create('portrait.jpg', 120, 'image/jpeg'),
        ]));

        $student = Student::firstWhere('student_id_no', 'EAMU-2026-0001');
        $path = $student->photo_path;

        $this->actingAs($admin)->put(route('admin.students.update', $student), $this->studentPayload([
            'student_id_no' => $student->student_id_no,
            'email' => $student->email,
            'remove_photo' => '1',
        ]));

        $this->assertNull($student->refresh()->photo_path);
        Storage::disk('public')->assertMissing($path);
    }
}
