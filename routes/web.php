<?php

use App\Http\Controllers\Academy\AcademyController;
use App\Http\Controllers\Academy\CertificateController;
use App\Http\Controllers\Academy\ExamController;
use App\Http\Controllers\Admin\AccreditationController;
use App\Http\Controllers\Admin\CommitteeController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\CourseContentController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\FinalsController;
use App\Http\Controllers\Admin\GalaController;
use App\Http\Controllers\Admin\GalaEntryController;
use App\Http\Controllers\Admin\GalaProgrammeController;
use App\Http\Controllers\Admin\GalaResultsFileController;
use App\Http\Controllers\Admin\GalaSanctionController;
use App\Http\Controllers\Admin\MeetFileController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\OfficialApplicationController;
use App\Http\Controllers\Admin\OfficialTrainingController as AdminOfficialTrainingController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\OrganizationSwitchController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\RelayTeamController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SeedingController;
use App\Http\Controllers\Admin\SponsorController;
use App\Http\Controllers\Admin\SwimRecordController;
use App\Http\Controllers\Admin\TechnicalOfficialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VenueController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Portal\OfficialPortalController;
use App\Http\Controllers\Public\CalendarController;
use App\Http\Controllers\Public\DirectoryController;
use App\Http\Controllers\Public\FeeController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LibraryController;
use App\Http\Controllers\Public\NewsController;
use App\Http\Controllers\Public\OfficialApplicationController as PublicOfficialApplicationController;
use App\Http\Controllers\Public\OfficialsPathwayController;
use App\Http\Controllers\Public\OfficialsRegisterController;
use App\Http\Controllers\Public\OfficialTrainingController;
use App\Http\Controllers\Public\RecordController;
use App\Http\Controllers\Public\ResultsController;
use App\Http\Controllers\Public\ResultsFileController;
use Illuminate\Support\Facades\Route;

/*
 * The public website. No login needed.
 */
Route::get('/', HomeController::class)->name('home');
Route::get('calendar', [CalendarController::class, 'index'])->name('calendar');
Route::get('calendar/{gala}', [CalendarController::class, 'show'])->name('galas.show');
Route::get('clubs', [DirectoryController::class, 'clubs'])->name('clubs');
/*
 * A province and then a club beneath it, rather than one flat list. The
 * federation asked to be able to click a province, see the clubs affiliated
 * to it, and click through to a club's coach, pool and contacts — which is
 * also how a parent looks for somewhere to swim.
 */
Route::get('clubs/{province}', [DirectoryController::class, 'province'])->name('provinces.show');
Route::get('clubs/{province}/{club}', [DirectoryController::class, 'club'])->name('clubs.show');
Route::get('venues', [DirectoryController::class, 'venues'])->name('venues');
Route::get('fees', FeeController::class)->name('fees');
Route::get('about', [DirectoryController::class, 'about'])->name('about');
Route::get('records', RecordController::class)->name('records');
Route::get('results', [ResultsController::class, 'index'])->name('results.index');
Route::get('results/{gala}', [ResultsController::class, 'show'])->name('results.show');
Route::get('results/{gala}/live', [ResultsController::class, 'live'])->name('results.live');

/*
 * The results file itself. Streamed rather than linked at the disk, so an
 * unpublished gala's results cannot be reached by guessing a filename.
 */
Route::get('results/{gala}/file/{document}', ResultsFileController::class)->name('results.file');
Route::get('verify/{certificate}', [CertificateController::class, 'verify'])->name('certificates.verify');

/*
 * The certificate itself, opened in the browser's PDF viewer. Behind auth
 * because it carries a person's name and their standing; the verify page
 * above is the open one.
 */
Route::get('certificates/{certificate}/pdf', [CertificateController::class, 'pdf'])
    ->middleware('auth')
    ->name('certificates.pdf');
Route::get('officials', OfficialsPathwayController::class)->name('officials');
// The roll of people who have finished the pathway the page above describes.
Route::get('officials/register', OfficialsRegisterController::class)->name('officials.register');

/*
 * Clinics and exam sittings. Open to anybody, with or without an account:
 * the people a clinic most needs to reach are not on the register yet.
 */
Route::get('officials/training', [OfficialTrainingController::class, 'index'])->name('officials.training');
Route::get('officials/training/{training}', [OfficialTrainingController::class, 'show'])
    ->name('officials.training.show');
Route::post('officials/training/{training}', [OfficialTrainingController::class, 'register'])
    ->middleware('throttle:6,1')
    ->name('officials.training.register');

/*
 * Applying to officiate. Open to anybody: an applicant has no account until
 * the technical committee approves them, which is the point of the queue.
 */
