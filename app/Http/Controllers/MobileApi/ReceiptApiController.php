<?php

namespace App\Http\Controllers\MobileApi;

use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\CompanyProfile;
use App\Models\Payroll\EmployeeRequestedSalary;
use App\Models\Payroll\SalaryReceipt;
use App\Models\Payroll\SalaryReceiptItem;
use App\Models\UserManagement\User;
use Carbon\Carbon;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mpdf\Mpdf;

#[Prefix('Receipt/api'), Name('api-receipt'), Middleware('auth:sanctum')]
class ReceiptApiController extends Controller
{
    #[Get('/{id}', '.all',)]
    public function getAllPayrollByUser($id, request $request)
    {
        $request->validate([
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date',
        ]);

        $user = User::withTrashed()->where('id', $id)->firstOrFail();

        $data = SalaryReceipt::query()
            ->with(['user', 'user.userDetail', 'user.groups', 'salaryReceiptItems'])
            ->where('mst_user_id', $user->id)
            ->when(!empty($request->date_start) && !empty($request->date_end), function ($query) use ($request) {
                $startDate = Carbon::parse($request->date_start)->startOfDay();
                $endDate = Carbon::parse($request->date_end)->endOfDay();
                $query->whereBetween('start_date', [$startDate, $endDate]);
            })
            ->paginate(10)
            ->withQueryString();

        return new JsonBody($data);
    }

     #[Get('/{payroll}/detail', '.json.detail')]
    public function detailReceiptJson(int $payroll, Request $request)
    {
        if ($payroll == null) {
            return new JsonBody(null, 'Data not found', 404);
        }

        $data = SalaryReceipt::query()->with(['user.userDetail', 'companySalaryItem'])->findOrFail($payroll);
        $data->company_profile = CompanyProfile::query()->first();

        return new JsonBody($data);
    }

    #[Get('/{payroll}/export/pdf', '.json.export.pdf')]
    public function exportDetailPdf(int $payroll)
    {
        $receipt = SalaryReceipt::query()
            ->with(['user.userDetail', 'companySalaryItem'])
            ->findOrFail($payroll);

        if ($receipt->mst_user_id !== Auth::id()) {
            abort(403, 'Forbidden');
        }

        $receipt->total_subtract = $receipt->companySalaryItem
            ->where('calculation_type', 'subtract')
            ->where('is_tax', false)
            ->sum('salary');

        $receiptItemIds = SalaryReceiptItem::query()
            ->where('trx_salary_receipt_id', $payroll)
            ->whereNotNull('trx_employee_requested_salary_id')
            ->pluck('trx_employee_requested_salary_id', 'mst_company_salary_id');

        $requestedSalaries = EmployeeRequestedSalary::query()
            ->with('approvedBy.userDetail')
            ->whereIn('id', $receiptItemIds->values())
            ->get()
            ->keyBy('id');

        $receipt->companySalaryItem->each(function ($item) use ($receiptItemIds, $requestedSalaries) {
            $reqId = $receiptItemIds->get($item->id);
            $item->is_requested = !is_null($reqId);
            $item->request_info = $reqId ? $requestedSalaries->get($reqId) : null;
        });

        $company = CompanyProfile::query()->first();

        $html = view('pdf.receipt-detail', compact('receipt', 'company'))->render();

        $mpdf = new Mpdf([
            'format' => 'A4',
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('receipt-detail-' . $payroll . '-' . now()->format('Ymd-His') . '.pdf', 'I');
    }
}
