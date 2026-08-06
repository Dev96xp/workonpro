<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;

it('deletes the tenant storage folder when the tenant is deleted', function () {
    $tenant = Tenant::create([
        'id' => 'storagetest',
        'name' => 'Storage Test',
        'email' => 'storage@example.com',
        'status' => 'active',
        'plan' => 'pro',
    ]);

    // Storage::fake() must run after tenant creation: provisioning the tenant
    // database briefly initializes tenancy (FilesystemTenancyBootstrapper),
    // which rebuilds the "public" disk and would otherwise clobber the fake.
    Storage::fake('public');
    Storage::disk('public')->put('tenants/storagetest/images/photo.webp', 'fake-content');

    expect(Storage::disk('public')->exists('tenants/storagetest/images/photo.webp'))->toBeTrue();

    $tenant->delete();

    expect(Storage::disk('public')->exists('tenants/storagetest/images/photo.webp'))->toBeFalse();
});
