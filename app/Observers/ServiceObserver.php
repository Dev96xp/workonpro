<?php

namespace App\Observers;

use App\Models\BusinessProfile;
use App\Models\Service;
use App\Models\ServiceListing;

class ServiceObserver
{
    /**
     * Handle the Service "created" event.
     */
    public function created(Service $service): void
    {
        $this->sync($service);
    }

    /**
     * Handle the Service "updated" event.
     */
    public function updated(Service $service): void
    {
        $this->sync($service);
    }

    /**
     * Handle the Service "deleted" event.
     */
    public function deleted(Service $service): void
    {
        if (! tenancy()->initialized) {
            return;
        }

        ServiceListing::query()
            ->where('tenant_id', tenant('id'))
            ->where('service_id', $service->id)
            ->delete();
    }

    private function sync(Service $service): void
    {
        if (! tenancy()->initialized) {
            return;
        }

        ServiceListing::query()->updateOrCreate(
            [
                'tenant_id' => tenant('id'),
                'service_id' => $service->id,
            ],
            [
                'name' => $service->name,
                'description' => $service->description,
                'price' => $service->price,
                'category' => $service->category,
                'city' => BusinessProfile::query()->value('city'),
                'phone' => BusinessProfile::query()->value('phone'),
                'is_active' => $service->is_active,
            ]
        );
    }
}
