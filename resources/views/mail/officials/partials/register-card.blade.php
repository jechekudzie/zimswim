{{--
  The register card: an official's number, and the few facts that sit under it.

  Shared by every message that has to tell somebody where they stand on the
  register, so the number looks the same whichever door they came in through.

  @param string $registrationNumber
  @param array<string, string> $rows  Label => value, in the order to show them.
--}}
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 28px 0;">
<tr>
<td style="background-color: #f3f8fe; border: 1px solid #dbe7f3; border-left: 4px solid #007a3d; border-radius: 4px; padding: 22px 24px;">
<p style="margin: 0 0 2px; font-size: 13px; line-height: 1.4; color: #52525b; text-align: left;">Your registration number</p>
<p style="margin: 0; font-size: 30px; line-height: 1.15; font-weight: bold; letter-spacing: 1px; color: #0b2a4a; text-align: left;">{{ $registrationNumber }}</p>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top: 18px; border-top: 1px solid #dbe7f3;">
@foreach ($rows as $label => $value)
<tr>
<td width="38%" valign="top" style="padding: {{ $loop->first ? '10px' : '8px' }} 12px 0 0; font-size: 14px; line-height: 1.4; color: #52525b;">{{ $label }}</td>
<td valign="top" style="padding: {{ $loop->first ? '10px' : '8px' }} 0 0; font-size: 14px; line-height: 1.4; font-weight: bold; color: #111111;">{{ $value }}</td>
</tr>
@endforeach
</table>
</td>
</tr>
</table>
