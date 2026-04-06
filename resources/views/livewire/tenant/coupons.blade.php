<?php

use App\Models\BusinessImage;
use App\Models\Coupon;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('components.layouts.tenant')] class extends Component {
    use WithPagination, WithFileUploads;

    public string $search = '';

    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    #[Validate('required|string|max:50|alpha_dash')]
    public string $code = '';

    #[Validate('nullable|string|max:150')]
    public string $description = '';

    #[Validate('required|in:percentage,fixed')]
    public string $type = 'percentage';

    #[Validate('required|numeric|min:0.01')]
    public string $value = '';

    #[Validate('nullable|numeric|min:0')]
    public string $min_amount = '';

    #[Validate('nullable|integer|min:1')]
    public string $max_uses = '';

    #[Validate('nullable|date|after:today')]
    public string $expires_at = '';

    public bool $is_active = true;

    #[Validate('nullable|image|mimes:jpg,jpeg,png,webp|max:10240')]
    public $image1 = null;

    #[Validate('nullable|image|mimes:jpg,jpeg,png,webp|max:10240')]
    public $image2 = null;

    /** IDs of existing BusinessImage records (when editing) */
    public ?int $existingImage1Id = null;
    public ?int $existingImage2Id = null;

    public bool $removeImage1 = false;
    public bool $removeImage2 = false;

    public function with(): array
    {
        return [
            'coupons' => Coupon::query()
                ->with('images')
                ->when($this->search, fn ($q) => $q->where('code', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%"))
                ->orderByDesc('created_at')
                ->paginate(10),
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $coupon = Coupon::with('images')->findOrFail($id);
        $this->editingId        = $id;
        $this->code             = $coupon->code;
        $this->description      = $coupon->description ?? '';
        $this->type             = $coupon->type;
        $this->value            = (string) $coupon->value;
        $this->min_amount       = $coupon->min_amount ? (string) $coupon->min_amount : '';
        $this->max_uses         = $coupon->max_uses ? (string) $coupon->max_uses : '';
        $this->expires_at       = $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : '';
        $this->is_active        = $coupon->is_active;
        $this->existingImage1Id = $coupon->images->get(0)?->id;
        $this->existingImage2Id = $coupon->images->get(1)?->id;
        $this->removeImage1     = false;
        $this->removeImage2     = false;
        $this->showModal        = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'code'        => strtoupper($this->code),
            'description' => $this->description ?: null,
            'type'        => $this->type,
            'value'       => $this->value,
            'min_amount'  => $this->min_amount ?: null,
            'max_uses'    => $this->max_uses ?: null,
            'expires_at'  => $this->expires_at ?: null,
            'is_active'   => $this->is_active,
        ];

        $coupon = $this->editingId
            ? tap(Coupon::findOrFail($this->editingId))->update($data)
            : Coupon::create($data);

        $this->syncImage(1, $coupon, $this->existingImage1Id);
        $this->syncImage(2, $coupon, $this->existingImage2Id);

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId      = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $coupon = Coupon::with('images')->findOrFail($this->deletingId);

            foreach ($coupon->images as $image) {
                $this->deleteFile($image->path);
                $image->delete();
            }

            $coupon->delete();
        }

        $this->showDeleteModal = false;
        $this->deletingId      = null;
    }

    public function generateCode(): void
    {
        $this->code = strtoupper(Str::random(8));
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(url('/'));
    }

    private function syncImage(int $slot, Coupon $coupon, ?int $existingId): void
    {
        $upload = $slot === 1 ? $this->image1 : $this->image2;
        $remove = $slot === 1 ? $this->removeImage1 : $this->removeImage2;

        if ($upload) {
            if ($existingId) {
                $old = BusinessImage::find($existingId);
                if ($old) {
                    $this->deleteFile($old->path);
                    $old->delete();
                }
            }
            $this->storeImage($upload, $coupon->id);

            return;
        }

        if ($remove && $existingId) {
            $old = BusinessImage::find($existingId);
            if ($old) {
                $this->deleteFile($old->path);
                $old->delete();
            }
        }
    }

    private function storeImage($file, int $couponId): void
    {
        $tenantId  = tenant('id');
        $filename  = Str::random(10) . $file->getClientOriginalExtension();
        $directory = base_path("storage/app/public/tenants/{$tenantId}/images");
        $dbPath    = "tenants/{$tenantId}/images/{$filename}";

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $originalSize = $file->getSize();

        ImageManager::withDriver(new Driver())
            ->read($file->getRealPath())
            ->scale(height: 800)
            ->toWebp(85)
            ->save("{$directory}/{$filename}");

        BusinessImage::create([
            'filename'        => $filename,
            'original_name'   => $file->getClientOriginalName(),
            'path'            => $dbPath,
            'mime_type'       => 'image/webp',
            'size'            => $originalSize,
            'compressed_size' => filesize("{$directory}/{$filename}"),
            'imageable_type'  => Coupon::class,
            'imageable_id'    => $couponId,
        ]);
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            $fullPath = base_path("storage/app/public/{$path}");
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    private function resetForm(): void
    {
        $this->code             = '';
        $this->description      = '';
        $this->type             = 'percentage';
        $this->value            = '';
        $this->min_amount       = '';
        $this->max_uses         = '';
        $this->expires_at       = '';
        $this->is_active        = true;
        $this->image1           = null;
        $this->image2           = null;
        $this->existingImage1Id = null;
        $this->existingImage2Id = null;
        $this->removeImage1     = false;
        $this->removeImage2     = false;
        $this->resetValidation();
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    @include('livewire.tenant.partials.navbar')

    <div class="flex">
        @include('livewire.tenant.partials.sidebar')

        <flux:main>
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="xl">Cupones</flux:heading>
                        <flux:text class="text-zinc-500">Crea y gestiona cupones de descuento</flux:text>
                    </div>
                    <flux:button wire:click="openCreate" variant="primary" icon="plus">
                        Nuevo cupón
                    </flux:button>
                </div>

                <div class="mt-6">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por código o descripción..." icon="magnifying-glass" />
                </div>

                <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Descuento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Usos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Expira</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($coupons as $coupon)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            @if ($coupon->images->isNotEmpty())
                                                <img src="{{ $coupon->images->first()->url() }}"
                                                    class="size-10 rounded-lg object-cover" />
                                            @endif
                                            <div>
                                                <span class="font-mono font-bold tracking-wider">{{ $coupon->code }}</span>
                                                @if ($coupon->description)
                                                    <p class="text-xs text-zinc-500">{{ $coupon->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($coupon->type === 'percentage')
                                            <flux:badge color="blue" size="sm">{{ $coupon->value }}%</flux:badge>
                                        @else
                                            <flux:badge color="green" size="sm">${{ number_format($coupon->value, 2) }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-zinc-500">
                                        {{ $coupon->uses_count }}
                                        @if ($coupon->max_uses)
                                            / {{ $coupon->max_uses }}
                                        @else
                                            / ∞
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-zinc-500">
                                        {{ $coupon->expires_at ? $coupon->expires_at->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($coupon->isValid())
                                            <flux:badge color="green" size="sm">Activo</flux:badge>
                                        @elseif ($coupon->isExpired())
                                            <flux:badge color="red" size="sm">Expirado</flux:badge>
                                        @elseif ($coupon->hasReachedMaxUses())
                                            <flux:badge color="orange" size="sm">Agotado</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">Inactivo</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <flux:button wire:click="openEdit({{ $coupon->id }})" size="sm" icon="pencil" />
                                            <flux:button wire:click="confirmDelete({{ $coupon->id }})" size="sm" icon="trash" variant="danger" />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-zinc-500">
                                        No hay cupones creados aún.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($coupons->hasPages())
                        <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                            {{ $coupons->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </flux:main>
    </div>

    {{-- Modal Crear/Editar --}}
    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <flux:heading size="lg">{{ $editingId ? 'Editar cupón' : 'Nuevo cupón' }}</flux:heading>

        <form wire:submit="save" class="mt-4 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>Código <span class="text-red-500">*</span></flux:label>
                    <div class="flex gap-2">
                        <flux:input wire:model="code" placeholder="PROMO10" class="uppercase" />
                        <flux:button type="button" wire:click="generateCode" icon="arrow-path" title="Generar código" />
                    </div>
                    <flux:error name="code" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>Descripción</flux:label>
                    <flux:input wire:model="description" placeholder="Ej: Descuento de temporada" />
                    <flux:error name="description" />
                </flux:field>

                <flux:field>
                    <flux:label>Tipo <span class="text-red-500">*</span></flux:label>
                    <flux:select wire:model="type">
                        <flux:select.option value="percentage">Porcentaje (%)</flux:select.option>
                        <flux:select.option value="fixed">Monto fijo ($)</flux:select.option>
                    </flux:select>
                    <flux:error name="type" />
                </flux:field>

                <flux:field>
                    <flux:label>Valor <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="value" type="number" step="0.01" min="0.01"
                        placeholder="{{ $type === 'percentage' ? '10' : '25.00' }}" />
                    <flux:error name="value" />
                </flux:field>

                <flux:field>
                    <flux:label>Monto mínimo ($)</flux:label>
                    <flux:input wire:model="min_amount" type="number" step="0.01" min="0" placeholder="0.00" />
                    <flux:error name="min_amount" />
                </flux:field>

                <flux:field>
                    <flux:label>Máximo de usos</flux:label>
                    <flux:input wire:model="max_uses" type="number" min="1" placeholder="Ilimitado" />
                    <flux:error name="max_uses" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>Fecha de expiración</flux:label>
                    <flux:input wire:model="expires_at" type="date" />
                    <flux:error name="expires_at" />
                </flux:field>

                <flux:field>
                    <flux:checkbox wire:model="is_active" label="Cupón activo" />
                </flux:field>
            </div>

            {{-- Imágenes del cupón --}}
            <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:text class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Imágenes del cupón (opcional, máx. 2)
                </flux:text>
                <div class="grid grid-cols-2 gap-4">
                    {{-- Imagen 1 --}}
                    <div>
                        @if ($image1)
                            <div class="relative mb-2">
                                <img src="{{ $image1->temporaryUrl() }}" class="h-32 w-full rounded-lg object-cover" />
                                <button type="button" wire:click="$set('image1', null)"
                                    class="absolute right-1 top-1 rounded-full bg-red-600 p-1 text-white shadow hover:bg-red-700">
                                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @elseif ($existingImage1Id && ! $removeImage1)
                            <div class="relative mb-2">
                                <img src="{{ BusinessImage::find($existingImage1Id)?->url() }}"
                                    class="h-32 w-full rounded-lg object-cover" />
                                <button type="button" wire:click="$set('removeImage1', true)"
                                    class="absolute right-1 top-1 rounded-full bg-red-600 p-1 text-white shadow hover:bg-red-700">
                                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endif
                        <flux:input wire:model="image1" type="file" accept="image/*" size="sm" />
                        <flux:error name="image1" />
                    </div>

                    {{-- Imagen 2 --}}
                    <div>
                        @if ($image2)
                            <div class="relative mb-2">
                                <img src="{{ $image2->temporaryUrl() }}" class="h-32 w-full rounded-lg object-cover" />
                                <button type="button" wire:click="$set('image2', null)"
                                    class="absolute right-1 top-1 rounded-full bg-red-600 p-1 text-white shadow hover:bg-red-700">
                                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @elseif ($existingImage2Id && ! $removeImage2)
                            <div class="relative mb-2">
                                <img src="{{ BusinessImage::find($existingImage2Id)?->url() }}"
                                    class="h-32 w-full rounded-lg object-cover" />
                                <button type="button" wire:click="$set('removeImage2', true)"
                                    class="absolute right-1 top-1 rounded-full bg-red-600 p-1 text-white shadow hover:bg-red-700">
                                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endif
                        <flux:input wire:model="image2" type="file" accept="image/*" size="sm" />
                        <flux:error name="image2" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" wire:click="$set('showModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $editingId ? 'Guardar cambios' : 'Crear cupón' }}</span>
                    <span wire:loading>Guardando...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal Confirmar Eliminar --}}
    <flux:modal wire:model="showDeleteModal" class="max-w-sm">
        <div class="text-center">
            <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
                <flux:icon.trash class="size-6 text-red-600 dark:text-red-400" />
            </div>
            <flux:heading size="lg">¿Eliminar cupón?</flux:heading>
            <flux:text class="mt-2 text-zinc-500">Esta acción no se puede deshacer.</flux:text>
        </div>
        <div class="mt-6 flex justify-center gap-3">
            <flux:button wire:click="$set('showDeleteModal', false)">Cancelar</flux:button>
            <flux:button wire:click="delete" variant="danger">Eliminar</flux:button>
        </div>
    </flux:modal>
</div>
