<?php

namespace App\Domains\Tenant\Netsuite;

use App\Exceptions\NetsuiteExpiredToken;
use App\Models\Credential;
use App\Repositories\Tenant\Netsuite\NetsuiteAccountRepository;
use App\Repositories\Tenant\Netsuite\NetsuiteOauthRepository;
use App\Repositories\Tenant\Netsuite\NetsuiteOrderRepository;
use GuzzleHttp\Exception\RequestException;


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

    public function refresh() {
        return $this->oauthRepository->refresh();
    }

    public function getOrder(int $order_id)
    {
        try {
            return $this->orderRepository->getOrder($order_id);
        } catch (RequestException $ex) {
            //try and refresh
            $this->refresh();
            return $this->orderRepository->getOrder($order_id);
        }
    }

    public function getRecentOrders($date) {
        try {
            return $this->orderRepository->recentOrders($date);
        } catch (RequestException $ex) {
            //try and refresh
            $this->refresh();
            return $this->orderRepository->recentOrders($date);
        }
    }

    public function updateOrder(int $order_id, $resp, $status) {
        try {
            return $this->orderRepository->update($order_id, $resp, $status);
        } catch (RequestException $ex) {
            //try and refresh
            $this->refresh();
            return $this->orderRepository->update($order_id, $resp, $status);
        }
    }

    public function getAccounts($date) {
        try {
            return $this->accountRepository->accounts($date);
        } catch (RequestException $ex) {
            //try and refresh
            $this->refresh();
            return $this->accountRepository->accounts($date);
        }
    }
}
