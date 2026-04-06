<?php

use App\Models\BusinessImage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tenant')] class extends Component {
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    private function imageLimit(): int
    {
        return match (tenant('plan')) {
            'pro', 'enterprise' => 100,
            default             => 40,
        };
    }

    public function with(): array
    {
        return [
            'images'     => BusinessImage::gallery()->orderByDesc('created_at')->get(),
            'limit'      => $this->imageLimit(),
            'imageCount' => BusinessImage::gallery()->count(),
        ];
    }

    #[On('images-uploaded')]
    public function refreshImages(): void
    {
        // Livewire re-renders automatically on event
    }

    public function setFeatured(int $id): void
    {
        BusinessImage::setFeatured($id);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId      = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $image    = BusinessImage::findOrFail($this->deletingId);
            $fullPath = storage_path('app/public/' . $image->path);

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            $image->delete();
        }

        $this->showDeleteModal = false;
        $this->deletingId      = null;
    }

    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(url('/'));
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
                        <flux:heading size="xl">Imágenes</flux:heading>
                        <flux:text class="text-zinc-500">
                            {{ $imageCount }} / {{ $limit }} imágenes usadas
                        </flux:text>
                    </div>
                </div>

                {{-- Dropzone upload area --}}
                @if ($imageCount < $limit)
                    <div class="mt-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:heading size="lg">Subir imágenes</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">
                            Formatos: JPG, PNG, WEBP. Máximo 10 MB por imagen. Se comprimen automáticamente.
                        </flux:text>
                        <div class="mt-4 rounded-lg border-2 border-dashed border-zinc-300 p-6 dark:border-zinc-600"
                             id="image-dropzone"
                             x-data
                             x-init="
                                new Dropzone($el, {
                                    url: '/images/upload',
                                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                                    acceptedFiles: 'image/*',
                                    paramName: 'file',
                                    dictDefaultMessage: 'Arrastra imágenes aquí o haz clic para seleccionar',
                                    complete: function(file) { this.removeFile(file); },
                                    queuecomplete: function() { Livewire.dispatch('images-uploaded'); },
                                });
                             ">
                        </div>
                    </div>
                @else
                    <div class="mt-6 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                        Has alcanzado el límite de {{ $limit }} imágenes.
                        @if (tenant('plan') === 'basic')
                            <a href="#" class="font-semibold underline">Actualiza tu plan</a> para subir hasta 100 imágenes.
                        @endif
                    </div>
                @endif

                {{-- Image grid --}}
                @if ($images->isNotEmpty())
                    <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                        @foreach ($images as $image)
                            <div class="group relative overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                <img
                                    src="{{ $image->url() }}"
                                    alt="{{ $image->original_name }}"
                                    class="aspect-square w-full object-cover"
                                    loading="lazy"
                                />
                                <div class="absolute inset-0 flex items-end bg-gradient-to-t from-black/60 to-transparent opacity-0 transition-opacity group-hover:opacity-100">
                                    <div class="w-full p-2">
                                        <p class="truncate text-xs text-white">{{ $image->original_name }}</p>
                                        <p class="text-xs text-zinc-300">
                                            {{ number_format($image->compressed_size / 1024, 0) }} KB
                                        </p>
                                    </div>
                                </div>
                                <button
                                    wire:click="setFeatured({{ $image->id }})"
                                    class="absolute left-2 top-2 rounded-full p-1 shadow transition-colors {{ $image->is_featured ? 'flex bg-yellow-400 text-white' : 'hidden bg-white/80 text-zinc-400 hover:text-yellow-400 group-hover:flex' }}"
                                    title="{{ $image->is_featured ? 'Imagen destacada' : 'Marcar como destacada' }}"
                                >
                                    <svg class="size-4" fill="{{ $image->is_featured ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                    </svg>
                                </button>
                                <button
                                    wire:click="confirmDelete({{ $image->id }})"
                                    class="absolute right-2 top-2 hidden rounded-full bg-red-600 p-1 text-white shadow hover:bg-red-700 group-hover:flex"
                                    title="Eliminar"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-xl border border-dashed border-zinc-300 bg-white p-12 text-center dark:border-zinc-600 dark:bg-zinc-800">
                        <flux:icon.photo class="mx-auto size-10 text-zinc-400" />
                        <flux:text class="mt-2 text-zinc-500">No hay imágenes subidas aún.</flux:text>
                    </div>
                @endif
            </div>
        </flux:main>
    </div>

    {{-- Modal Confirmar Eliminar --}}
    <flux:modal wire:model="showDeleteModal" class="max-w-sm">
        <div class="text-center">
            <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
                <flux:icon.trash class="size-6 text-red-600 dark:text-red-400" />
            </div>
            <flux:heading size="lg">¿Eliminar imagen?</flux:heading>
            <flux:text class="mt-2 text-zinc-500">Esta acción no se puede deshacer.</flux:text>
        </div>
        <div class="mt-6 flex justify-center gap-3">
            <flux:button wire:click="$set('showDeleteModal', false)">Cancelar</flux:button>
            <flux:button wire:click="delete" variant="danger">Eliminar</flux:button>
        </div>
    </flux:modal>


    @assets
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
    @endassets

</div>
