<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Modules\Admin\Http\Controllers\Concerns\ResolvesManagedCanteen;
use App\Modules\Admin\Services\AuditLogger;
use App\Modules\Admin\Services\QrCodeSvg;
use App\Modules\Admin\Services\QrTokenService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class AdminDiningTableController extends Controller
{
    use AuthorizesRequests;
    use ResolvesManagedCanteen;

    public function __construct(
        private readonly QrTokenService $qr,
        private readonly AuditLogger $audit,
        private readonly QrCodeSvg $svg,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', DiningTable::class);

        $canteen = $this->managedCanteen($request);

        $tables = DiningTable::query()
            ->where('canteen_id', $canteen->id)
            ->with('activeToken')
            ->orderBy('code')
            ->get();

        return view(
            'admin::tables.index',
            compact('canteen', 'tables'),
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', DiningTable::class);

        $canteen = $this->managedCanteen($request);

        $data = $request->validate([
            'code' => [
                'required',
                'alpha_dash',
                'max:30',
                Rule::unique('dining_tables', 'code')
                    ->where(
                        fn ($query) => $query->where(
                            'canteen_id',
                            $canteen->id,
                        ),
                    ),
            ],
            'label' => ['required', 'string', 'max:60'],
            'zone' => ['nullable', 'string', 'max:60'],
        ]);

        DiningTable::query()->create([
            'canteen_id' => $canteen->id,
            'code' => $data['code'],
            'label' => $data['label'],
            'zone' => $data['zone'] ?? null,
            'status' => 'active',
        ]);

        return redirect()
            ->route('admin.tables.index')
            ->with('status', 'Meja berhasil dibuat.');
    }

    public function rotate(DiningTable $table): RedirectResponse
    {
        $this->authorize('rotateQr', $table);

        $plainToken = $this->qr->rotate($table);

        return redirect()
            ->route('admin.tables.qr', $table)
            ->with('qr_plain', $plainToken);
    }

    public function qr(DiningTable $table): View
    {
        $this->authorize('rotateQr', $table);

        $plainToken = session('qr_plain');

        $url = is_string($plainToken)
            ? route('customer.scan', ['token' => $plainToken])
            : null;

        $svg = $url !== null
            ? $this->svg->render($url)
            : null;

        return view(
            'admin::tables.qr',
            compact('table', 'plainToken', 'url', 'svg'),
        );
    }

    public function status(
        Request $request,
        DiningTable $table,
    ): RedirectResponse {
        $this->authorize('update', $table);

        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $before = $table->status;

        if ($before === $data['status']) {
            return redirect()
                ->route('admin.tables.index')
                ->with('status', 'Status meja tidak berubah.');
        }

        $table->forceFill([
            'status' => $data['status'],
        ])->save();

        $this->audit->record(
            'dining_table',
            $table->id,
            $data['status'] === 'active' ? 'activated' : 'deactivated',
            ['status' => $before],
            ['status' => $data['status']],
            null,
            $table->canteen_id,
        );

        $message = $data['status'] === 'active'
            ? "{$table->label} berhasil diaktifkan."
            : "{$table->label} dinonaktifkan. QR tidak dapat digunakan sampai meja diaktifkan kembali.";

        return redirect()
            ->route('admin.tables.index')
            ->with('status', $message);
    }
}
