<?php

namespace App\Observers;

use App\Models\BusinessProfile;
use App\Models\Service;
use App\Models\ServiceListing;
use App\Models\Subcategory;

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

    /**
     * The central listing also keeps the main category so search can filter a whole category at once.
     */
    private function categorySlugOf(?string $subcategorySlug): ?string
    {
        if (! $subcategorySlug) {
            return null;
        }

        return Subcategory::query()->with('category')->firstWhere('slug', $subcategorySlug)?->category?->slug;
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
                'category' => $this->categorySlugOf($service->subcategory),
                'subcategory' => $service->subcategory,
                'city' => BusinessProfile::query()->value('city'),
                'phone' => BusinessProfile::query()->value('phone'),
                'is_active' => $service->is_active,
            ]
        );
    }
}
