@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
{{-- The wordmark. Most clients block images, which is why the alt text is
     the federation's name rather than the word "logo". --}}
<img src="{{ asset('brand/logo.png') }}" class="logo" alt="Zimbabwe Swimming">
</a>
{{-- The flag rule, as four table cells: a gradient would not render. --}}
<table class="flag-rule" align="center" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="flag-green">&nbsp;</td>
<td class="flag-gold">&nbsp;</td>
<td class="flag-red">&nbsp;</td>
<td class="flag-ink">&nbsp;</td>
</tr>
</table>
</td>
</tr>
