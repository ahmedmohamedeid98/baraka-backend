<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\TransactionResource;
use App\Http\Resources\WalletResource;
use Illuminate\Http\Request;

class UserWalletController extends ApiController
{
    /**
     * Get user wallet
     * GET /api/v1/wallet
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        return $this->successResponse([
            'wallet' => new WalletResource($wallet),
        ]);
    }

    /**
     * Get wallet transactions
     * GET /api/v1/wallet/transactions
     */
    public function transactions(Request $request)
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        $perPage = min($request->get('per_page', 20), 50);

        $transactions = $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginatedResponse($transactions, TransactionResource::class);
    }
}
