<?php

namespace App\Jobs;

use App\Services\Tenant\Apple\AppleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DepProcessOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order_id;
    public $type;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $order_id, $type)
    {
        $this->order_id = $order_id;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AppleService $appleService)
    {
        $appleService->processOrder($this->order_id, $this->type);
    }
}
