<?php

namespace App\Jobs;

use App\Services\Tenant\Apple\AppleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DepStatusRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $request_id;
    protected $order_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $request_id, int $order_id)
    {
        $this->order_id = $order_id;
        $this->request_id = $request_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AppleService $appleService)
    {
        $appleService->processResponse($this->request_id, $this->order_id);
    }
}
