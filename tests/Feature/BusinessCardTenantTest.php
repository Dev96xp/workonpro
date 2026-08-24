<?php

use App\Models\BusinessProfile;
use App\Models\Tenant;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

function makeBusinessCardTestTenant(string $id): Tenant
{
    $tenant = Tenant::create([
        'id' => $id,
        'name' => "Tenant {$id}",
        'email' => "{$id}@example.com",
        'status' => 'active',
        'plan' => 'starter',
    ]);
    $tenant->domains()->create(['domain' => $id]);

    return $tenant;
}

it('shows the tenant business card page with the profile data', function () {
    $tenant = makeBusinessCardTestTenant('cardtenant');
    tenancy()->initialize($tenant);

    BusinessProfile::create([
        'business_name' => 'Barberia El Corte',
        'phone' => '(555) 123-4567',
        'whatsapp' => '+15551234567',
        'email' => 'contacto@elcorte.com',
        'address' => 'Calle 5',
        'city' => 'Monterrey',
    ]);

    $this->withoutMiddleware([PreventAccessFromCentralDomains::class, InitializeTenancyBySubdomain::class])
        ->get(route('tenant.businesscard', ['subdomain' => $tenant->id]))
        ->assertOk()
        ->assertSee('Barberia El Corte')
        ->assertSee('(555) 123-4567')
        ->assertSee('Monterrey');
});

it('falls back to the tenant name and hides contact buttons when the profile has no data', function () {
    $tenant = makeBusinessCardTestTenant('cardtenantfallback');
    tenancy()->initialize($tenant);

    $this->withoutMiddleware([PreventAccessFromCentralDomains::class, InitializeTenancyBySubdomain::class])
        ->get(route('tenant.businesscard', ['subdomain' => $tenant->id]))
        ->assertOk()
        ->assertSee($tenant->name)
        ->assertDontSee('wa.me', false);
});

it('downloads a vcard with the business contact info', function () {
    $tenant = makeBusinessCardTestTenant('cardtenantvcard');
    tenancy()->initialize($tenant);

    BusinessProfile::create([
        'business_name' => 'Barberia El Corte',
        'phone' => '(555) 123-4567',
        'email' => 'contacto@elcorte.com',
        'address' => 'Calle 5',
        'city' => 'Monterrey',
    ]);

    $this->withoutMiddleware([PreventAccessFromCentralDomains::class, InitializeTenancyBySubdomain::class])
        ->get(route('tenant.businesscard.vcard', ['subdomain' => $tenant->id]))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/vcard; charset=utf-8')
        ->assertSee('FN:Barberia El Corte')
        ->assertSee('TEL;TYPE=WORK,VOICE:(555) 123-4567')
        ->assertSee('EMAIL:contacto@elcorte.com');
});
