{{--
  The welcome an admitted official gets.

  Everything is styled inline. The shared theme is inlined by Laravel for the
  components it ships, but the pieces here — the steps and the exam strip, and
  the register card it pulls in — are bespoke, and a mail client that drops a
  <style> block would leave them as unstyled rows. Tables and inline styles
  render the same in Outlook, Gmail and Apple Mail; flex, grid and border-radius
  do not, so nothing here depends on them. Rounded corners are used where
  squaring them off in Outlook costs nothing.
--}}
<x-mail::message>
# Welcome to the pool deck, {{ $firstName }}

The technical committee has approved your application. You are on the national
officials register, you are training as a {{ $category }}, and everything you
need to start is below.

{{-- The hero: being on the register, with the number that proves it. --}}
@include('mail.officials.partials.register-card', [
    'registrationNumber' => $registrationNumber,
    'rows' => array_filter([
        'Training as' => $category,
        'Registered through' => $club,
        'Province' => $province,
        'Standing' => 'Learner',
    ]),
])

@if ($accountIsNew)
An account has been opened for you. Set a password and it is yours — nothing
secret travels in this email.
@else
You already have an account here, so keep signing in with the password you
have. Your new role as an official is on it.
@endif

<x-mail::button :url="$actionUrl" color="success">
{{ $actionLabel }}
</x-mail::button>

## What happens next

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 18px 0 8px;">
@foreach ($steps as $index => $step)
<tr>
<td width="30" valign="top" style="padding: 0 14px 0 0;">
<table cellpadding="0" cellspacing="0" role="presentation"><tr>
<td width="26" height="26" align="center" valign="middle" style="width: 26px; height: 26px; background-color: #0b2a4a; border-radius: 13px; color: #ffffff; font-size: 13px; font-weight: bold; line-height: 26px; text-align: center;">{{ $index + 1 }}</td>
</tr></table>
</td>
<td valign="top" style="padding: 0 0 20px;">
<p style="margin: 0 0 4px; font-size: 16px; line-height: 1.35; font-weight: bold; color: #111111; text-align: left;">{{ $step['title'] }}</p>
<p style="margin: 0; font-size: 15px; line-height: 1.6; color: #52525b; text-align: left;">{{ $step['body'] }}</p>
</td>
</tr>
@endforeach
</table>

@if ($exam)
{{-- The exam in four figures, kept quiet so the register card stays the one
     loud thing in the message. --}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 4px 0 26px; background-color: #f7f9fb; border-radius: 4px;">
<tr>
<td style="padding: 16px 18px 6px;">
<p style="margin: 0; font-size: 13px; line-height: 1.4; color: #52525b; text-align: left;">The {{ $course->title }} exam</p>
</td>
</tr>
<tr>
<td style="padding: 0 18px 18px;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
@foreach ($exam as $figure)
<td width="25%" valign="top" style="padding: 0 10px 0 0;">
<p style="margin: 0; font-size: 22px; line-height: 1.2; font-weight: bold; color: #0b2a4a; text-align: left;">{{ $figure['value'] }}</p>
<p style="margin: 0; font-size: 12px; line-height: 1.4; color: #52525b; text-align: left;">{{ $figure['label'] }}</p>
</td>
@endforeach
</tr>
</table>
</td>
</tr>
</table>
@endif

## Where to find things

{{-- Written as raw anchors rather than markdown links because the theme sets
     word-break: break-all on body links — right for a bare URL, which would
     otherwise overflow, but it splits link *text* mid-word too ("the pathw
     ay"). An inline style survives the inliner and overrides it. --}}
<a href="{{ $portalUrl }}" style="color: #007a3d; font-weight: bold; word-break: normal;">Your portal</a> is the one page that answers how far along
you are: the lessons you have left, your exam attempts, the sessions a referee
has signed, and your certificates once they are issued.

@if ($course)
<a href="{{ $academyUrl }}" style="color: #007a3d; font-weight: bold; word-break: normal;">The Academy</a> holds your course, and remembers where you
left off.
@else
<a href="{{ $academyUrl }}" style="color: #007a3d; font-weight: bold; word-break: normal;">The Academy</a> is where your course will appear, once one
is published for your category.
@endif

<a href="{{ $pathwayUrl }}" style="color: #007a3d; font-weight: bold; word-break: normal;">The pathway</a> sets out every category, the grades above
it, and what each one is responsible for on the deck.

Keep {{ $registrationNumber }} somewhere you can find it — it is your number on
the register, and quoting it saves everybody time. If your club or your details
change, your club administrator can put it right; ask them rather than applying
again, so the sessions you have already done stay attached to you.

<x-mail::subcopy>
@if ($accountIsNew)
The button above sets your password and is good for sixty minutes. After that,
use **Forgot your password?** on the sign-in page and we will send another.
@endif
Your application reference was {{ $reference }}.
</x-mail::subcopy>

Same Waters. Brighter Futures.<br>
**The Technical Committee** · Zimbabwe Swimming
</x-mail::message>
