<div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="first_name" :value="__('Nombre')" />
            <x-text-input id="first_name" name="first_name" type="text" class="block mt-1 w-full"
                          :value="old('first_name', $coach->first_name ?? '')" required autofocus />
            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="last_name" :value="__('Apellido')" />
            <x-text-input id="last_name" name="last_name" type="text" class="block mt-1 w-full"
                          :value="old('last_name', $coach->last_name ?? '')" required />
            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="document_id" :value="__('Documento (opcional)')" />
            <x-text-input id="document_id" name="document_id" type="text" class="block mt-1 w-full"
                          :value="old('document_id', $coach->document_id ?? '')" />
            <p class="mt-1 text-xs text-wv-text-secondary">{{ __('Único por equipo.') }}</p>
            <x-input-error :messages="$errors->get('document_id')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="birth_date" :value="__('Fecha de nacimiento (opcional)')" />
            <x-text-input id="birth_date" name="birth_date" type="date" class="block mt-1 w-full"
                          :value="old('birth_date', optional($coach->birth_date ?? null)?->format('Y-m-d'))" />
            <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="phone" :value="__('Teléfono (opcional)')" />
            <x-text-input id="phone" name="phone" type="text" class="block mt-1 w-full"
                          :value="old('phone', $coach->phone ?? '')" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="email" :value="__('Correo (opcional)')" />
            <x-text-input id="email" name="email" type="email" class="block mt-1 w-full"
                          :value="old('email', $coach->email ?? '')" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="role" :value="__('Rol / función (opcional)')" />
        <select id="role" name="role"
                class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">
            <option value="">— {{ __('Sin rol definido') }} —</option>
            @foreach (['manager' => 'Manager', 'head' => 'Head Coach', 'bench' => 'Bench Coach', 'pitching' => 'Pitching Coach', 'hitting' => 'Hitting Coach', 'assistant' => 'Assistant', 'bullpen' => 'Bullpen Coach', '1b' => '1B Coach', '3b' => '3B Coach', 'bench' => 'Bench'] as $key => $label)
                <option value="{{ $key }}" {{ (string) old('role', $coach->role ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('role')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="notes" :value="__('Notas (opcional)')" />
        <textarea id="notes" name="notes" rows="3"
                  class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">{{ old('notes', $coach->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="photo" :value="__('Foto del entrenador (opcional)')" />
            @if (! empty($coach->photo_path))
                <div class="mt-1 mb-2 flex items-center gap-3">
                    <img src="{{ $coach->photo_url }}" alt="{{ $coach->full_name }}"
                         class="w-16 h-16 rounded-full object-cover border border-wv-border">
                    <p class="text-xs text-wv-text-secondary">{{ basename($coach->photo_path) }}</p>
                </div>
            @endif
            <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"
                   class="block mt-1 w-full text-sm text-wv-text file:mr-3 file:py-2 file:px-3 file:rounded file:border-0 file:bg-wv-accent file:text-wv-text-on-accent file:font-semibold hover:file:bg-wv-accent-hover">
            <p class="mt-1 text-xs text-wv-text-secondary">{{ __('JPG, PNG o WebP. Maximo 2MB.') }}</p>
            <x-input-error :messages="$errors->get('photo')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="document_photo" :value="__('Foto del documento de identidad (opcional)')" />
            @if (! empty($coach->document_photo_path))
                <div class="mt-1 mb-2 flex items-center gap-3">
                    <img src="{{ $coach->document_photo_url }}" alt="Documento"
                         class="w-16 h-16 rounded object-cover border border-wv-border">
                    <p class="text-xs text-wv-text-secondary">{{ basename($coach->document_photo_path) }}</p>
                </div>
            @endif
            <input id="document_photo" name="document_photo" type="file" accept="image/jpeg,image/png,image/webp"
                   class="block mt-1 w-full text-sm text-wv-text file:mr-3 file:py-2 file:px-3 file:rounded file:border-0 file:bg-wv-accent file:text-wv-text-on-accent file:font-semibold hover:file:bg-wv-accent-hover">
            <p class="mt-1 text-xs text-wv-text-secondary">{{ __('JPG, PNG o WebP. Maximo 2MB.') }}</p>
            <x-input-error :messages="$errors->get('document_photo')" class="mt-2" />
        </div>
    </div>

    <div class="flex items-center">
        <input id="active" name="active" type="checkbox" value="1"
               {{ old('active', $coach->active ?? true) ? 'checked' : '' }}
               class="rounded border-wv-border bg-wv-surface text-wv-accent focus:ring-wv-accent focus:ring-offset-wv-bg">
        <label for="active" class="ms-2 text-sm text-wv-text">
            {{ __('Entrenador activo en el equipo') }}
        </label>
    </div>
</div>
