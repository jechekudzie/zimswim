<?php

namespace Database\Seeders;

use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use App\Enums\QuestionType;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseQuestion;
use App\Models\CourseQuestionOption;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The Officials Academy launches with Timekeeper and Judge of Stroke, as set
 * out in the proposal. Later categories are created as drafts so the technical
 * committee can fill them in.
 */
class AcademySeeder extends Seeder
{
    public function run(): void
    {
        $national = Organization::where('slug', 'zimbabwe-swimming')->first();

        foreach ($this->courses() as $position => $definition) {
            $course = Course::updateOrCreate(
                ['slug' => Str::slug($definition['title'])],
                [
                    'organization_id' => $national?->getKey(),
                    'title' => $definition['title'],
                    'official_category' => $definition['category'],
                    'awards_grade' => OfficialGrade::Provincial,
                    'summary' => $definition['summary'],
                    'description' => $definition['description'] ?? null,
                    'pass_mark' => 80,
                    'max_attempts' => 3,
                    'exam_question_count' => $definition['exam_questions'],
                    'exam_minutes' => 30,
                    'practical_sessions_required' => $definition['category']->learnerSessionsRequired(),
                    'minimum_age' => $definition['category']->minimumAge(),
                    'fee' => 0,
                    'free_for_registered_parents' => true,
                    'is_published' => $definition['published'],
                    'position' => $position,
                ],
            );

            foreach ($definition['lessons'] ?? [] as $index => $lesson) {
                CourseLesson::updateOrCreate(
                    ['course_id' => $course->getKey(), 'slug' => Str::slug($lesson['title'])],
                    [
                        'title' => $lesson['title'],
                        'position' => $index,
                        'summary' => $lesson['summary'],
                        'content' => $lesson['content'],
                        'video_seconds' => $lesson['minutes'] * 60,
                        'is_published' => true,
                    ],
                );
            }

            foreach ($definition['questions'] ?? [] as $question) {
                $this->question($course, $question);
            }
        }
    }

