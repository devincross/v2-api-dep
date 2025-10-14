<?php

namespace App\Domains\Tenant\Netsuite;

use App\Models\Credential;
use App\Repositories\Tenant\Netsuite\NetsuiteAccountRepository;
use App\Repositories\Tenant\Netsuite\NetsuiteOauthRepository;
use App\Repositories\Tenant\Netsuite\NetsuiteOrderRepository;


class NetsuiteDomain
{
    public function __construct(
        protected NetsuiteAccountRepository $accountRepository,
        protected NetsuiteOrderRepository $orderRepository,
        protected NetsuiteOauthRepository $oauthRepository
    ) {}

    public function oAuthSetup(array $data) {
        return $this->oauthRepository->oAuthSetup($data);
    }

    public function generateRedirect() {
        return $this->oauthRepository->generateRedirect();
    }

    public function getOrder(int $order_id) {
        return $this->orderRepository->getOrder($order_id);
    }

    public function getRecentOrders($date) {
        return $this->orderRepository->recentOrders($date);
    }

    public function updateOrder(int $order_id, $resp, $status) {
        return $this->orderRepository->update($order_id, $resp, $status);
    }

    public function getAccounts($date) {
        return $this->accountRepository->accounts($date);
    }
}
