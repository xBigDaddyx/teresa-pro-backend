<?php

namespace App\Events;

use App\Domain\Accuracy\CartonBox\Entities\CartonBox;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartonProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $carton;
    public $nextStep;

    public function __construct(CartonBox $carton, string $nextStep)
    {
        $this->carton = $carton;
        $this->nextStep = $nextStep;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . request()->header('X-Tenant-ID')), // Channel berdasarkan tenant
        ];
    }

    public function broadcastAs(): string
    {
        return 'carton.processed';
    }

    public function broadcastWith(): array
    {
        return [
            'carton_id' => $this->carton->getId(),
            'barcode' => $this->carton->getBarcode(),
            'internal_sku' => $this->carton->getInternalSku(),
            'validation_status' => $this->carton->getValidationStatus(),
            'status' => $this->carton->getStatus()->getValue(),
            'processed_at' => $this->carton->getProcessedAt(),
            'processed_by' => $this->carton->getProcessedBy(),
            'next_step' => $this->nextStep,
        ];
    }
}
