<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\WalletService;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\FundWalletRequest;
use App\Http\Requests\WalletTransferRequest;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Payment\PaystackPaymentController;
use App\Http\Requests\WalletSetTransactionPinRequest;

class WalletController extends Controller
{

    use HttpResponse;

    protected $service; 

    public function __construct(WalletService $service){
        $this->service = $service;
    }

    public function getBalance(){
        return $this->response($this->service->getBalance());
    }

    public function fundWallet(FundWalletRequest $request){
        return $this->response($this->service->fundWallet($request));
    }

    public function transfer(WalletTransferRequest $request){
        return $this->response($this->service->transfer($request));

    }

    public function getTransactions(){
        return $this->response($this->service->getTransactions());
    }

    public function setTransactionPin(WalletSetTransactionPinRequest $request){
        return $this->response($this->service->setTransactionPin($request));
    }

    public function getTransactionPin(){
        return $this->response($this->service->getTransactionPin());
    }
}