Route::get('officials/apply', [PublicOfficialApplicationController::class, 'create'])->name('officials.apply');
Route::post('officials/apply', [PublicOfficialApplicationController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('officials.apply.store');
Route::get('officials/applied', [PublicOfficialApplicationController::class, 'applied'])->name('officials.applied');
Route::get('news', [NewsController::class, 'index'])->name('news.index');
Route::get('library', [LibraryController::class, 'index'])->name('library.index');
Route::get('library/{document}', [LibraryController::class, 'download'])->name('library.download');
Route::get('news/{post}', [NewsController::class, 'show'])->name('news.show');

/*
 * The secure backend. Every route is scoped to the organisation the user is
 * acting in, and each policy decides what that user may reach.
 */
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    /*
     * Where a technical official works from. Separate from the board
     * dashboard because the questions are different: not how many swimmers
     * are registered, but how far through the course they are and what the
     * referee still has to sign.
     */
    Route::get('portal', OfficialPortalController::class)->name('portal');

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
            Route::get('official-applications', [OfficialApplicationController::class, 'export'])->name('official-applications');
            Route::get('records', [SwimRecordController::class, 'export'])->name('records');
            Route::get('users', [UserController::class, 'export'])->name('users');
        });

        /*
         * The queue of people asking to join the officials register.
         * Approving one opens an account, so it sits behind the same
         * permission as creating an official by hand.
         */
        /*
         * The MeetManager export a gala is published from. Times are
         * recorded there on the day rather than typed in here.
         */
        Route::post('galas/{gala}/results-files', [GalaResultsFileController::class, 'store'])->name('galas.results-files.store');
        Route::delete('galas/{gala}/results-files/{document}', [GalaResultsFileController::class, 'destroy'])->name('galas.results-files.destroy');

        /*
         * The wording and pictures on the public pages. Kept separate from
         * news and documents: those are things the federation publishes,
         * this is what the site itself says.
         */
        Route::get('content', [ContentController::class, 'index'])->name('content.index');
        Route::put('content', [ContentController::class, 'update'])->name('content.update');
        Route::post('content/image', [ContentController::class, 'image'])->name('content.image');
        Route::delete('content/image', [ContentController::class, 'resetImage'])->name('content.image.reset');

        /*
         * The news page. An article is published by its date rather than by
         * a flag, so one can be written now and appear on the morning of a
         * gala without anybody having to remember.
         */
        Route::resource('news', PostController::class)
            ->parameters(['news' => 'post'])
            ->except(['show']);

        Route::resource('sponsors', SponsorController::class)->except(['show']);

        /*
         * Committees belong to the body that elected them, so this page shows
         * the one being acted in rather than the whole federation.
         */
        Route::get('committees', [CommitteeController::class, 'index'])->name('committees.index');
        Route::post('committees', [CommitteeController::class, 'store'])->name('committees.store');
        Route::put('committees/{committee}', [CommitteeController::class, 'update'])->name('committees.update');
        Route::delete('committees/{committee}', [CommitteeController::class, 'destroy'])->name('committees.destroy');
        Route::post('committees/{committee}/members', [CommitteeController::class, 'storeMember'])->name('committees.members.store');
        Route::put('committees/{committee}/members/{member}', [CommitteeController::class, 'updateMember'])->name('committees.members.update');
        Route::delete('committees/{committee}/members/{member}', [CommitteeController::class, 'destroyMember'])->name('committees.members.destroy');

        /*
         * The handbook, the rules and the forms. Results files live in the
         * same table but belong to a gala, and are managed from its screen.
         */
        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::put('documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
        Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

        Route::get('official-applications', [OfficialApplicationController::class, 'index'])->name('official-applications.index');
        Route::post('official-applications/{application}/approve', [OfficialApplicationController::class, 'approve'])->name('official-applications.approve');
        Route::post('official-applications/{application}/decline', [OfficialApplicationController::class, 'decline'])->name('official-applications.decline');

        Route::resource('organizations', OrganizationController::class)->except(['show']);
        Route::resource('members', MemberController::class)->except(['show']);
        Route::resource('venues', VenueController::class)->except(['show']);
        Route::resource('galas', GalaController::class);

        Route::resource('officials', TechnicalOfficialController::class)
            ->except(['destroy'])
            ->parameters(['officials' => 'official']);

        // Clinics, exam sittings and refreshers on the officials' calendar.
        Route::get('training/{training}/export', [AdminOfficialTrainingController::class, 'export'])
            ->name('training.export');
        Route::resource('training', AdminOfficialTrainingController::class)
            ->parameters(['training' => 'training']);

        Route::post('galas/{gala}/sanction', [GalaSanctionController::class, 'store'])->name('galas.sanction');

        /*
         * The programme: sessions and the events in each. The public gala
         * page could always show one; until now there was no way to type it.
         */
        Route::get('galas/{gala}/programme', [GalaProgrammeController::class, 'index'])->name('galas.programme');
        Route::post('galas/{gala}/sessions', [GalaProgrammeController::class, 'storeSession'])->name('galas.sessions.store');
        Route::patch('galas/{gala}/sessions/{session}', [GalaProgrammeController::class, 'updateSession'])->name('galas.sessions.update');
        Route::delete('galas/{gala}/sessions/{session}', [GalaProgrammeController::class, 'destroySession'])->name('galas.sessions.destroy');
        Route::post('galas/{gala}/events', [GalaProgrammeController::class, 'storeEvent'])->name('galas.events.store');
        Route::patch('galas/{gala}/events/{event}', [GalaProgrammeController::class, 'updateEvent'])->name('galas.events.update');
        Route::delete('galas/{gala}/events/{event}', [GalaProgrammeController::class, 'destroyEvent'])->name('galas.events.destroy');

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

        /*
         * Opens a login for an official who was written onto the register
         * before anybody could be invited, and tells them they are on it.
         */
        Route::post('officials/{official}/invite', [TechnicalOfficialController::class, 'invite'])
            ->name('officials.invite');

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
