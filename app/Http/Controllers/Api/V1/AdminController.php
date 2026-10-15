<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Report;
use App\Models\Withdrawal;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AdminController extends Controller
{
    private function guard(Request $request):void{abort_unless($request->user()->role==='admin',403);}
    public function reports(Request $request){$this->guard($request);return Report::with(['order.product','reporter'])->whereIn('status',['open','investigating'])->latest()->paginate(20);}
    public function resolveReport(Request $request,Report $report,WalletService $wallet){$this->guard($request);$data=$request->validate(['decision'=>'required|in:release,refund']);DB::transaction(function()use($report,$data,$wallet){$locked=Report::whereKey($report->id)->lockForUpdate()->firstOrFail();$order=Order::whereKey($locked->order_id)->lockForUpdate()->firstOrFail();$locked->update(['status'=>'resolved']);if($data['decision']==='refund'){$order->update(['payment_status'=>'refunded','order_status'=>'completed']);}else{$order->update(['order_status'=>'delivered','delivered_at'=>$order->delivered_at??now()]);}});if($data['decision']==='release')$wallet->release($report->order);return response()->json(['report'=>$report->fresh(),'order'=>$report->order->fresh()]);}
    public function withdrawals(Request $request){$this->guard($request);return Withdrawal::with('user:id,name,email')->where('status','pending')->latest()->paginate(20);}
    public function decideWithdrawal(Request $request,Withdrawal $withdrawal){$this->guard($request);$data=$request->validate(['decision'=>'required|in:complete,reject']);DB::transaction(function()use($withdrawal,$data){$w=Withdrawal::whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();abort_unless($w->status==='pending',409);$w->update(['status'=>$data['decision']==='complete'?'completed':'rejected']);if($data['decision']==='reject'){$user=$w->user()->lockForUpdate()->firstOrFail();$user->increment('wallet_balance',$w->amount);WalletTransaction::create(['user_id'=>$user->id,'type'=>'credit','amount'=>$w->amount,'description'=>'Pengembalian dana penarikan ditolak']);}});return $withdrawal->fresh();}
}
