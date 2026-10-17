@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ asset('brand/mark-mail.png') }}" width="32" height="18" alt="" class="brand-mark">{!! $slot !!}
</a>
</td>
</tr>
