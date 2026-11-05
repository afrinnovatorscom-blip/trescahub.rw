warning: in the working copy of 'resources/views/front/pages/account-order.blade.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'resources/views/front/pages/account-orders.blade.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'resources/views/front/pages/checkout.blade.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'resources/views/marketplace/deliveries/index.blade.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'resources/views/marketplace/deliveries/partials/controls.blade.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'resources/views/marketplace/deliveries/partials/live-completion.blade.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'resources/views/marketplace/deliveries/prepare.blade.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'resources/views/marketplace/deliveries/show.blade.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'resources/views/marketplace/notifications/index.blade.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'resources/views/marketplace/orders/partials/admin-decision.blade.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'resources/views/marketplace/orders/partials/live-updates.blade.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'resources/views/marketplace/vendors/index.blade.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'resources/views/shop-products/workflow-fields.blade.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'resources/views/vendor/deliveries/index.blade.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'resources/views/vendor/orders/index.blade.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'resources/views/vendor/orders/partials/vendor-decision.blade.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'resources/views/vendor/orders/show.blade.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'tests/Feature/HomepageSectionsTest.php', CRLF will be replaced by LF the next time Git touches it
warning: in the working copy of 'tests/Feature/VendorDeliveriesTest.php', CRLF will be replaced by LF the next time Git touches it
[1mdiff --git a/app/Http/Controllers/Marketplace/AdminAccessController.php b/app/Http/Controllers/Marketplace/AdminAccessController.php[m
[1mindex cbcd7c6..2f77384 100644[m
[1m--- a/app/Http/Controllers/Marketplace/AdminAccessController.php[m
[1m+++ b/app/Http/Controllers/Marketplace/AdminAccessController.php[m
[36m@@ -4,11 +4,11 @@[m
 [m
 use App\Http\Controllers\Controller;[m
 use App\Models\AuditLog;[m
[32m+[m[32muse App\Models\DeliveryTask;[m
 use App\Models\Order;[m
[32m+[m[32muse App\Models\OrderItem;[m
 use App\Models\Permission;[m
[31m-use App\Models\ProductRequest;[m
 use App\Models\Role;[m
[31m-use App\Models\ShopProduct;[m
 use App\Models\User;[m
 use App\Models\Vendor;[m
 use App\Services\AuditLogger;[m
[36m@@ -43,7 +43,8 @@[m [mpublic function reports(Request $request, ReportService $reportService)[m
         $validated = $request->validate([[m
             'from' => ['nullable', 'date'],[m
             'to' => ['nullable', 'date', 'after_or_equal:from'],[m
[31m-            'category' => ['nullable', 'string', 'in:all,products,services,orders,inventory,vendors'],[m
[32m+[m[32m            'category' => ['nullable', 'string', 'in:all,products,services,orders,inventory,vendors,finance'],[m
[32m+[m[32m            'q' => ['nullable', 'string', 'max:150'],[m
             'export' => ['nullable', 'string', 'in:pdf'],[m
         ]);[m
 [m
[36m@@ -55,6 +56,19 @@[m [mpublic function reports(Request $request, ReportService $reportService)[m
         $to = Carbon::parse($validated['to'] ?? now()->toDateString());[m
         $category = $validated['category'] ?? 'all';[m
 [m
[32m+[m[32m        if ($category === 'finance') {[m
[32m+[m[32m            abort_unless($request->user()->hasPermission('finance.view'), 403);[m
[32m+[m[32m            $financeReport = $reportService->buildFinance([m
[32m+[m[32m                isset($validated['from']) ? Carbon::parse($validated['from']) : null,[m
[32m+[m[32m                isset($validated['to']) ? Carbon::parse($validated['to']) : null,[m
[32m+[m[32m                $validated['q'] ?? null,[m
[32m+[m[32m                25,[m
[32m+[m[32m            );[m
[32m+[m[32m            $canViewVendors = $request->user()->hasPermission('vendors.view');[m
[32m+[m
[32m+[m[32m            return view('marketplace.reports.finance', compact('financeReport', 'canViewVendors'));[m
[32m+[m[32m        }[m
[32m+[m
         $report = $reportService->build($from, $to, $category, $request->user());[m
 [m
         return view('marketplace.reports.index', compact('report'));[m
[36m@@ -67,9 +81,28 @@[m [mpublic function exportPdf(Request $request, ReportService $reportService)[m
         $validated = $request->validate([[m
             'from' => ['nullable', 'date'],[m
             'to' => ['nullable', 'date', 'after_or_equal:from'],[m
[31m-            'category' => ['nullable', 'string', 'in:all,products,services,orders,inventory,vendors'],[m
[32m+[m[32m            'category' => ['nullable', 'string', 'in:all,products,services,orders,inventory,vendors,finance'],[m
[32m+[m[32m            'q' => ['nullable', 'string', 'max:150'],[m
         ]);[m
 [m
[32m+[m[32m        if (($validated['category'] ?? null) === 'finance') {[m
[32m+[m[32m            abort_unless($request->user()->hasPermission('finance.view'), 403);[m
[32m+[m[32m            $financeReport = $reportService->buildFinance([m
[32m+[m[32m                isset($validated['from']) ? Carbon::parse($validated['from']) : null,[m
[32m+[m[32m                isset($validated['to']) ? Carbon::parse($validated['to']) : null,[m
[32m+[m[32m                $validated['q'] ?? null,[m
[32m+[m[32m            );[m
[32m+[m[32m            $pdf = Pdf::loadView('marketplace.reports.finance-pdf', compact('financeReport'))[m
[32m+[m[32m                ->setPaper('a4', 'portrait');[m
[32m+[m[32m            $pdf->render();[m
[32m+[m[32m            $canvas = $pdf->getDomPDF()->getCanvas();[m
[32m+[m[32m            $font = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans', 'normal');[m
[32m+[m[32m            $canvas->page_text(26, 570, 'Tresca Hub Finance Report - Page {PAGE_NUM} of {PAGE_COUNT}', $font, 7, [0.39, 0.45, 0.55]);[m
[32m+[m[32m            $filename = 'tresca-hub-finance-report-'.($validated['from'] ?? 'all-dates').'-to-'.($validated['to'] ?? 'all-dates').'.pdf';[m
[32m+[m
[32m+[m[32m            return $pdf->download($filename);[m
[32m+[m[32m        }[m
[32m+[m
         $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString());[m
         $to = Carbon::parse($validated['to'] ?? now()->toDateString());[m
         $category = $validated['category'] ?? 'all';[m
[36m@@ -79,11 +112,265 @@[m [mpublic function exportPdf(Request $request, ReportService $reportService)[m
         $pdf = Pdf::loadView('marketplace.reports.pdf', compact('report'))[m
             ->setPaper('a4', 'portrait');[m
 [m
[31m-        $filename = 'tresca-hub-report-' . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.pdf';[m
[32m+[m[32m        $filename = 'tresca-hub-report-'.$from->format('Y-m-d').'-to-'.$to->format('Y-m-d').'.pdf';[m
 [m
         return $pdf->download($filename);[m
     }[m
 [m
[32m+[m[32m    public fun