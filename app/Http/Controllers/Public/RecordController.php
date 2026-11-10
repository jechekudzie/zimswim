<?php

namespace App\Http\Controllers\Public;

use App\Enums\Course;
use App\Http\Controllers\Controller;
use App\Models\SwimRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecordController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $course = $request->string('course')->toString() ?: Course::LongCourse->value;
        $gender = $request->string('gender')->toString() ?: 'female';

        $records = SwimRecord::query()
            ->ratified()
            ->where('scope', 'national')
            ->where('course', $course)
            ->where('gender', $gender)
            ->orderBy('age_group')
            ->orderBy('stroke')
            ->orderBy('distance')
            ->get()
            ->map(fn (SwimRecord $record): array => [
                'age_group' => $record->age_group,
                'event' => $record->eventTitle(),
                'stroke' => $record->stroke->label(),
                'distance' => $record->distance,
                'time' => $record->formattedTime(),
                'holder' => $record->holder_name,
                'venue' => $record->venue_name,
                'set_on' => $record->set_on?->toDateString(),
            ])
            ->groupBy('age_group');

        return Inertia::render('public/records', [
            'records' => $records,
            'filters' => ['course' => $course, 'gender' => $gender],
            'courses' => Course::options(),
            'pendingCount' => SwimRecord::query()->awaitingRatification()->count(),
        ]);
    }
}
