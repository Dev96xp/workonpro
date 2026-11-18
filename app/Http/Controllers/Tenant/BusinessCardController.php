<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\BusinessImage;
use App\Models\BusinessProfile;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BusinessCardController extends Controller
{
    /**
     * The business digital card (/card).
     */
    public function business(): View
    {
        $this->ensurePlanHasCards();

        return view('tenant.businesscard', $this->businessData());
    }

    /**
     * An employee's digital card (/card/{slug}), branded with the business.
     */
    public function employee(Request $request): View
    {
        $employee = $this->findEmployee((string) $request->route('slug'));
        $business = $this->businessData();

        return view('tenant.businesscard', [
            ...$business,
            'person' => $employee,
            'businessPhone' => $employee->phone ?: $business['businessPhone'],
            'businessEmail' => $employee->email,
            'businessWa' => $employee->whatsapp ?: $business['businessWa'],
            'vcardUrl' => url('/card/'.$employee->card_slug.'/vcard'),
        ]);
    }

    public function businessVcard(): Response
    {
        $this->ensurePlanHasCards();

        $data = $this->businessData();

        return $this->vcard($data['businessName'], [
            'ORG:'.$data['businessName'],
            $data['businessPhone'] ? 'TEL;TYPE=WORK,VOICE:'.$data['businessPhone'] : null,
            $data['businessEmail'] ? 'EMAIL:'.$data['businessEmail'] : null,
            $data['businessAddress'] ? 'ADR;TYPE=WORK:;;'.$data['businessAddress'].';'.$data['businessCity'].';;;' : null,
        ]);
    }

    public function employeeVcard(Request $request): Response
    {
        $employee = $this->findEmployee((string) $request->route('slug'));
        $data = $this->businessData();

        return $this->vcard($employee->name, [
            'ORG:'.$data['businessName'],
            $employee->role ? 'TITLE:'.$employee->role : null,
            ($employee->phone ?: $data['businessPhone']) ? 'TEL;TYPE=WORK,VOICE:'.($employee->phone ?: $data['businessPhone']) : null,
            'EMAIL:'.$employee->email,
        ]);
    }

    private function ensurePlanHasCards(): void
    {
        abort_unless(Tenant::hasFeature(tenant('plan'), 'businesscard'), 403);
    }

    private function findEmployee(string $slug): Employee
    {
        $this->ensurePlanHasCards();
        abort_unless(Tenant::hasTeamCards(tenant('plan')), 404);

        $employee = Employee::query()->where('card_slug', $slug)->firstOrFail();

        abort_unless($employee->hasPublicCard(), 404);

        return $employee;
    }

    /**
     * @return array{businessName: string, businessSlogan: ?string, businessAddress: ?string, businessCity: ?string, businessPhone: ?string, businessEmail: ?string, businessWa: ?string, logoImage: ?BusinessImage, initials: string, tenantDomain: string, person: ?Employee, vcardUrl: string}
     */
    private function businessData(): array
    {
        $profile = BusinessProfile::first();
        $businessName = $profile?->business_name ?: tenant('name');

        return [
            'businessName' => $businessName,
            'businessSlogan' => $profile?->slogan,
            'businessAddress' => tenant()->isPersonal() ? null : $profile?->address,
            'businessCity' => $profile?->city,
            'businessPhone' => $profile?->phone,
            'businessEmail' => $profile?->email,
            'businessWa' => $profile?->whatsapp,
            'logoImage' => BusinessImage::gallery()->where('is_logo', true)->first(),
            'initials' => Str::of($businessName)->explode(' ')->take(2)->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))->implode('') ?: '?',
            'tenantDomain' => request()->getHost(),
            'person' => null,
            'vcardUrl' => url('/card/vcard'),
        ];
    }

    /**
     * @param  list<?string>  $lines
     */
    private function vcard(string $name, array $lines): Response
    {
        $vcard = implode("\r\n", array_filter([
            'BEGIN:VCARD',
            'VERSION:3.0',
            'FN:'.$name,
            ...$lines,
            'URL:'.url('/'),
            'END:VCARD',
        ]));

        return response($vcard, 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.Str::slug($name).'.vcf"',
        ]);
    }
}
