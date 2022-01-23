<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\UUIDGenerate;
use App\Http\Controllers\Controller;
use App\Notifications\GeneralNotification;
use App\Transaction;
use App\User;
use App\Wallet;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Yajra\Datatables\Datatables;

class WalletController extends Controller
{
    public function index()
    {
        return view('backend.wallet.index');
    }

    public function ssd()
    {
        $wallets = Wallet::with('user');

        return Datatables::of($wallets)
            ->addColumn('account_person', function ($each) {
                $user = $each->user;
                if ($user) {
                    return '<p>Name : ' . $user->name . '</p><p>Email : ' . $user->email . '</p><p>Phone : ' . $user->phone . '</p>';
                }

                return '-';
            })
            ->editColumn('amount', function ($each) {
                return number_format($each->amount, 2);
            })
            ->editColumn('created_at', function ($each) {
                return Carbon::parse($each->created_at)->format('Y-m-d H:i:s');
            })
            ->editColumn('updated_at', function ($each) {
                return Carbon::parse($each->updated_at)->format('Y-m-d H:i:s');
            })
            ->rawColumns(['account_person'])
            ->make(true);
    }

    public function addAmount()
    {
        $users = User::orderBy("name")->get();
        return view('backend.wallet.add_amount', compact('users'));
    }

    public function addAmountStore(Request $request)
    {
        $request->validate(
            [
                'user_id' => 'required',
                'amount' => 'required|integer',
            ],
            [
                'user_id.required' => 'The user field is required.',
            ]
        );

        if ($request->amount < 1000) {
            return back()->withErrors(['amount' => 'The amount must be at least 1000 MMK.'])->withInput();
        }

        DB::beginTransaction();
        try {
            $to_account = User::with('wallet')->where('id', $request->user_id)->firstOrFail();
            $to_account_wallet = $to_account->wallet;
            $to_account_wallet->increment('amount', $request->amount);
            $to_account_wallet->update();

            $ref_no = UUIDGenerate::refNumber();
            $to_account_transaction = new Transaction();
            $to_account_transaction->ref_no = $ref_no;
            $to_account_transaction->trx_id = UUIDGenerate::trxId();
            $to_account_transaction->user_id = $to_account->id;
            $to_account_transaction->type = 1;
            $to_account_transaction->amount = $request->amount;
            $to_account_transaction->source_id = 0;
            $to_account_transaction->description = $request->description;
            $to_account_transaction->save();

            // To Noti
            $title = 'E-money Received!';
            $message = 'Your wallet received ' . number_format($request->amount) . ' MMK by Magic Pay Super Admin.';
            $sourceable_id = $to_account_transaction->id;
            $sourceable_type = Transaction::class;
            $web_link = url('/transaction/' . $to_account_transaction->trx_id);
            $deep_link = [
                'target' => 'transaction_detail',
                'parameter' => [
                    'trx_id' => $to_account_transaction->trx_id,
                ],
            ];
            Notification::send([$to_account], new GeneralNotification($title, $message, $sourceable_id, $sourceable_type, $web_link, $deep_link));

            DB::commit();
            return redirect()->route('admin.wallet.index')->with('create', 'Successfully added amount.');
        } catch (\Exception $error) {
            DB::rollback();
            return back()->withErrors(['fail' => 'Something wrong. ' . $error->getMessage()])->withInput();
        }
    }

    public function reduceAmount()
    {
        $users = User::orderBy("name")->get();
        return view('backend.wallet.reduce_amount', compact('users'));
    }

    public function reduceAmountStore(Request $request)
    {
        $request->validate(
            [
                'user_id' => 'required',
                'amount' => 'required|integer',
            ],
            [
                'user_id.required' => 'The user field is required.',
            ]
        );

        if ($request->amount < 1) {
            return back()->withErrors(['amount' => 'The amount must be at least 1 MMK.'])->withInput();
        }

        DB::beginTransaction();
        try {
            $to_account = User::with('wallet')->where('id', $request->user_id)->firstOrFail();
            $to_account_wallet = $to_account->wallet;

            if ($to_account_wallet->amount < $request->amount) {
                throw new Exception("The amount is greater than the wallet balance.");
            }

            $to_account_wallet->decrement('amount', $request->amount);
            $to_account_wallet->update();

            $ref_no = UUIDGenerate::refNumber();
            $to_account_transaction = new Transaction();
            $to_account_transaction->ref_no = $ref_no;
            $to_account_transaction->trx_id = UUIDGenerate::trxId();
            $to_account_transaction->user_id = $to_account->id;
            $to_account_transaction->type = 2;
            $to_account_transaction->amount = $request->amount;
            $to_account_transaction->source_id = 0;
            $to_account_transaction->description = $request->description;
            $to_account_transaction->save();

            // To Noti
            $title = 'E-money Reduced!';
            $message = 'Your wallet reduced ' . number_format($request->amount) . ' MMK by Magic Pay Super Admin.';
            $sourceable_id = $to_account_transaction->id;
            $sourceable_type = Transaction::class;
            $web_link = url('/transaction/' . $to_account_transaction->trx_id);
            $deep_link = [
                'target' => 'transaction_detail',
                'parameter' => [
                    'trx_id' => $to_account_transaction->trx_id,
                ],
            ];
            Notification::send([$to_account], new GeneralNotification($title, $message, $sourceable_id, $sourceable_type, $web_link, $deep_link));

            DB::commit();
            return redirect()->route('admin.wallet.index')->with('create', 'Successfully reduced amount.');
        } catch (\Exception $error) {
            DB::rollback();
            return back()->withErrors(['fail' => 'Something wrong. ' . $error->getMessage()])->withInput();
        }
    }
}
