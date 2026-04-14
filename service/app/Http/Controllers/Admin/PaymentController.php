<?php
namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Models\WalletPayment;
use App\Models\PaymentRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function list(Request $request)
    {
        $payments = PaymentRequest::query();
        
        // 从 session 获取筛选条件
        $filterData = session()->get('payment_filter', []);
        
        // 搜索功能
        if ($request->filled('search')) {
            $searchTerms = explode(' ', $request->search);
            $payments = $payments->where(function ($query) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $term = trim($term);
                    if (!empty($term)) {
                        $query->orWhere('id', 'like', "%{$term}%")
                            ->orWhere('transaction_id', 'like', "%{$term}%")
                            ->orWhere('attribute_id', 'like', "%{$term}%")
                            ->orWhere('payer_id', 'like', "%{$term}%")
                            ->orWhere('receiver_id', 'like', "%{$term}%");
                    }
                }
            });
        }

        // 应用 session 中的筛选条件
        if (!empty($filterData['payment_status']) && $filterData['payment_status'] !== '') {
            $payments = $payments->where('is_paid', $filterData['payment_status']);
        }

        if (!empty($filterData['payment_method'])) {
            $payments = $payments->where('payment_method', $filterData['payment_method']);
        }

        if (!empty($filterData['attribute'])) {
            $payments = $payments->where('attribute', $filterData['attribute']);
        }

        if (!empty($filterData['from_date']) && !empty($filterData['to_date'])) {
            $payments = $payments->whereBetween('created_at', [
                $filterData['from_date'] . " 00:00:00", 
                $filterData['to_date'] . " 23:59:59"
            ]);
        }

        // 处理直接请求的筛选条件（覆盖 session 条件）
        if ($request->filled('payment_status')) {
            $payments = $payments->where('is_paid', $request->payment_status);
        }

        if ($request->filled('payment_method')) {
            $payments = $payments->where('payment_method', $request->payment_method);
        }

        if ($request->filled('attribute')) {
            $payments = $payments->where('attribute', $request->attribute);
        }

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $payments = $payments->whereBetween('created_at', [
                $request->from_date . " 00:00:00", 
                $request->to_date . " 23:59:59"
            ]);
        }

        // 分页查询
        $payments = $payments->orderBy('created_at', 'desc')
                            ->paginate(config('default_pagination'));

        // 获取筛选选项
        $payment_methods = PaymentRequest::distinct()
            ->whereNotNull('payment_method')
            ->pluck('payment_method')
            ->filter()
            ->values();
            
        $attributes = PaymentRequest::distinct()
            ->whereNotNull('attribute')
            ->pluck('attribute')
            ->filter()
            ->values();

        return view('admin-views.payment.list', compact('payments', 'payment_methods', 'attributes'));
    }

    public function filter(Request $request)
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'payment_status' => 'nullable|in:0,1',
            'payment_method' => 'nullable|string',
            'attribute' => 'nullable|string',
        ]);

        // 将筛选条件存储到 session
        $filterData = $request->only([
            'from_date', 
            'to_date', 
            'payment_status', 
            'payment_method', 
            'attribute'
        ]);
        
        session()->put('payment_filter', $filterData);
        
        return redirect()->route('admin.payment.list');
    }

    public function filter_reset(Request $request)
    {
        session()->forget('payment_filter');
        return redirect()->route('admin.payment.list');
    }

    /**
     * 获取相关信息
     */
    private function getRelatedInfo($payment)
    {
        $related_info = null;
        
        if ($payment->attribute == 'order' && $payment->attribute_id) {
            $related_info = Order::find($payment->attribute_id);
        } elseif ($payment->attribute == 'wallet_payments' && $payment->attribute_id) {
            $related_info = WalletPayment::find($payment->attribute_id);
        }
        
        return $related_info;
    }

    /**
     * 获取统计数据
     */
    public function statistics(Request $request)
    {
        $query = PaymentRequest::query();
        
        // 应用日期筛选
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date . " 00:00:00", 
                $request->to_date . " 23:59:59"
            ]);
        }

        $stats = [
            'total_payments' => $query->count(),
            'paid_payments' => $query->where('is_paid', 1)->count(),
            'unpaid_payments' => $query->where('is_paid', 0)->count(),
            'total_amount' => $query->sum('payment_amount'),
            'paid_amount' => $query->where('is_paid', 1)->sum('payment_amount'),
        ];

        return response()->json($stats);
    }
}
