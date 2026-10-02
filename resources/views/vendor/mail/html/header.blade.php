@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; color: #2563EB; font-size: 20px; font-weight: 700; text-decoration: none;">
{!! $slot !!}
</a>
</td>
</tr>
