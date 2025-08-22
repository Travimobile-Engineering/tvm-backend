<?php

namespace App\Http\Controllers;

use App\Actions\SendVerificationCode;
use App\Http\Requests\ChangePinRequest;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;
use App\Services\WalletService;
use App\Http\Requests\FundWalletRequest;
use App\Http\Requests\WalletTopUpRequest;
use App\Http\Requests\DriverWithdrawRequest;
use App\Http\Requests\WalletTransferRequest;
use App\Http\Requests\DriverWalletSetupequest;
use App\Http\Requests\SendPinOtpRequest;
use App\Http\Requests\VerifyPinRequest;
use App\Services\Admin\AccountService;
use App\Services\AgentService;

class WalletController extends Controller
{
    use HttpResponse;

    public function __construct(
        protected WalletService $service,
        protected AgentService $agentService,
        protected AccountService $accountService,
    )
    {}

    public function getBalance()
    {
        return $this->service->getBalance();
    }

    public function fundWallet(FundWalletRequest $request)
    {
        return $this->service->fundWallet($request);
    }

    public function transfer(WalletTransferRequest $request)
    {
        return $this->service->transfer($request);
    }

    public function getTransactions()
    {
        return $this->service->getTransactions();
    }

    public function walletSetup(DriverWalletSetupequest $request, SendVerificationCode $sendVerificationCode)
    {
        return $this->service->walletSetup($request, $sendVerificationCode);
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

    public function sendOtp(SendPinOtpRequest $request)
    {
        return $this->agentService->sendOtp($request);
    }

    public function verifyWalletPin(VerifyPinRequest $request)
    {
        return $this->agentService->verifyPin($request);
    }

    public function changePin(ChangePinRequest $request)
    {
        return $this->agentService->changePin($request);
    }

    public function getCharges()
    {
        return $this->service->getCharges();
    }

    public function updateAccountRecipient()
    {
        return $this->accountService->updateAccountRecipient();
    }
}
