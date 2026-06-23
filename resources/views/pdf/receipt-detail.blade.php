<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payroll Receipt Detail</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #333; padding: 10px; }
        .header { text-align: center; margin-bottom: 8px; }
        .header h1 { font-size: 16pt; font-weight: bold; }
        .header h4 { font-size: 10pt; color: #666; margin-top: 2px; }
        hr { border: none; border-top: 1px solid #ddd; margin: 6px 0; }
        .section { margin-bottom: 10px; }
        .row { display: table; width: 100%; margin: 2px 0; }
        .label { display: table-cell; width: 50%; font-size: 10pt; color: #555; }
        .value { display: table-cell; width: 50%; text-align: right; font-weight: bold; font-size: 10pt; }
        .status-badge {
            display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9pt; font-weight: bold;
        }
        .status-approved { background: #d4edda; color: #155724; }
        .status-denied { background: #f8d7da; color: #721c24; }
        .status-pending { background: #fff3cd; color: #856404; }
        .section-title {
            text-align: center; font-weight: bold; font-size: 10pt; color: #666;
            margin: 10px 0 6px;
        }
        table.items { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 8px; }
        table.items th {
            background: #f5f5f5; border: 1px solid #ddd; padding: 4px 6px;
            text-align: center; font-weight: bold; font-size: 8pt;
        }
        table.items td { border: 1px solid #ddd; padding: 3px 6px; }
        table.items .text-center { text-align: center; }
        table.items .text-right { text-align: right; }
        table.items .text-left { text-align: left; }
        table.items .text-red { color: #dc3545; }
        .totals { margin-top: 6px; }
        .totals .row { margin: 3px 0; }
        .totals .value { font-size: 10pt; }
        .totals .total-final .value { font-size: 12pt; }
        .footer { margin-top: 12px; font-size: 8pt; color: #999; text-align: center; }
        .footer hr { width: 60%; margin: 6px auto; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $company->company_name  }}</h1>
        <hr>
        <h4>Payroll Receipt Detail</h4>
    </div>

    <div class="section">
        <div class="row">
            <span class="label">Status</span>
            <span class="value">
                <span class="status-badge status-{{ $receipt->status }}">
                    {{ ucfirst($receipt->status) }}
                </span>
            </span>
        </div>
        <div class="row">
            <span class="label">Start Date</span>
            <span class="value">{{ \Carbon\Carbon::parse($receipt->start_date)->format('d/m/Y') }}</span>
        </div>
        <div class="row">
            <span class="label">End Date</span>
            <span class="value">{{ \Carbon\Carbon::parse($receipt->end_date)->format('d/m/Y') }}</span>
        </div>
        <div class="row">
            <span class="label">Employee Name</span>
            <span class="value">{{ $receipt->user?->userDetail?->fullname ?? '-' }}</span>
        </div>
        <div class="row">
            <span class="label">Employee Email</span>
            <span class="value">{{ $receipt->user?->email ?? '-' }}</span>
        </div>
        <div class="row">
            <span class="label">Employee Phone</span>
            <span class="value">{{ $receipt->user?->userDetail?->phone ?? '-' }}</span>
        </div>
    </div>

    <div class="section-title">
        — Payroll Item Detail —
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:8%;">No</th>
                <th style="width:24%;">Name</th>
                <th style="width:18%;">Base Amount</th>
                <th style="width:10%;">Category</th>
                <th style="width:12%;">Type</th>
                <th style="width:10%;">Qty</th>
                <th style="width:18%;">Total Earning</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($receipt->companySalaryItem as $index => $item)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-left">
                    {{ $item->detail_item?->salary_name_snapshot ?? $item->name }}
                    @if (!empty($item->is_requested) && $item->request_info)
                        <span style="font-size:7pt;color:#666;display:block;margin-top:1px;">
                            Requested {{ \Carbon\Carbon::parse($item->request_info['created_at'])->format('d/m/Y') }}
                            · Approved by {{ $item->request_info['approved_by']['user_detail']['fullname'] ?? '-' }}
                            {{ !empty($item->request_info['approved_date']) ? \Carbon\Carbon::parse($item->request_info['approved_date'])->format('d/m/Y') : '' }}
                        </span>
                    @endif
                </td>
                <td class="text-center">
                    @php $rate = $item->detail_item?->salary_rate_snapshot ?? $item->salary; @endphp
                    @if (!$item->is_tax)
                        Rp {{ number_format($rate, 0, ',', '.') }}
                    @else
                        {{ $rate }}%
                    @endif
                </td>
                <td class="text-center">
                    @if (!$item->is_tax)
                        {{ $item->calculation_type === 'add' ? 'Add' : 'Subtract' }}
                    @else
                        Tax
                    @endif
                </td>
                <td class="text-center">{{ $item->type }}</td>
                <td class="text-center">{{ $item->detail_item?->quantity ?? 0 }}</td>
                <td class="text-right {{ ($item->is_tax || $item->calculation_type === 'subtract') ? 'text-red' : '' }}">
                    @if ($item->detail_item?->total_value)
                        @if ($item->is_tax || $item->calculation_type === 'subtract')
                        - Rp {{ number_format($item->detail_item->total_value, 0, ',', '.') }}
                        @else
                        Rp {{ number_format($item->detail_item->total_value, 0, ',', '.') }}
                        @endif
                    @else
                        -
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding:10px;">
                    Tidak ada data
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section totals">
        <div class="row">
            <span class="label">Work Hours</span>
            <span class="value">{{ $receipt->work_hour ?? 0 }} hours</span>
        </div>
        <div class="row">
            <span class="label">Total Presence</span>
            <span class="value">{{ $receipt->total_presence_record ?? 0 }} presence</span>
        </div>
        <hr>
        <div class="row">
            <span class="label">Total Salary</span>
            <span class="value">Rp {{ number_format($receipt->total_salary ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="row">
            <span class="label">Total Subtraction</span>
            <span class="value text-red">- Rp {{ number_format($receipt->total_subtract ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="row">
            <span class="label">Total Tax</span>
            <span class="value text-red">- Rp {{ number_format($receipt->total_tax ?? 0, 0, ',', '.') }}</span>
        </div>
        <hr>
        <div class="row total-final">
            <span class="label" style="font-weight:bold;font-size:11pt;">Total Salary After Tax</span>
            <span class="value" style="font-weight:bold;font-size:11pt;">
                Rp {{ number_format($receipt->salary_after_tax ?? 0, 0, ',', '.') }}
            </span>
        </div>
    </div>

    <div class="footer">
        <hr>
        *This receipt is automatically generated<br>
        *If there any mistake, try contact your administrator
    </div>
</body>
</html>
