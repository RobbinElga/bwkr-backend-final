<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ExpenseStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\RejectExpenseRequest;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\AuditService;
use App\Services\ProofFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\NotificationService;
use App\Services\ReportExporter;
use App\Support\ReportPeriod;
use Illuminate\Support\Facades\URL;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ProofFileService $files,
        private readonly AuditService $audit,
        private readonly NotificationService $notify,
    ) {}

    public function index(Request $request)
    {
        $expenses = Expense::with(['project', 'bankAccount'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->project_id, fn($q, $id) => $q->where('project_id', $id))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ExpenseResource::collection($expenses);
    }

    public function store(StoreExpenseRequest $request)
    {
        $threshold = (int) config('bwkr.expense.materai_threshold', 5_000_000);

        $data = collect($request->validated())
            ->except(['receipt_file', 'ttd_file', 'materai_file'])->all();

        $data['created_by']    = Auth::id();
        $data['status']        = ExpenseStatus::Pending->value;
        $data['needs_materai'] = (int) $data['amount'] > $threshold;   // diturunkan dari nominal
        $data['receipt_file']  = $this->files->store($request->file('receipt_file'), 'expenses');

        if ($request->hasFile('ttd_file')) {
            $data['ttd_file'] = $this->files->store($request->file('ttd_file'), 'expenses');
        }
        if ($request->hasFile('materai_file')) {
            $data['materai_file'] = $this->files->store($request->file('materai_file'), 'expenses');
        }

        $expense = Expense::create($data);
        $this->audit->log('created', $expense, new: $this->auditable($expense));

        $this->notify->notifyRoles(
            ['super_admin', 'admin'],
            'expense.pending',
            'Pengeluaran menunggu persetujuan',
            'Rp' . number_format($expense->amount, 0, ',', '.') . ' menunggu diproses.',
            '/keuangan/pengeluaran'
        );

        return (new ExpenseResource($expense->load('project')))->response()->setStatusCode(201);
    }

    public function show(Expense $expense)
    {
        return new ExpenseResource($expense->load('project', 'bankAccount'));
    }

    public function approve(Expense $expense)
    {
        if ($expense->status !== ExpenseStatus::Pending) {
            return response()->json(['message' => 'Pengeluaran ini sudah diproses.'], 422);
        }

        $threshold = (int) config('bwkr.expense.super_approval_threshold', 5_000_000);

        if ($expense->amount > $threshold && Auth::user()->role !== UserRole::SuperAdmin) {
            return response()->json([
                'message' => 'Pengeluaran di atas Rp' . number_format($threshold, 0, ',', '.') . ' hanya dapat disetujui Super Admin.',
            ], 403);
        }

        $expense->update([
            'status'      => ExpenseStatus::Approved->value,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $this->audit->log('approved', $expense, new: ['status' => 'approved', 'approved_by' => Auth::id()]);

        if ($expense->created_by !== Auth::id()) {
            $this->notify->notifyUser(
                $expense->created_by,
                'expense.approved',
                'Pengeluaran disetujui',
                'Pengeluaran Rp' . number_format($expense->amount, 0, ',', '.') . ' telah disetujui.',
                '/keuangan/pengeluaran'
            );
        }

        return new ExpenseResource($expense->fresh()->load('project'));
    }

    public function reject(RejectExpenseRequest $request, Expense $expense)
    {
        if ($expense->status !== ExpenseStatus::Pending) {
            return response()->json(['message' => 'Pengeluaran ini sudah diproses.'], 422);
        }

        $expense->update([
            'status'      => ExpenseStatus::Rejected->value,
            'approved_by' => Auth::id(),
            'notes'       => $request->notes ?? $expense->notes,
        ]);

        $this->audit->log('rejected', $expense, new: ['status' => 'rejected']);

        if ($expense->created_by !== Auth::id()) {
            $this->notify->notifyUser(
                $expense->created_by,
                'expense.rejected',
                'Pengeluaran ditolak',
                'Pengeluaran Rp' . number_format($expense->amount, 0, ',', '.') . ' ditolak.',
                '/keuangan/pengeluaran'
            );
        }

        return new ExpenseResource($expense->fresh()->load('project'));
    }

    /** Stream file pengeluaran (privat) — receipt|ttd|materai. */
    public function file(Expense $expense, string $type)
    {
        $map = ['receipt' => 'receipt_file', 'ttd' => 'ttd_file', 'materai' => 'materai_file'];

        abort_unless(isset($map[$type]), 404);

        $path = $expense->{$map[$type]};
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    private function auditable(Expense $e): array
    {
        return collect($e->toArray())->except(['receipt_file', 'ttd_file', 'materai_file'])->all();
    }

    public function export(Request $request, ReportExporter $exporter)
    {
        $format = $request->query('format', 'excel');
        $period = ReportPeriod::fromRequest($request);

        $expenses = Expense::with(['project', 'bankAccount', 'creator'])
            ->when($period['from'], fn($q) => $q->whereBetween('created_at', [$period['from'], $period['to']]))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->project_id, fn($q, $id) => $q->where('project_id', $id))
            ->latest()
            ->get();

        $rows = $expenses->map(fn($e) => [
            optional($e->created_at)->format('d/m/Y H:i'),
            $e->project?->name ?? '-',
            'Rp ' . number_format($e->amount, 0, ',', '.'),
            $e->bankAccount?->bank_name ?? '-',
            $e->needs_materai ? 'Ya' : 'Tidak',
            ucfirst($e->status->value),
            $e->creator?->name ?? '-',
        ])->all();

        $approved = $expenses->filter(fn($e) => $e->status->value === 'approved')->sum('amount');

        $payload = [
            'title'    => 'Laporan Pengeluaran',
            'subtitle' => 'Panel Admin BWKR • ' . $period['label']
                . ($request->status ? ' • Status: ' . ucfirst($request->status) : ''),
            'columns'  => ['Tanggal', 'Project', 'Nominal', 'Rekening', 'Materai', 'Status', 'Dibuat oleh'],
            'rows'     => $rows,
            'summary'  => [
                'Jumlah Pengeluaran' => $expenses->count(),
                'Total Nominal'      => 'Rp ' . number_format($expenses->sum('amount'), 0, ',', '.'),
                'Disetujui'          => 'Rp ' . number_format($approved, 0, ',', '.'),
            ],
        ];

        return $exporter->respond($format, 'laporan-pengeluaran-' . now()->format('Ymd-His'), $payload);
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        $this->audit->log('deleted', $expense);
        return response()->json(['message' => 'Pengeluaran dihapus.']);
    }

    public function trashed()
    {
        return ExpenseResource::collection(
            Expense::onlyTrashed()->with('project')->latest('deleted_at')->get()
        );
    }

    public function restore(int $id)
    {
        $e = Expense::onlyTrashed()->findOrFail($id);
        $e->restore();
        $this->audit->log('restored', $e);
        return response()->json(['message' => 'Pengeluaran dipulihkan.']);
    }

    public function forceDelete(int $id)
    {
        $e = Expense::onlyTrashed()->findOrFail($id);
        $e->forceDelete();
        $this->audit->log('force_deleted', $e);
        return response()->json(['message' => 'Pengeluaran dihapus permanen.']);
    }

    /** URL bertanda-tangan untuk file pengeluaran (10 menit). */
    public function fileUrl(Expense $expense, string $type)
    {
        $map = ['receipt' => 'receipt_file', 'ttd' => 'ttd_file', 'materai' => 'materai_file'];
        abort_unless(isset($map[$type]) && $expense->{$map[$type]}, 404);

        $url = URL::temporarySignedRoute('expense.file', now()->addMinutes(10), [
            'expense' => $expense->id,
            'type' => $type,
        ]);
        return response()->json(['url' => $url]);
    }

    /** Stream file via signed URL (tanpa token). */
    public function fileSigned(Expense $expense, string $type)
    {
        $map = ['receipt' => 'receipt_file', 'ttd' => 'ttd_file', 'materai' => 'materai_file'];
        abort_unless(isset($map[$type]), 404);
        $path = rescue(fn() => $expense->{$map[$type]}, null, false);
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
