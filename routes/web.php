<?php

use App\Http\Controllers\Academy\AcademyController;
use App\Http\Controllers\Academy\CertificateController;
use App\Http\Controllers\Academy\ExamController;
use App\Http\Controllers\Admin\AccreditationController;
use App\Http\Controllers\Admin\CourseContentController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinalsController;
use App\Http\Controllers\Admin\GalaController;
use App\Http\Controllers\Admin\GalaEntryController;
use App\Http\Controllers\Admin\GalaSanctionController;
use App\Http\Controllers\Admin\MeetFileController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\OrganizationSwitchController;
use App\Http\Controllers\Admin\RelayTeamController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SeedingController;
use App\Http\Controllers\Admin\SwimRecordController;
use App\Http\Controllers\Admin\TechnicalOfficialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VenueController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Public\CalendarController;
use App\Http\Controllers\Public\DirectoryController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\NewsController;
use App\Http\Controllers\Public\OfficialsPathwayController;
use App\Http\Controllers\Public\RecordController;
use App\Http\Controllers\Public\ResultsController;
use Illuminate\Support\Facades\Route;

/*
 * The public website. No login needed.
 */
Route::get('/', HomeController::class)->name('home');
Route::get('calendar', [CalendarController::class, 'index'])->name('calendar');
Route::get('calendar/{gala}', [CalendarController::class, 'show'])->name('galas.show');
Route::get('clubs', [DirectoryController::class, 'clubs'])->name('clubs');
Route::get('venues', [DirectoryController::class, 'venues'])->name('venues');
Route::get('about', [DirectoryController::class, 'about'])->name('about');
Route::get('records', RecordController::class)->name('records');
Route::get('results', [ResultsController::class, 'index'])->name('results.index');
Route::get('results/{gala}', [ResultsController::class, 'show'])->name('results.show');
Route::get('results/{gala}/live', [ResultsController::class, 'live'])->name('results.live');
Route::get('verify/{certificate}', [CertificateController::class, 'verify'])->name('certificates.verify');
Route::get('officials', OfficialsPathwayController::class)->name('officials');
Route::get('news', [NewsController::class, 'index'])->name('news.index');
Route::get('news/{post}', [NewsController::class, 'show'])->name('news.show');

