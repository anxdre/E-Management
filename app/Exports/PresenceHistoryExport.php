<?php

namespace App\Exports;

use App\Models\PresenceManagement\PresenceEmployee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PresenceHistoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{

    protected array $filters;
    protected int $userId;

    public function __construct(int $userId, array $filters = [])
    {
        $this->userId = $userId;
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        return PresenceEmployee::query()
            ->with(['presenceLocation', 'user.userDetail'])
            ->where('mst_user_id', $this->userId)
            ->when(!empty($this->filters['status']), function ($q) {
                $q->where('status_by_admin', $this->filters['status']);
            })
            ->when(!empty($this->filters['location_id']), function ($q) {
                $q->where('mst_presence_location_id', $this->filters['location_id']);
            })
            ->when(!empty($this->filters['date_start']), function ($q) {
                $q->whereDate('time_in', '>=', $this->filters['date_start']);
            })
            ->when(!empty($this->filters['date_end']), function ($q) {
                $q->whereDate('time_in', '<=', $this->filters['date_end']);
            })
            ->orderBy('time_in', $this->filters['order_by'] ?? 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Employee Name',
            'Email',
            'Date',
            'Time In',
            'Time Out',
            'Working Hour',
            'Location',
            'Latitude',
            'Longitude',
            'Admin Status',
            'Note',
            'Created At',
        ];
    }

    public function map($presence): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $presence->user?->userDetail?->fullname ?? '-',
            $presence->user?->email ?? '-',
            $presence->time_in ? date('Y-m-d', strtotime($presence->time_in)) : '-',
            $presence->time_in ? date('H:i:s', strtotime($presence->time_in)) : '-',
            $presence->time_out ? date('H:i:s', strtotime($presence->time_out)) : '-',
            $this->calcWorkingHour($presence->time_in, $presence->time_out) ?? '-',
            $presence->presenceLocation?->name ?? '-',
            $presence->latitude ?? '-',
            $presence->longitude ?? '-',
            $presence->status_by_admin ?? 'pending',
            $presence->note ?? '-',
            $presence->created_at ?? '-',
        ];
    }

    private function calcWorkingHour($timeIn, $timeOut): string
    {
        if (!$timeIn || !$timeOut) {
            return '-';
        }
        $start = Carbon::parse($timeIn);
        $end = Carbon::parse($timeOut);
        $diff = $start->diff($end);
        return sprintf('%d:%02d:%02d', $diff->h, $diff->i, $diff->s);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
