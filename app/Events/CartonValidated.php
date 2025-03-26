<?php

namespace App\Events;

use App\Domain\Accuracy\CartonBox\Entities\CartonBox;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartonValidated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $carton;

    public function __construct(CartonBox $carton)
    {
        $this->carton = $carton;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . request()->header('X-Tenant-ID')),
        ];
    }

    public function broadcastAs(): string
    {
        return 'carton.validated';
    }

    public function broadcastWith(): array
    {
        return [
            'carton_id' => $this->carton->getId(),
            'barcode' => $this->carton->getBarcode(),
            'internal_sku' => $this->carton->getInternalSku(),
            'validation_status' => $this->carton->getValidationStatus(),
            'status' => $this->carton->getStatus()->getValue(),
            'validated_at' => $this->carton->getProcessedAt(), // Gunakan processed_at sebagai proxy, sesuaikan jika ada validated_at
            'validated_by' => $this->carton->getProcessedBy(), // Gunakan processed_by sebagai proxy, sesuaikan jika ada validated_by
        ];
    }
}
