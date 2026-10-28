<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Permission;
use App\Models\ProductRequest;
use App\Models\Role;
use App\Models\ShopProduct;
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
            'category' => ['nullable', 'string', 'in:all,products,services,orders,inventory,vendors'],
            'export' => ['nullable', 'string', 'in:pdf'],
        ]);

        if (($validated['export'] ?? null) === 'pdf') {
            return $this->exportPdf($request, $reportService);
        }

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString());
        $to = Carbon::parse($validated['to'] ?? now()->toDateString());
        $category = $validated['category'] ?? 'all';

        $report = $reportService->build($from, $to, $category, $request->user());

        return view('marketplace.reports.index', compact('report'));
    }

    public function exportPdf(Request $request, ReportService $reportService)
    {
        abort_unless($request->user()->hasPermission('reports.view'), 403);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'category' => ['nullable', 'string', 'in:all,products,services,orders,inventory,vendors'],
        ]);

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString());
        $to = Carbon::parse($validated['to'] ?? now()->toDateString());
        $category = $validated['category'] ?? 'all';

        $report = $reportService->build($from, $to, $category, $request->user());

        $pdf = Pdf::loadView('marketplace.reports.pdf', compact('report'))
            ->setPaper('a4', 'portrait');

        $filename = 'tresca-hub-report-' . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
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

    public function notifications()
    {
        $notifications = auth()->user()->notifications()->paginate(20);

        return view('marketplace.notifications.index', compact('notifications'));
    }

    public function readNotification(string $id)
    {
        $notification = auth()->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('marketplace.notifications.index'));
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
