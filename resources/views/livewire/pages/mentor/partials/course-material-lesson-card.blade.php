<article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="font-semibold text-slate-900">{{ $lesson->title }}</h3>
                @if ($lesson->is_preview)
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Preview</span>
                @endif
            </div>
            @if ($lesson->description)
                <p class="mt-1 text-sm text-slate-600">{{ $lesson->description }}</p>
            @endif
        </div>
        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-slate-600">{{ $lesson->materials->count() }} material</span>
    </div>

    <div class="mt-4 space-y-3">
        @forelse ($lesson->materials as $material)
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium text-slate-900">{{ $material->title }}</p>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium uppercase text-slate-600">{{ $material->type->value }}</span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $material->status }}</span>
                        </div>
                        @if ($material->description)
                            <p class="mt-1 text-sm text-slate-600">{{ $material->description }}</p>
                        @endif
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $material->original_filename ?: 'File tanpa nama asli' }}
                            @if ($material->file_size)
                                · {{ number_format($material->file_size / 1024, 1) }} KB
                            @endif
                        </p>
                    </div>

                    @if ($canModifyMaterials)
                        <div class="flex flex-col gap-2 sm:min-w-72">
                            <input wire:model="replacementFiles.{{ $material->id }}" type="file" class="block w-full text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                            <x-input-error :messages="$errors->get('replacementFiles.'.$material->id)" class="mt-1" />
                            <div class="flex flex-wrap gap-2">
                                <button type="button" wire:click="replaceMaterialFile({{ $material->id }})" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">Ganti file</button>
                                <button type="button" wire:click="markMaterialDeleted({{ $material->id }})" wire:confirm="Tandai material ini sebagai deleted dan hapus file private?" class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">Hapus</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="rounded-lg bg-white p-4 text-sm text-slate-500">Belum ada material untuk lesson ini.</p>
        @endforelse
    </div>
</article>
