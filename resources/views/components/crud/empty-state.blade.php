@props(['colspan' => 1, 'message' => 'Belum ada data.'])

<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-10 text-center text-slate-400">
        {{ $message }}
    </td>
</tr>
