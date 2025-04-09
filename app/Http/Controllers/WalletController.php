<?php

namespace App\Http\Controllers;

use App\Trait\HttpResponse;
use Illuminate\Http\Request;
use App\Services\WalletService;
use App\Http\Requests\FundWalletRequest;
use App\Http\Requests\WalletTopUpRequest;
use App\Http\Requests\DriverWithdrawRequest;
use App\Http\Requests\WalletTransferRequest;
use App\Http\Requests\DriverWalletSetupequest;
use App\Http\Requests\SetTransactionPinRequest;

class WalletController extends Controller
{

    use HttpResponse;

    protected $service;

    public function __construct(WalletService $service){
        $this->service = $service;
    }

    public function getBalance()
    {
        return $this->service->getBalance();
    }

    public function fundWallet(FundWalletRequest $request)
    {
        return $this->response($this->service->fundWallet($request));
    }

    public function transfer(WalletTransferRequest $request)
    {
        return $this->response($this->service->transfer($request));
    }

    public function getTransactions()
    {
        return $this->service->getTransactions();
    }

    public function walletSetup(DriverWalletSetupequest $request)
    {
        return $this->service->walletSetup($request);
    }

    public function changeBank(Request $request)
    {
        return $this->service->changeBank($request);
    }

    public function verifyPin(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'code' => ['required', 'string'],
        ]);

        return $this->service->verifyPin($request);
    }

    public function setTransactionPin(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'pin' => ['required', 'string', 'confirmed', 'min:4', 'max:4'],
        ]);

        return $this->service->setTransactionPin($request);
    }

    public function withdraw(DriverWithdrawRequest $request)
    {
        return $this->service->withdraw($request);
    }

    public function balanceWithdraw(DriverWithdrawRequest $request)
    {
        return $this->service->balanceWithdraw($request);
    }

    public function recentTransaction($userId)
    {
        return $this->service->recentTransaction($userId);
    }

    public function recentEarning($userId)
    {
        return $this->service->recentEarning($userId);
    }

    public function walletTopUp(WalletTopUpRequest $request)
    {
        return $this->service->walletTopUp($request);
    }

    public function stats($userId)
    {
        return $this->service->stats($userId);
    }
}