    /**
     * @param  array{prompt: string, rule?: string, explanation?: string, options: array<int, array{0: string, 1: bool}>}  $data
     */
    private function question(Course $course, array $data): void
    {
        $question = CourseQuestion::updateOrCreate(
            ['course_id' => $course->getKey(), 'prompt' => $data['prompt']],
            [
                'type' => QuestionType::Single,
                'rule_reference' => $data['rule'] ?? null,
                'explanation' => $data['explanation'] ?? null,
                'in_exam_bank' => true,
                'is_active' => true,
            ],
        );

        $question->options()->delete();

        foreach (array_values($data['options']) as $position => [$label, $isCorrect]) {
            CourseQuestionOption::create([
                'course_question_id' => $question->getKey(),
                'label' => $label,
                'is_correct' => $isCorrect,
                'position' => $position,
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function courses(): array
    {
        return [
            [
                'title' => 'Timekeeper',
                'category' => OfficialCategory::Timekeeper,
                'published' => true,
                'exam_questions' => 10,
                'summary' => 'Where almost every official in Zimbabwe starts. Learn the rules you need, how to run a stopwatch, and how times are recorded and backed up.',
                'description' => "Timekeeping is the entry point to officiating. You do not need a swimming background: you need to be reliable, attentive and willing to learn.\n\nThis course covers the rules a timekeeper must know, the mechanics of manual and backup timing, and what happens to a time once you have written it down. After the theory and a short exam, you work six pool-deck sessions as a learner before you are accredited.",
                'lessons' => [
                    [
                        'title' => 'What a timekeeper does',
                        'minutes' => 7,
                        'summary' => 'Your place on the pool deck, who you report to, and what the team around you is doing.',
                        'content' => "A timekeeper is assigned to a lane and records the time of every swimmer in that lane for the whole session.\n\nYou report to the chief timekeeper, who collects times, resolves disagreements between watches, and passes times to the control room. The referee has overall charge of the meet; the starter sends the races away; judges of stroke and inspectors of turns watch the swimming itself. Your job is the clock and nothing else: you do not judge strokes or turns.\n\nTo be recognised as having worked a meet you must work at least 50% of its sessions and sign the official register for that session.",
                    ],
                    [
                        'title' => 'Starting and stopping the watch',
                        'minutes' => 9,
                        'summary' => 'React to the signal, not to the swimmer, and stop on the touch.',
                        'content' => "Start your watch on the starting signal: the strobe light if one is fitted, otherwise the sound. Do not anticipate the start and do not react to the swimmers leaving the blocks.\n\nStop your watch when any part of the swimmer touches the wall at the finish of the race. Watch the wall, not the swimmer's head. For races of more than one length, do not stop at the turn.\n\nIf you miss a start or a finish, say so immediately. An honest missing time is recoverable; a guessed time is not.",
                    ],
                    [
                        'title' => 'Recording and backing up times',
                        'minutes' => 8,
                        'summary' => 'Reading to two decimal places, writing it down, and what happens when watches disagree.',
                        'content' => "Read the watch to two decimal places and write the time exactly as shown, for example 1:02.34 or 58.07. Never round and never adjust a time because it looks wrong.\n\nWhere there are two or three watches on a lane, the chief timekeeper decides the official time. With three watches, the middle time stands. With two that disagree, the average is taken. Where automatic officiating equipment is in use, your watch is the backup if the pad fails.\n\nHand your card to the chief timekeeper at the end of each heat and keep your lane number with it.",
                    ],
                    [
                        'title' => 'Conduct, kit and the call room',
                        'minutes' => 6,
                        'summary' => 'Dress code, behaviour, and the boundaries between officials and swimmers.',
                        'content' => "Technical officials at finals are dressed in white shirts with navy blue or black trousers or skirts, and closed shoes. No jeggings, no shorts at finals.\n\nStay off your phone on the deck, do not coach, and do not discuss a swimmer's performance with parents. If a parent raises something with you, direct them to their club, which raises it with the province.\n\nThe call room is for competing swimmers and call room supervisors only. Alternates report to the marshalling area for finals.",
                    ],
                ],
                'questions' => [
                    ['prompt' => 'When does a timekeeper start the watch?', 'rule' => '4.4', 'explanation' => 'The watch follows the official starting signal, never the swimmers.', 'options' => [['On the starting signal', true], ['When the swimmer leaves the block', false], ['When the referee raises an arm', false], ['When the swimmer enters the water', false]]],
                    ['prompt' => 'When does a timekeeper stop the watch?', 'rule' => '5.2', 'options' => [['When any part of the swimmer touches the wall at the finish', true], ['When the swimmer stops swimming', false], ['When the swimmer leaves the water', false], ['When the next heat is called', false]]],
                    ['prompt' => 'To how many decimal places is a manual time read?', 'options' => [['Two', true], ['One', false], ['Three', false], ['None, it is rounded to the second', false]]],
                    ['prompt' => 'With three watches on a lane that all differ, which time stands?', 'options' => [['The middle time', true], ['The fastest time', false], ['The slowest time', false], ['The chief timekeeper decides at random', false]]],
                    ['prompt' => 'With two watches that disagree, what is the official time?', 'options' => [['The average of the two', true], ['The faster of the two', false], ['The slower of the two', false], ['The swim is not timed', false]]],
                    ['prompt' => 'Who does a timekeeper report to?', 'options' => [['The chief timekeeper', true], ['The starter', false], ['The swimmer’s coach', false], ['The announcer', false]]],
                    ['prompt' => 'What is the minimum age to qualify as a timekeeper in Zimbabwe?', 'options' => [['13 years', true], ['16 years', false], ['18 years', false], ['There is no minimum', false]]],
                    ['prompt' => 'How many pool-deck sessions must be worked as a learner before timekeeper accreditation?', 'options' => [['6', true], ['2', false], ['4', false], ['10', false]]],
                    ['prompt' => 'A timekeeper notices what looks like an illegal turn. What should they do?', 'options' => [['Nothing; turns are judged by the inspector of turns', true], ['Raise a hand and stop the race', false], ['Record the time and add a note disqualifying the swimmer', false], ['Tell the swimmer after the race', false]]],
                    ['prompt' => 'What proportion of a meet’s sessions must be worked to be recognised as having worked that meet?', 'options' => [['At least 50%', true], ['At least 25%', false], ['All of them', false], ['At least one session', false]]],
                    ['prompt' => 'A timekeeper misses the finish touch. What is the correct action?', 'options' => [['Report the missed time to the chief timekeeper immediately', true], ['Estimate the time from the swimmer next to them', false], ['Write down the watch time anyway', false], ['Leave the card blank and say nothing', false]]],
                    ['prompt' => 'Where automatic officiating equipment is in use, what is the role of the timekeeper’s watch?', 'options' => [['It is the backup time if the touch pad fails', true], ['It replaces the pad time', false], ['It is not needed at all', false], ['It is used only for relays', false]]],
                ],
            ],
            [
                'title' => 'Judge of Stroke',
                'category' => OfficialCategory::JudgeOfStroke,
                'published' => true,
                'exam_questions' => 10,
                'summary' => 'Starts, turns and finishes for each stroke, relay takeovers, and how an infringement is reported.',
                'description' => "A judge of stroke watches the swimming itself and reports infringements to the referee. You must already be a qualified inspector of turns.\n\nThis course covers the four strokes in full, the medley rules, relay takeovers and the disqualification codes used on the World Aquatics infraction sheet. Disqualification is never personal: you report what you saw, against a rule, and the referee decides.",
                'lessons' => [
                    [
                        'title' => 'Freestyle and backstroke',
                        'minutes' => 11,
                        'summary' => 'The 15 metre rule, remaining on the back, and turn and finish requirements.',
                        'content' => "In freestyle the swimmer may swim any style. The head must break the surface of the water at or before the 15 metre mark after the start and after each turn, and some part of the swimmer must touch the wall at each turn and at the finish.\n\nIn backstroke the swimmer must remain on the back throughout, except when executing a turn. The head must break the surface by the 15 metre mark. During the turn the shoulders may turn past the vertical to the breast, after which a continuous single or simultaneous double arm pull may be used to initiate the turn. The swimmer must have returned to the back on leaving the wall, and must finish on the back.",
                    ],
                    [
                        'title' => 'Breaststroke and butterfly',
                        'minutes' => 13,
                        'summary' => 'Simultaneity, the stroke cycle, kicks, and the two-hand touch.',
                        'content' => "Breaststroke: after the start and each turn one arm stroke and one leg kick are permitted while wholly submerged, with a single butterfly kick allowed before the first breaststroke kick. The body must be on the breast; arm and leg movements must be simultaneous and in the same horizontal plane. Touch at the turn and finish is with both hands separated and simultaneously.\n\nButterfly: both arms must be brought forward together over the water and backward simultaneously. All up-and-down movements of the legs must be simultaneous. The body must be kept on the breast. Touch at the turn and finish is with both hands separated and simultaneously.",
                    ],
                    [
                        'title' => 'Medley, relays and reporting',
                        'minutes' => 10,
                        'summary' => 'Stroke order, takeovers, and how to write up an infringement.',
                        'content' => "In individual medley the order is butterfly, backstroke, breaststroke, freestyle. In the medley relay it is backstroke, breaststroke, butterfly, freestyle. In the freestyle section of a medley the swimmer may not swim backstroke, breaststroke or butterfly.\n\nIn relays, the feet of the departing swimmer must not lose contact with the starting platform before the preceding team-mate touches the wall. Teams must swim in the order listed.\n\nWhen you see an infringement, signal by raising a hand, then report to the referee in writing with the swimmer's lane, the event, the rule number and what you saw. Never discuss it with the swimmer, the coach or a parent.",
                    ],
                ],
                'questions' => [
                    ['prompt' => 'In freestyle, by which mark must the head break the surface after the start?', 'rule' => '5.3', 'options' => [['15 metres', true], ['10 metres', false], ['20 metres', false], ['5 metres', false]]],
                    ['prompt' => 'In backstroke, when may a swimmer leave the position on the back?', 'rule' => '6.2', 'options' => [['Only when executing a turn', true], ['At any time during the race', false], ['Only in the last 5 metres', false], ['Never', false]]],
                    ['prompt' => 'How must a breaststroke swimmer touch at the turn and finish?', 'rule' => '7.7', 'options' => [['With both hands separated and simultaneously', true], ['With one hand', false], ['With any part of the body', false], ['With both hands together and overlapping', false]]],
                    ['prompt' => 'In butterfly, how must the arms be brought forward?', 'rule' => '8.3', 'options' => [['Simultaneously over the water', true], ['Alternately over the water', false], ['Simultaneously under the water', false], ['In any manner', false]]],
                    ['prompt' => 'What is the stroke order in an individual medley?', 'rule' => '9.2', 'options' => [['Butterfly, backstroke, breaststroke, freestyle', true], ['Backstroke, breaststroke, butterfly, freestyle', false], ['Freestyle, butterfly, backstroke, breaststroke', false], ['Breaststroke, butterfly, backstroke, freestyle', false]]],
                    ['prompt' => 'What is the stroke order in a medley relay?', 'rule' => '9.3', 'options' => [['Backstroke, breaststroke, butterfly, freestyle', true], ['Butterfly, backstroke, breaststroke, freestyle', false], ['Freestyle, backstroke, breaststroke, butterfly', false], ['Breaststroke, backstroke, butterfly, freestyle', false]]],
                    ['prompt' => 'A relay swimmer’s feet leave the blocks before their team-mate touches. What is this?', 'rule' => '10.4.5', 'options' => [['A disqualification of the relay team', true], ['A warning only', false], ['Permitted if the gap is under half a second', false], ['A matter for the starter only', false]]],
                    ['prompt' => 'In the freestyle section of an individual medley, which strokes may not be swum?', 'rule' => '9.1', 'options' => [['Backstroke, breaststroke and butterfly', true], ['Any stroke may be swum', false], ['Only butterfly is barred', false], ['Only backstroke is barred', false]]],
                    ['prompt' => 'Who makes the final decision on a disqualification?', 'options' => [['The referee', true], ['The judge of stroke who saw it', false], ['The chief timekeeper', false], ['The competition manager', false]]],
                    ['prompt' => 'How many breaststroke kicks and arm strokes are permitted wholly submerged after the start?', 'rule' => '7.1', 'options' => [['One arm stroke and one leg kick, plus a single butterfly kick', true], ['Two of each', false], ['Unlimited until 15 metres', false], ['None at all', false]]],
                    ['prompt' => 'Which minimum age applies to a judge of stroke?', 'options' => [['16 years', true], ['13 years', false], ['18 years', false], ['21 years', false]]],
                    ['prompt' => 'A judge of stroke must already be a qualified what?', 'options' => [['Inspector of turns', true], ['Starter', false], ['Referee', false], ['Announcer', false]]],
                ],
            ],
            [
                'title' => 'Inspector of Turns',
                'category' => OfficialCategory::InspectorOfTurns,
                'published' => false,
                'exam_questions' => 10,
                'summary' => 'Turn and finish requirements for every stroke. Content to be supplied by the technical officials convenor.',
            ],
            [
                'title' => 'Chief Timekeeper',
                'category' => OfficialCategory::ChiefTimekeeper,
                'published' => false,
                'exam_questions' => 10,
                'summary' => 'Running a bank of timekeepers, resolving watches and passing times to the control room.',
            ],
            [
                'title' => 'Starter',
                'category' => OfficialCategory::Starter,
                'published' => false,
                'exam_questions' => 10,
                'summary' => 'Start protocol, false starts and signals. Content to be supplied by the technical officials convenor.',
            ],
            [
                'title' => 'Referee',
                'category' => OfficialCategory::Referee,
                'published' => false,
                'exam_questions' => 10,
                'summary' => 'Protests, protocol and running a panel of officials.',
            ],
        ];
    }
}