/*
 * The secure backend. Every route is scoped to the organisation the user is
 * acting in, and each policy decides what that user may reach.
 */
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    /*
     * The bell in the header. Opening a notice marks it read and takes the
     * person to the thing it is about, so the tally always reflects what is
     * genuinely still waiting.
     */
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('notifications/{notification}', [NotificationController::class, 'read'])->name('notifications.read');

    Route::prefix('academy')->name('academy.')->group(function (): void {
        Route::get('/', [AcademyController::class, 'index'])->name('index');
        Route::get('{course}', [AcademyController::class, 'show'])->name('show');
        Route::post('{course}/enrol', [AcademyController::class, 'enrol'])->name('enrol');
        Route::get('{course}/lessons/{lesson}', [AcademyController::class, 'lesson'])->name('lesson');
        Route::post('{course}/lessons/{lesson}/complete', [AcademyController::class, 'completeLesson'])->name('lesson.complete');
        Route::post('{course}/exam', [ExamController::class, 'start'])->name('exam.start');
        Route::get('{course}/exam/{attempt}', [ExamController::class, 'show'])->name('exam.show');
        Route::post('{course}/exam/{attempt}/submit', [ExamController::class, 'submit'])->name('exam.submit');
    });

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::post('organization-switch', OrganizationSwitchController::class)->name('organization.switch');

        /*
         * Spreadsheet downloads of the admin listings, kept under their own
         * prefix rather than as `members/export`: a path segment beside a
         * resource route would be read as a slug, and `export` is a
         * reserved word in the generated client.
         */
        Route::prefix('exports')->name('exports.')->group(function (): void {
            Route::get('organizations', [OrganizationController::class, 'export'])->name('organizations');
            Route::get('members', [MemberController::class, 'export'])->name('members');
            Route::get('venues', [VenueController::class, 'export'])->name('venues');
            Route::get('galas', [GalaController::class, 'export'])->name('galas');
            Route::get('officials', [TechnicalOfficialController::class, 'export'])->name('officials');
            Route::get('records', [SwimRecordController::class, 'export'])->name('records');
            Route::get('users', [UserController::class, 'export'])->name('users');
        });

        Route::resource('organizations', OrganizationController::class)->except(['show']);
        Route::resource('members', MemberController::class)->except(['show']);
        Route::resource('venues', VenueController::class)->except(['show']);
        Route::resource('galas', GalaController::class);

        Route::resource('officials', TechnicalOfficialController::class)
            ->except(['destroy'])
            ->parameters(['officials' => 'official']);

        Route::post('galas/{gala}/sanction', [GalaSanctionController::class, 'store'])->name('galas.sanction');

        Route::get('galas/{gala}/entries', [GalaEntryController::class, 'index'])->name('galas.entries.index');
        Route::post('galas/{gala}/entries', [GalaEntryController::class, 'store'])->name('galas.entries.store');
        Route::patch('galas/{gala}/entries/{entry}', [GalaEntryController::class, 'update'])->name('galas.entries.update');
        Route::delete('galas/{gala}/entries/{entry}', [GalaEntryController::class, 'destroy'])->name('galas.entries.destroy');

        Route::get('galas/{gala}/relays', [RelayTeamController::class, 'index'])->name('galas.relays.index');
        Route::post('galas/{gala}/relays', [RelayTeamController::class, 'store'])->name('galas.relays.store');
        Route::delete('galas/{gala}/relays/{team}', [RelayTeamController::class, 'destroy'])->name('galas.relays.destroy');

        Route::get('galas/{gala}/lenex', [MeetFileController::class, 'exportLenex'])->name('galas.lenex');
        Route::get('galas/{gala}/entry-sheet', [MeetFileController::class, 'template'])->name('galas.entry-sheet');
        Route::post('galas/{gala}/import', [MeetFileController::class, 'import'])->name('galas.import');

        Route::get('galas/{gala}/finals', [FinalsController::class, 'index'])->name('galas.finals.index');
        Route::post('galas/{gala}/finals', [FinalsController::class, 'promote'])->name('galas.finals.promote');

        Route::get('galas/{gala}/heat-sheet', [SeedingController::class, 'show'])->name('galas.heat-sheet');
        Route::post('galas/{gala}/seed', [SeedingController::class, 'store'])->name('galas.seed');

        Route::get('galas/{gala}/results', [ResultController::class, 'index'])->name('galas.results.index');
        Route::get('galas/{gala}/results/{event}', [ResultController::class, 'edit'])->name('galas.results.edit');
        Route::post('galas/{gala}/results/{event}', [ResultController::class, 'store'])->name('galas.results.store');
        Route::post('galas/{gala}/results/{event}/publish', [ResultController::class, 'publish'])->name('galas.results.publish');

        Route::post('officials/{official}/accreditations', [AccreditationController::class, 'store'])
            ->name('officials.accreditations.store');
        Route::patch('officials/{official}/accreditations/{accreditation}', [AccreditationController::class, 'update'])
            ->name('officials.accreditations.update');
        Route::post('officials/{official}/accreditations/{accreditation}/ratify', [AccreditationController::class, 'ratify'])
            ->name('officials.accreditations.ratify');

        Route::get('records', [SwimRecordController::class, 'index'])->name('records.index');
        Route::post('records', [SwimRecordController::class, 'store'])->name('records.store');
        Route::post('records/{record}/ratify', [SwimRecordController::class, 'ratify'])->name('records.ratify');
        Route::post('records/{record}/reject', [SwimRecordController::class, 'reject'])->name('records.reject');
        Route::delete('records/{record}', [SwimRecordController::class, 'destroy'])->name('records.destroy');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('users/{user}/roles', [UserController::class, 'assignRole'])->name('users.roles.assign');
        Route::delete('users/{user}/roles', [UserController::class, 'revokeRole'])->name('users.roles.revoke');

        Route::get('academy', [CourseController::class, 'index'])->name('academy.index');
        Route::post('academy', [CourseController::class, 'store'])->name('academy.store');
        Route::get('academy/{course}/edit', [CourseController::class, 'edit'])->name('academy.edit');
        Route::patch('academy/{course}', [CourseController::class, 'update'])->name('academy.update');
        Route::delete('academy/{course}', [CourseController::class, 'destroy'])->name('academy.destroy');
        Route::get('academy/{course}/enrolments', [CourseController::class, 'enrolments'])->name('academy.enrolments');
        Route::post('academy/{course}/enrolments/{enrolment}/certificate', [CertificateController::class, 'issue'])
            ->name('academy.certificate.issue');

        Route::post('academy/{course}/lessons', [CourseContentController::class, 'storeLesson'])->name('academy.lessons.store');
        Route::patch('academy/{course}/lessons/{lesson}', [CourseContentController::class, 'updateLesson'])->name('academy.lessons.update');
        Route::delete('academy/{course}/lessons/{lesson}', [CourseContentController::class, 'destroyLesson'])->name('academy.lessons.destroy');
        Route::post('academy/{course}/questions', [CourseContentController::class, 'storeQuestion'])->name('academy.questions.store');
        Route::delete('academy/{course}/questions/{question}', [CourseContentController::class, 'destroyQuestion'])->name('academy.questions.destroy');

        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::patch('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });
});

require __DIR__.'/settings.php';
