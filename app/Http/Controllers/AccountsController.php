<?php

namespace App\Http\Controllers;

use App\Models\accounts;
use App\Models\transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Finder\Exception\AccessDeniedException;

class AccountsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($filter)
    {
        $accounts = accounts::where('type', $filter)->orderBy('title', 'asc')->get();
        if($filter == "Other")
        {
            $accounts = accounts::Other()->get();
        }

        return view('finance.accounts.index', compact('accounts', 'filter'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        return view('finance.accounts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate(
            [
                'title' => 'required|unique:accounts,title'
            ],
            [
                'title.required' => "Please Enter Account Title",
                'title.unique'  => "Account with this title already exists"
            ]
        );

        try
        {
            DB::beginTransaction();
                if($request->type == "Consignee")
                {
                    $account = accounts::create(
                        [
                            'title' => $request->title,
                            'type' => $request->type,
                            'address_one' => $request->address_one,
                            'address_two' => $request->address_two,
                            'license' => $request->license,
                            'email' => $request->email,
                            'tel' => $request->tel,
                            'po_box' => $request->po_box,
                        ]
                    );
                }
                else
                {
                  $account = accounts::create(
                    [
                        'title' => $request->title,
                        'type' => $request->type,
                        'email' => $request->email,
                        'tel'   => $request->tel,
                    ]
                  );
                }
           DB::commit();
           return back()->with('success', "Account Created Successfully");
        }
        catch(\Exception $e)
        {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id, $from, $to)
    {
        $account = accounts::find($id);

        $transactions = transactions::where('account_id', $id)->whereBetween('date', [$from, $to])->get();

        $pre_cr = transactions::where('account_id', $id)->whereDate('date', '<', $from)->sum('cr');
        $pre_db = transactions::where('account_id', $id)->whereDate('date', '<', $from)->sum('db');
        $pre_balance = $pre_cr - $pre_db;

        $cur_cr = transactions::where('account_id', $id)->sum('cr');
        $cur_db = transactions::where('account_id', $id)->sum('db');

        $cur_balance = $cur_cr - $cur_db;

        return view('finance.accounts.statment', compact('account', 'transactions', 'pre_balance', 'cur_balance', 'from', 'to'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(accounts $account)
    {
        return view('finance.accounts.edit', compact('account'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, accounts $account)
    {
        $request->validate(
            [
                'title' => "required|unique:accounts,title,". $account->id,
            ],
            [
                'title.required' => "Please Enter Account Title",
                'title.unique'  => "Account with this title already exists"
            ]
        );
        $account = accounts::find($account->id)->update(
            [
                'title' => $request->title,
                'address_one' => $request->address_one,
                'address_two' => $request->address_two,
                'license' => $request->license,
                'tel' => $request->tel,
                'email' => $request->email,
                'po_box' => $request->po_box,
            ]
        );

        return redirect()->route('accountsList', $request->type)->with('success', "Account Updated");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(accounts $accounts)
    {
        //
    }

    public function status($id)
    {
        $account = accounts::find($id);
        if($account->status == "Active")
        {
           $status = "Inactive";
        }
        else
        {
            $status = "Active";
        }

        $account->update(
            [
                'status' => $status,
            ]
        );

        return back()->with('success', "Status Updated");
    }
}
