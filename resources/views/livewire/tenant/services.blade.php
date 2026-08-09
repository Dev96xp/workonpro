<?php

use App\Models\BusinessImage;
use App\Models\Category;
use App\Models\Service;
use App\Models\Tenant;
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

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('nullable|string|max:500')]
    public string $description = '';

    #[Validate('required|string')]
    public string $category = '';

    #[Validate('nullable|numeric|min:0')]
    public string $price = '';

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

    private function serviceLimit(): ?int
    {
        return Tenant::planLimit(tenant('plan'), 'services');
    }

    public function with(): array
    {
        $limit = $this->serviceLimit();
        $count = Service::count();

        return [
            'services' => Service::query()
                ->with('images')
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(10),
            'categoryOptions' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'categoryLabels' => Category::query()->orderBy('sort_order')->pluck('name', 'slug'),
            'serviceLimit' => $limit,
            'serviceCount' => $count,
            'canCreateService' => $limit === null || $count < $limit,
        ];
    }

    public function openCreate(): void
    {
        if (! (Tenant::withinPlanLimit(tenant('plan'), 'services', Service::count()))) {
            $this->addError('name', 'Alcanzaste el límite de servicios de tu plan.');

            return;
        }

        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $service = Service::with('images')->findOrFail($id);
        $this->editingId        = $id;
        $this->name              = $service->name;
        $this->description       = $service->description ?? '';
        $this->category          = $service->category ?? '';
        $this->price              = $service->price !== null ? (string) $service->price : '';
        $this->is_active         = $service->is_active;
        $this->existingImage1Id  = $service->images->get(0)?->id;
        $this->existingImage2Id  = $service->images->get(1)?->id;
        $this->removeImage1      = false;
        $this->removeImage2      = false;
        $this->showModal         = true;
    }

    public function save(): void
    {
        $this->validate();

        if (! $this->editingId && ! Tenant::withinPlanLimit(tenant('plan'), 'services', Service::count())) {
            $this->addError('name', 'Alcanzaste el límite de servicios de tu plan.');

            return;
        }

        $data = [
            'name'        => $this->name,
            'description' => $this->description ?: null,
            'category'    => $this->category,
            'price'       => $this->price !== '' ? $this->price : null,
            'is_active'   => $this->is_active,
        ];

        $service = $this->editingId
            ? tap(Service::findOrFail($this->editingId))->update($data)
            : Service::create($data);

        $this->syncImage(1, $service, $this->existingImage1Id);
        $this->syncImage(2, $service, $this->existingImage2Id);

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
            $service = Service::with('images')->findOrFail($this->deletingId);

            foreach ($service->images as $image) {
                $this->deleteFile($image->path);
                $image->delete();
            }

            $service->delete();
        }

        $this->showDeleteModal = false;
        $this->deletingId      = null;
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

    private function syncImage(int $slot, Service $service, ?int $existingId): void
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
            $this->storeImage($upload, $service->id);

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

    private function storeImage($file, int $serviceId): void
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
            'imageable_type'  => Service::class,
            'imageable_id'    => $serviceId,
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
        $this->name              = '';
        $this->description       = '';
        $this->category          = '';
        $this->price              = '';
        $this->is_active         = true;
        $this->image1             = null;
        $this->image2             = null;
        $this->existingImage1Id  = null;
        $this->existingImage2Id  = null;
        $this->removeImage1      = false;
        $this->removeImage2      = false;
        $this->resetValidation();
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    @include('livewire.tenant.partials.navbar')

    <div class="flex">
        @include('livewire.tenant.partials.sidebar')

        <flux:main>
            <div class="p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <flux:heading size="xl">Servicios</flux:heading>
                        <flux:text class="text-zinc-500">
                            Administra los servicios que ofrece tu negocio
                            · {{ $serviceCount }} / {{ $serviceLimit ?? '∞' }} usados
                        </flux:text>
                    </div>
                    <flux:button wire:click="openCreate" variant="primary" icon="plus" class="sm:w-auto" :disabled="! $canCreateService">
                        Nuevo servicio
                    </flux:button>
                </div>

                @unless ($canCreateService)
                    <div class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                        Has alcanzado el límite de {{ $serviceLimit }} servicios de tu plan.
                        @if (tenant('plan') === 'basic')
                            <a href="#" class="font-semibold underline">Actualiza tu plan</a> para agregar más.
                        @endif
                    </div>
                @endunless

                <div class="mt-6">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o descripción..." icon="magnifying-glass" />
                </div>

                <div class="mt-4 overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Servicio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Categoría</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Precio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($services as $service)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            @if ($service->images->isNotEmpty())
                                                <img src="{{ $service->images->first()->url() }}"
                                                    class="size-10 rounded-lg object-cover" />
                                            @endif
                                            <div>
                                                <span class="font-medium">{{ $service->name }}</span>
                                                @if ($service->description)
                                                    <p class="text-xs text-zinc-500">{{ Str::limit($service->description, 60) }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-zinc-500">
                                        @if ($service->category)
                                            <flux:badge size="sm">{{ $categoryLabels[$service->category] ?? $service->category }}</flux:badge>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-zinc-500">
                                        {{ $service->price !== null ? '$' . number_format($service->price, 2) : '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($service->is_active)
                                            <flux:badge color="green" size="sm">Activo</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">Inactivo</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <flux:button wire:click="openEdit({{ $service->id }})" size="sm" icon="pencil" />
                                            <flux:button wire:click="confirmDelete({{ $service->id }})" size="sm" icon="trash" variant="danger" />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-zinc-500">
                                        No hay servicios creados aún.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($services->hasPages())
                        <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                            {{ $services->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </flux:main>
    </div>

    {{-- Modal Crear/Editar --}}
    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <flux:heading size="lg">{{ $editingId ? 'Editar servicio' : 'Nuevo servicio' }}</flux:heading>

        <form wire:submit="save" class="mt-4 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>Nombre del servicio <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="name" placeholder="Ej: Reparación de techo" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>Descripción</flux:label>
                    <flux:textarea wire:model="description" rows="3" placeholder="Describe en qué consiste el servicio..." />
                    <flux:error name="description" />
                </flux:field>

                <flux:field>
                    <flux:label>Categoría <span class="text-red-500">*</span></flux:label>
                    <flux:select wire:model="category">
                        <flux:select.option value="">Selecciona una categoría</flux:select.option>
                        @foreach ($categoryOptions as $option)
                            <flux:select.option value="{{ $option->slug }}">{{ $option->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="category" />
                </flux:field>

                <flux:field>
                    <flux:label>Precio base ($)</flux:label>
                    <flux:input wire:model="price" type="number" step="0.01" min="0" placeholder="0.00" />
                    <flux:error name="price" />
                </flux:field>

                <flux:field class="flex items-end">
                    <flux:checkbox wire:model="is_active" label="Servicio activo" />
                </flux:field>
            </div>

            {{-- Imágenes del servicio --}}
            <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:text class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Imágenes del servicio (opcional, máx. 2)
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
                    <span wire:loading.remove>{{ $editingId ? 'Guardar cambios' : 'Crear servicio' }}</span>
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
            <flux:heading size="lg">¿Eliminar servicio?</flux:heading>
            <flux:text class="mt-2 text-zinc-500">Esta acción no se puede deshacer.</flux:text>
        </div>
        <div class="mt-6 flex justify-center gap-3">
            <flux:button wire:click="$set('showDeleteModal', false)">Cancelar</flux:button>
            <flux:button wire:click="delete" variant="danger">Eliminar</flux:button>
        </div>
    </flux:modal>
</div>
