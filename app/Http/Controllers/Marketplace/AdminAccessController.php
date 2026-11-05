<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DeliveryTask;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AuditLogger;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminAccessController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        if ($user->isVendorOnly()) {
            return redirect()->route('vendor.dashboard');
        }

        if (! $user->hasPermission('admin.access') && ! $user->hasPermission('cms.manage')) {
            return redirect()->route('home');
        }

        return view('dashboard');
    }

    public function reports(Request $request, ReportService $reportService)
    {
        abort_unless($request->user()->hasPermission('reports.view'), 403);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'category' => ['nullable', 'string', 'in:all,products,services,orders,inventory,vendors,finance'],
            'q' => ['nullable', 'string', 'max:150'],
            'export' => ['nullable', 'string', 'in:pdf'],
        ]);

        if (($validated['export'] ?? null) === 'pdf') {
            return $this->exportPdf($request, $reportService);
        }

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString());
        $to = Carbon::parse($validated['to'] ?? now()->toDateString());
        $category = $validated['category'] ?? 'all';

        if ($category === 'finance') {
            abort_unless($request->user()->hasPermission('finance.view'), 403);
            $financeReport = $reportService->buildFinance(
                isset($validated['from']) ? Carbon::parse($validated['from']) : null,
                isset($validated['to']) ? Carbon::parse($validated['to']) : null,
                $validated['q'] ?? null,
                25,
            );
            $canViewVendors = $request->user()->hasPermission('vendors.view');

            return view('marketplace.reports.finance', compact('financeReport', 'canViewVendors'));
        }

        $report = $reportService->build($from, $to, $category, $request->user());

        return view('marketplace.reports.index', compact('report'));
    }

    public function exportPdf(Request $request, ReportService $reportService)
    {
        abort_unless($request->user()->hasPermission('reports.view'), 403);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'category' => ['nullable', 'string', 'in:all,products,services,orders,inventory,vendors,finance'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        if (($validated['category'] ?? null) === 'finance') {
            abort_unless($request->user()->hasPermission('finance.view'), 403);
            $financeReport = $reportService->buildFinance(
                isset($validated['from']) ? Carbon::parse($validated['from']) : null,
                isset($validated['to']) ? Carbon::parse($validated['to']) : null,
                $validated['q'] ?? null,
            );
            $pdf = Pdf::loadView('marketplace.reports.finance-pdf', compact('financeReport'))
                ->setPaper('a4', 'portrait');
            $pdf->render();
            $canvas = $pdf->getDomPDF()->getCanvas();
            $font = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans', 'normal');
            $canvas->page_text(26, 570, 'Tresca Hub Finance Report - Page {PAGE_NUM} of {PAGE_COUNT}', $font, 7, [0.39, 0.45, 0.55]);
            $filename = 'tresca-hub-finance-report-'.($validated['from'] ?? 'all-dates').'-to-'.($validated['to'] ?? 'all-dates').'.pdf';

            return $pdf->download($filename);
        }

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString());
        $to = Carbon::parse($validated['to'] ?? now()->toDateString());
        $category = $validated['category'] ?? 'all';

        $report = $reportService->build($from, $to, $category, $request->user());

        $pdf = Pdf::loadView('marketplace.reports.pdf', compact('report'))
            ->setPaper('a4', 'portrait');

        $filename = 'tresca-hub-report-'.$from->format('Y-m-d').'-to-'.$to->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function ordersHistory(Request $request)
    {
        abort_unless($request->user()->hasPermission('reports.view'), 403);

        $filters = $this->validatedOrdersHistoryFilters($request);
        $orders = $this->ordersHistoryQuery($filters)
            ->with(['items:id,order_id,product_name,quantity'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();
        $totalOrders = Order::query()->count();
        $ordersThisMonth = Order::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $statuses = Order::query()->whereNotNull('fulfillment_status')
            ->distinct()->orderBy('fulfillment_status')->pluck('fulfillment_status');

        return view('marketplace.reports.orders-history', compact(
            'orders',
            'filters',
            'statuses',
            'totalOrders',
            'ordersThisMonth',
        ));
    }

    public function exportOrdersHistoryPdf(Request $request)
    {
        abort_unless($request->user()->hasPermission('reports.view'), 403);

        $filters = $this->validatedOrdersHistoryFilters($request);
        $orders = $this->ordersHistoryQuery($filters)
            ->with(['items:id,order_id,product_name,quantity'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
        $report = [
            'orders' => $orders,
            'total_orders' => $orders->count(),
            'total_order_value' => round((float) $orders->sum('total'), 2),
            'period' => [
                'from' => $filters['from'] ?? null,
                'to' => $filters['to'] ?? null,
                'formatted' => ! ($filters['from'] ?? null) && ! ($filters['to'] ?? null)
                    ? 'All dates'
                    : (! ($filters['from'] ?? null)
                        ? 'Through '.Carbon::parse($filters['to'])->format('d M Y')
                        : (! ($filters['to'] ?? null)
                            ? 'From '.Carbon::parse($filters['from'])->format('d M Y')
                            : Carbon::parse($filters['from'])->format('d M Y').' — '.Carbon::parse($filters['to'])->format('d M Y'))),
            ],
            'filters' => $filters,
        ];
        $pdf = Pdf::loadView('marketplace.reports.orders-history-pdf', compact('report'))
            ->setPaper('a4', 'landscape');
        $pdf->render();
        $canvas = $pdf->getDomPDF()->getCanvas();
        $font = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(28, 570, 'Tresca Hub Orders History - Page {PAGE_NUM} of {PAGE_COUNT}', $font, 7, [0.39, 0.45, 0.55]);

        return $pdf->download('tresca-hub-orders-history-'.now()->format('Y-m-d').'.pdf');
    }

    private function validatedOrdersHistoryFilters(Request $request): array
    {
        return $request->validate([
            'customer' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'string', 'max:80'],
        ]);
    }

    private function ordersHistoryQuery(array $filters)
    {
        return Order::query()
            ->when($filters['customer'] ?? null, fn ($query, $customer) => $query->where('customer_name', 'like', '%'.$customer.'%'))
            ->when($filters['phone'] ?? null, fn ($query, $phone) => $query->where('phone', 'like', '%'.$phone.'%'))
            ->when($filters['reference'] ?? null, fn ($query, $reference) => $query->where('reference', 'like', '%'.$reference.'%'))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('created_at', '<=', Carbon::parse($to)->endOfDay()))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('fulfillment_status', $status));
    }

    public function showOrderHistory(Request $request, Order $order)
    {
        abort_unless($request->user()->hasPermission('reports.view'), 403);

        $order->load([
            'items.product:id,thumbnail',
            'items.vendor:id,business_name',
            'deliveries.vendor:id,business_name',
            'deliveries.officer:id,name',
            'deliveries.assignedOfficer:id,name',
            'deliveries.attempts.actor:id,name',
            'histories.actor:id,name',
        ]);

        $itemIds = $order->items->modelKeys();
        $deliveryIds = $order->deliveries->modelKeys();
        $itemType = (new OrderItem)->getMorphClass();
        $deliveryType = (new DeliveryTask)->getMorphClass();
        $auditLogsQuery = AuditLog::query()->with('actor:id,name');
        if ($itemIds !== [] || $deliveryIds !== []) {
            $auditLogsQuery->where(function ($query) use ($itemIds, $itemType, $deliveryIds, $deliveryType) {
                if ($itemIds !== []) {
                    $query->where(fn ($query) => $query
                        ->where('auditable_type', $itemType)
                        ->whereIn('auditable_id', $itemIds));
                }
                if ($deliveryIds !== []) {
                    $method = $itemIds === [] ? 'where' : 'orWhere';
                    $query->{$method}(fn ($query) => $query
                        ->where('auditable_type', $deliveryType)
                        ->whereIn('auditable_id', $deliveryIds));
                }
            });
        } else {
            $auditLogsQuery->whereRaw('1 = 0');
        }
        $auditLogs = $auditLogsQuery->whereIn('action', [
                'order.item_allocated',
                'order.item_accepted',
                'order.item_declined',
                'order.item_problem_reported',
                'order.item_progress',
                'delivery.completed',
            ])
            ->orderBy('created_at')
            ->get();

        $vendorIds = $auditLogs
            ->flatMap(fn (AuditLog $log) => [
                data_get($log->changes, 'to_vendor'),
                data_get($log->changes, 'from_vendor'),
                data_get($log->changes, 'vendor_id'),
            ])
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $vendorsById = $vendorIds->isEmpty()
            ? collect()
            : Vendor::query()->whereIn('id', $vendorIds)->pluck('business_name', 'id');

        $timeline = [];
        if ($order->created_at) {
            $timeline[] = [
                'at' => $order->created_at,
                'title' => 'Order placed',
                'detail' => 'Original order record created.',
            ];
        }
        foreach ($order->histories as $history) {
            $timeline[] = [
                'at' => $history->created_at,
                'title' => $history->from_status
                    ? \Illuminate\Support\Str::headline($history->from_status).' → '.\Illuminate\Support\Str::headline($history->to_status)
                    : \Illuminate\Support\Str::headline($history->to_status),
                'detail' => ($history->note ?: $history->customer_message ?: 'Order status updated.')
                    .($history->actor ? ' · '.$history->actor->name : ''),
            ];
        }
        foreach ($auditLogs as $log) {
            $item = $order->items->firstWhere('id', $log->auditable_id);
            $timelineEntry = match ($log->action) {
                'order.item_allocated' => [
                    'title' => 'Product allocation updated',
                    'detail' => ($item?->product_name ?: 'Order item')
                        .' → '.($vendorsById[(int) data_get($log->changes, 'to_vendor')] ?? 'Admin / unassigned'),
                ],
                'order.item_accepted' => [
                    'title' => 'Vendor approval recorded',
                    'detail' => $item?->product_name ?: 'Order item',
                ],
                'order.item_declined' => [
                    'title' => 'Vendor declined assignment',
                    'detail' => $item?->product_name ?: 'Order item',
                ],
                'order.item_problem_reported' => [
                    'title' => 'Vendor reported an issue',
                    'detail' => data_get($log->changes, 'note') ?: ($item?->product_name ?: 'Order item'),
                ],
                'order.item_progress' => [
                    'title' => 'Vendor fulfillment updated',
                    'detail' => $item?->product_name ?: 'Order item',
                ],
                'delivery.completed' => [
                    'title' => data_get($log->meta, 'actor_type') === 'customer'
                        ? 'Customer confirmed delivery'
                        : 'Delivery marked complete',
                    'detail' => $order->deliveries->firstWhere('id', $log->auditable_id)?->reference ?: '',
                ],
                default => null,
            };
            if ($timelineEntry) {
                $timeline[] = ['at' => $log->created_at] + $timelineEntry;
            }
        }
        foreach ($order->deliveries as $delivery) {
            $timeline[] = [
                'at' => $delivery->created_at,
                'title' => 'Delivery record created',
                'detail' => $delivery->reference,
            ];
            if ($delivery->dispatched_at) {
                $timeline[] = [
                    'at' => $delivery->dispatched_at,
                    'title' => 'Delivery dispatched',
                    'detail' => $delivery->reference,
                ];
            }
            if ($delivery->delivered_at) {
                $timeline[] = [
                    'at' => $delivery->delivered_at,
                    'title' => 'Delivery completed',
                    'detail' => $delivery->reference,
                ];
            }
            foreach ($delivery->attempts as $attempt) {
                $timeline[] = [
                    'at' => $attempt->attempted_at,
                    'title' => 'Delivery attempt: '.\Illuminate\Support\Str::headline($attempt->result),
                    'detail' => $attempt->reason ?: ($attempt->note ?: ''),
                ];
            }
        }
        $timeline = collect($timeline)
            ->filter(fn (array $event) => $event['at'] !== null)
            ->sortBy(fn (array $event) => $event['at']->getTimestamp())
            ->values();

        $approvalHistory = $order->histories
            ->where('to_status', 'confirmed')
            ->sortBy('created_at')
            ->first();
        $rejectionHistory = $order->histories
            ->where('to_status', 'cancelled')
            ->sortBy('created_at')
            ->first();

        return view('marketplace.reports.order-history-show', compact(
            'order',
            'auditLogs',
            'vendorsById',
            'timeline',
            'approvalHistory',
            'rejectionHistory',
        ));
    }

    public function audit(Request $request)
    {
        abort_unless($request->user()->hasPermission('audit_logs.view'), 403);

        $logs = AuditLog::query()->with('actor')
            ->when($request->action, fn ($query) => $query->where('action', $request->action))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('marketplace.audit.index', compact('logs'));
    }

    public function roles()
    {
        abort_unless(auth()->user()->hasAnyPermission(['roles.manage', 'permissions.manage']), 403);

        return view('marketplace.roles.index', [
            'roles' => Role::query()->withCount('users', 'permissions')->orderBy('name')->get(),
        ]);
    }

    public function editRole(Role $role)
    {
        abort_unless(auth()->user()->hasAnyPermission(['roles.manage', 'permissions.manage']), 403);
        $role->load('permissions');
        $permissions = Permission::query()->orderBy('group')->orderBy('slug')->get()->groupBy('group');

        return view('marketplace.roles.edit', compact('role', 'permissions'));
    }

    public function storeRole(Request $request)
    {
        abort_unless($request->user()->hasPermission('roles.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $slug = Str::slug($data['name']);
        $role = Role::query()->create([
            'name' => $data['name'],
            'slug' => Role::query()->where('slug', $slug)->exists() ? $slug.'-'.Str::lower(Str::random(4)) : $slug,
            'description' => $data['description'] ?? null,
            'is_system' => false,
        ]);
        AuditLogger::log($request->user(), 'role.created', $role);

        return redirect()->route('marketplace.roles.edit', $role)->with('success', 'Role created.');
    }

    public function updateRole(Request $request, Role $role)
    {
        abort_unless($request->user()->hasPermission('permissions.manage'), 403);

        if ($role->slug === 'super-administrator' && ! $request->user()->hasRole('super-administrator')) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->name = $data['name'];
        $role->description = $data['description'] ?? null;
        $role->save();

        if ($role->slug === 'super-administrator') {
            $role->permissions()->sync(Permission::query()->pluck('id'));
        } else {
            $ids = collect($data['permissions'] ?? []);
            if (! $request->user()->hasRole('super-administrator')) {
                $allowed = Permission::query()->whereIn('slug', $request->user()->permissionSlugs())->pluck('id');
                $ids = $ids->intersect($allowed);
            }
            $role->permissions()->sync($ids);
        }

        AuditLogger::log($request->user(), 'role.permissions_updated', $role, ['permissions' => $role->permissions()->pluck('slug')]);

        return back()->with('success', 'Role saved.');
    }

    public function users(Request $request)
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);
        $users = User::query()->with('roles', 'vendor')->when($request->q, function ($query) use ($request) {
            $query->where('name', 'like', '%'.$request->q.'%')->orWhere('email', 'like', '%'.$request->q.'%');
        })->orderBy('name')->paginate(20);

        return view('marketplace.users.index', [
            'users' => $users,
            'roles' => $this->assignableRoles($request->user()),
            'vendors' => Vendor::query()->orderBy('business_name')->get(),
        ]);
    }

    public function createUser()
    {
        abort_unless(auth()->user()->hasPermission('users.manage'), 403);

        return view('marketplace.users.form', [
            'staff' => new User,
            'roles' => $this->assignableRoles(auth()->user()),
            'vendors' => Vendor::query()->orderBy('business_name')->get(),
        ]);
    }

    public function storeUser(Request $request)
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $roles = $this->guardRoles($request->user(), Role::query()->whereIn('id', $data['roles'] ?? [])->with('permissions')->get());

        if ($roles->contains(fn (Role $role) => $role->slug === 'vendor') && empty($data['vendor_id'])) {
            return back()->withErrors(['vendor_id' => 'Select the vendor account for this user.'])->withInput();
        }

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'vendor_id' => $roles->contains(fn (Role $role) => $role->slug === 'vendor') ? ($data['vendor_id'] ?? null) : null,
            'email_verified_at' => now(),
        ]);

        $user->roles()->sync($roles->pluck('id'));
        AuditLogger::log($request->user(), 'user.created', $user, ['roles' => $roles->pluck('slug')]);

        return redirect()->route('marketplace.users.index')->with('success', 'User created.');
    }

    public function updateUser(Request $request, User $staff)
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
        ]);

        $roles = $this->guardRoles($request->user(), Role::query()->whereIn('id', $data['roles'] ?? [])->with('permissions')->get());
        $this->guardLastSuperAdmin($staff, $roles);

        $staff->roles()->sync($roles->pluck('id'));
        $staff->vendor_id = $roles->contains(fn (Role $role) => $role->slug === 'vendor') ? ($data['vendor_id'] ?? null) : null;
        $staff->save();
        $staff->forgetPermissionCache();
        AuditLogger::log($request->user(), 'user.roles_updated', $staff, ['roles' => $roles->pluck('slug')]);

        return back()->with('success', 'User access updated.');
    }

    public function notifications(Request $request)
    {
        $user = $request->user();
        $notificationQuery = $user->notifications();
        $filter = 'all';
        $search = '';

        $filters = $request->validate([
            'filter' => ['nullable', 'in:all,unread,read'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $filter = $filters['filter'] ?? 'all';
        $search = trim($filters['q'] ?? '');

        if ($filter === 'unread') {
            $notificationQuery->whereNull('read_at');
        } elseif ($filter === 'read') {
            $notificationQuery->whereNotNull('read_at');
        }

        if ($search !== '') {
            $term = '%'.$search.'%';
            $notificationQuery->where(function ($query) use ($term) {
                $query->where('data->title', 'like', $term)
                    ->orWhere('data->body', 'like', $term);
            });
        }

        $notifications = $notificationQuery->latest()->paginate(20)->withQueryString();
        $notificationCounts = [
            'all' => $user->notifications()->count(),
            'unread' => $user->unreadNotifications()->count(),
            'read' => $user->readNotifications()->count(),
        ];

        return view('marketplace.notifications.index', compact(
            'notifications',
            'notificationCounts',
            'filter',
            'search',
        ));
    }

    public function unreadNotificationCount(Request $request)
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function readNotification(string $id)
    {
        $notification = auth()->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('marketplace.notifications.index'));
    }

    public function markAllNotificationsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()
            ->route('marketplace.notifications.index')
            ->with('success', 'All notifications have been marked as read.');
    }

    private function assignableRoles(User $actor)
    {
        return Role::query()->with('permissions')->orderBy('name')->get()->filter(function (Role $role) use ($actor) {
            if ($actor->hasRole('super-administrator')) {
                return true;
            }

            return $role->permissions->every(fn (Permission $permission) => $actor->hasPermission($permission->slug));
        });
    }

    private function guardRoles(User $actor, $roles)
    {
        foreach ($roles as $role) {
            if ($role->slug === 'super-administrator' && ! $actor->hasRole('super-administrator')) {
                abort(403);
            }

            if (! $actor->hasRole('super-administrator')) {
                foreach ($role->permissions as $permission) {
                    abort_unless($actor->hasPermission($permission->slug), 403);
                }
            }
        }

        return $roles;
    }

    private function guardLastSuperAdmin(User $staff, $roles): void
    {
        $superId = Role::query()->where('slug', 'super-administrator')->value('id');
        $keeps = $roles->contains(fn (Role $role) => $role->slug === 'super-administrator');
        $isSuper = $staff->hasRole('super-administrator');

        if ($isSuper && ! $keeps) {
            $count = DB::table('role_user')->where('role_id', $superId)->count();
            if ($count <= 1) {
                abort(422, 'The last super administrator cannot be removed.');
            }
        }
    }
}
