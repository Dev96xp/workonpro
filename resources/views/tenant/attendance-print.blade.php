<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ __('tenant.attendance.heading') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white p-8 text-zinc-900">
    <h1 class="text-2xl font-bold">{{ tenant('name') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('tenant.attendance.heading') }}</p>

    <p class="mt-4 text-sm">
        <strong>{{ __('tenant.attendance.from_label') }}:</strong> {{ \Illuminate\Support\Carbon::parse($startDate)->format('d/m/Y') }}
        &nbsp;—&nbsp;
        <strong>{{ __('tenant.attendance.to_label') }}:</strong> {{ \Illuminate\Support\Carbon::parse($endDate)->format('d/m/Y') }}
    </p>
    @if ($employeeName)
        <p class="mt-1 text-sm text-zinc-500">{{ __('tenant.attendance.employee_label') }}: {{ $employeeName }}</p>
    @endif
    @if ($location)
        <p class="mt-1 text-sm text-zinc-500">{{ __('tenant.attendance.location_label') }}: {{ $location }}</p>
    @endif

    <table class="mt-4 w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-300 text-left">
                <th class="py-1">{{ __('tenant.attendance.employee_label') }}</th>
                <th class="py-1">{{ __('tenant.attendance.location_label') }}</th>
                <th class="py-1">{{ __('tenant.attendance.date_label') }}</th>
                <th class="py-1 text-right">{{ __('tenant.attendance.check_in_label') }}</th>
                <th class="py-1 text-right">{{ __('tenant.attendance.check_out_label') }}</th>
                <th class="py-1 text-right">{{ __('tenant.attendance.hours_worked_label') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attendances as $attendance)
                <tr class="border-b border-zinc-200">
                    <td class="py-1">{{ $attendance->employee->name }}</td>
                    <td class="py-1">{{ $attendance->location ?? '—' }}</td>
                    <td class="py-1">{{ $attendance->check_in?->format('d/m/Y') ?? '—' }}</td>
                    <td class="py-1 text-right">{{ $attendance->check_in?->format('H:i') ?? '—' }}</td>
                    <td class="py-1 text-right">{{ $attendance->check_out?->format('H:i') ?? __('tenant.attendance.in_progress') }}</td>
                    <td class="py-1 text-right">
                        @if ($attendance->check_in && $attendance->check_out)
                            @php $minutes = $attendance->check_in->diffInMinutes($attendance->check_out); @endphp
                            {{ sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60) }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-4 text-center text-zinc-500">{{ __('tenant.attendance.empty') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script>
        window.onload = () => window.print();
    </script>
</body>
</html>
