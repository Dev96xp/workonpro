<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ __('tenant.attendance_payroll.heading') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white p-8 text-zinc-900">
    <h1 class="text-2xl font-bold">{{ tenant('name') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('tenant.attendance_payroll.heading') }}</p>

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
                <th class="py-1">{{ __('tenant.employees.role_label') }}</th>
                <th class="py-1">{{ __('tenant.employees.salary_period_label') }}</th>
                <th class="py-1 text-right">{{ __('tenant.attendance.hours_worked_label') }}</th>
                <th class="py-1 text-right">{{ __('tenant.attendance_payroll.amount_label') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="border-b border-zinc-200">
                    <td class="py-1">{{ $row['employee']->name }}</td>
                    <td class="py-1">{{ $row['employee']->role ?? '—' }}</td>
                    <td class="py-1">{{ $row['employee']->salary_period ? __('tenant.employees.period_'.$row['employee']->salary_period) : '—' }}</td>
                    <td class="py-1 text-right">
                        {{ sprintf('%dh %02dm', intdiv((int) round($row['hours'] * 60), 60), (int) round($row['hours'] * 60) % 60) }}
                    </td>
                    <td class="py-1 text-right">
                        {{ $row['amount'] !== null ? '$'.number_format($row['amount'], 2) : __('tenant.attendance_payroll.no_salary') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-4 text-center text-zinc-500">{{ __('tenant.attendance_payroll.empty') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if ($rows->isNotEmpty())
            <tfoot>
                <tr class="border-t-2 border-zinc-400 font-semibold">
                    <td colspan="4" class="py-2 text-right">{{ __('tenant.attendance_payroll.total_label') }}</td>
                    <td class="py-2 text-right">${{ number_format($total, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <script>
        window.onload = () => window.print();
    </script>
</body>
</html>
