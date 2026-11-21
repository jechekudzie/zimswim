{{--
  The welcome for an official written onto the register by a registrar.

  Styled inline over tables for the same reason as the learner welcome: these
  pieces are bespoke to the message and must survive a client that strips a
  style block. See mail/officials/partials/register-card.blade.php.
--}}
<x-mail::message>
# You are on the register, {{ $firstName }}

Zimbabwe Swimming keeps one national register of technical officials, and you
are now on it. This is your record, and the login that goes with it.

@include('mail.officials.partials.register-card', [
    'registrationNumber' => $registrationNumber,
    'rows' => array_filter([
        'Registered through' => $club,
        'Province' => $province,
        'Standing' => $standing,
    ]),
])

@if ($accountIsNew)
An account has been opened for you. Set a password and it is yours — nothing
secret travels in this email.
@else
You already have an account here, so keep signing in with the password you
have. Your place on the officials register is now on it.
@endif

<x-mail::button :url="$actionUrl" color="success">
{{ $actionLabel }}
</x-mail::button>

@if ($accreditations !== [])
## What the register holds for you

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 18px 0 22px;">
<tr>
<th width="46%" align="left" style="padding: 0 12px 8px 0; border-bottom: 1px solid #dbe7f3; font-size: 12px; line-height: 1.4; font-weight: normal; color: #52525b; text-align: left;">Category</th>
<th width="30%" align="left" style="padding: 0 12px 8px 0; border-bottom: 1px solid #dbe7f3; font-size: 12px; line-height: 1.4; font-weight: normal; color: #52525b; text-align: left;">Grade</th>
<th align="left" style="padding: 0 0 8px; border-bottom: 1px solid #dbe7f3; font-size: 12px; line-height: 1.4; font-weight: normal; color: #52525b; text-align: left;">Valid until</th>
</tr>
@foreach ($accreditations as $accreditation)
<tr>
<td style="padding: 10px 12px 10px 0; border-bottom: 1px solid #eef3f8; font-size: 15px; line-height: 1.4; font-weight: bold; color: #111111;">{{ $accreditation['category'] }}</td>
<td style="padding: 10px 12px 10px 0; border-bottom: 1px solid #eef3f8; font-size: 15px; line-height: 1.4; color: #52525b;">{{ $accreditation['grade'] }}</td>
<td style="padding: 10px 0; border-bottom: 1px solid #eef3f8; font-size: 15px; line-height: 1.4; color: #52525b;">{{ $accreditation['expires'] ?? 'No expiry' }}</td>
</tr>
@endforeach
</table>

**Please check this is right.** It was typed in from the federation's records,
and you are the person most likely to spot a grade or a date that is wrong. If
anything is off, tell your club administrator rather than applying again — they
can correct it, and your history stays attached to you.
@else
## Please check your record

Your portal shows everything the register holds for you. Nothing is recorded
against you yet beyond your details, so if you already hold an accreditation
the federation has not got it written down — tell your club administrator and
they will put it on.
@endif

## What your portal is for

{{-- Raw anchors: see mail/officials/welcome.blade.php for why. --}}
<a href="{{ $portalUrl }}" style="color: #007a3d; font-weight: bold; word-break: normal;">Your portal</a> is where you will find the galas you have
been put on, the sessions a referee has signed, your current accreditations and
the dates they run out, and your certificates once they are issued. It is also
where you will be warned before an accreditation lapses, rather than finding
out at a gala.

If you want to add a category to the ones you already hold,
<a href="{{ $pathwayUrl }}" style="color: #007a3d; font-weight: bold; word-break: normal;">the pathway</a> sets out what each one needs.

@if ($accountIsNew)
<x-mail::subcopy>
The button above sets your password and is good for sixty minutes. After that,
use **Forgot your password?** on the sign-in page and we will send another.
</x-mail::subcopy>
@endif

Same Waters. Brighter Futures.<br>
**The Technical Committee** · Zimbabwe Swimming
</x-mail::message>
