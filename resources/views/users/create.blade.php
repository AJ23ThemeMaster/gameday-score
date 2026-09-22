<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Crear usuario') }}</h2></x-slot>
    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-between items-center">
                <a href="{{ route('users.index') }}" class="text-sm text-wv-accent hover:text-wv-accent-hover">← {{ __('Volver al listado') }}</a>
            </div>
            @include('partials._flash')

            <div class="bg-wv-surface border border-wv-border rounded-card">
                <form method="POST" action="{{ route('users.store') }}" class="p-6">
                    @csrf

                    <div class="space-y-4">
                        <div>
                            <x-input-label for="name" :value="__('Nombre')" />
                            <x-text-input id="name" name="name" type="text" class="block mt-1 w-full"
                                          :value="old('name')" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Correo electrónico')" />
                            <x-text-input id="email" name="email" type="email" class="block mt-1 w-full"
                                          :value="old('email')" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="password" :value="__('Contraseña inicial')" />
                            <x-text-input id="password" name="password" type="password" class="block mt-1 w-full"
                                          required autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            <p class="mt-1 text-xs text-wv-text-secondary">{{ __('Mínimo 8 caracteres. El usuario podrá cambiarla después desde su perfil.') }}</p>
                        </div>

                        <div>
                            <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block mt-1 w-full"
                                          required autocomplete="new-password" />
                        </div>

                        {{-- Roles del usuario --}}
                        <div>
                            <x-input-label :value="__('Roles del usuario')" />
                            <p class="mt-1 text-xs text-wv-text-secondary mb-2">
                                {{ __('Marca los roles que tendrá este usuario. Los permisos se heredan de los roles asignados.') }}
                            </p>

                            @if ($roles->isEmpty())
                                <div class="text-sm text-wv-alert border border-wv-alert/40 bg-wv-alert/10 rounded-md p-3">
                                    {{ __('No hay roles registrados. Crea al menos uno desde') }}
                                    <a href="{{ route('roles.index') }}" class="underline text-wv-accent">{{ __('Roles y permisos') }}</a>.
                                </div>
                            @else
                                <div class="space-y-2 border border-wv-border rounded-md p-3 bg-wv-bg">
                                    @foreach ($roles as $r)
                                        <label class="flex items-center gap-3 bg-wv-surface px-3 py-2 rounded border border-wv-border hover:border-wv-accent/60 cursor-pointer transition">
                                            <input type="checkbox" name="roles[]" value="{{ $r->name }}"
                                                   {{ in_array($r->name, old('roles', []), true) ? 'checked' : '' }}
                                                   class="rounded border-wv-border bg-wv-surface text-wv-accent focus:ring-wv-accent focus:ring-offset-wv-bg">
                                            <span class="text-sm text-wv-text font-medium">{{ $r->name }}</span>
                                            <span class="text-xs text-wv-text-secondary ms-auto">
                                                {{ trans_choice(':count permiso|:count permisos', $r->permissions->count(), ['count' => $r->permissions->count()]) }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error :messages="$errors->get('roles')" class="mt-2" />
                                <x-input-error :messages="$errors->get('roles.*')" class="mt-2" />
                            @endif
                        </div>

                        <div>
                            <x-input-label for="team_id" :value="__('Equipo asociado (opcional, para gestores)')" />
                            <select id="team_id" name="team_id"
                                    class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">
                                <option value="">— {{ __('Sin equipo') }} —</option>
                                @foreach ($teams ?? [] as $t)
                                    <option value="{{ $t->id }}" {{ (string) old('team_id') === (string) $t->id ? 'selected' : '' }}>
                                        {{ $t->name }}@if ($t->short_name) ({{ $t->short_name }})@endif
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('team_id')" class="mt-2" />
                            <p class="mt-1 text-xs text-wv-text-secondary">{{ __('DISI-81: los usuarios con rol gestor quedan con scope automático a su equipo.') }}</p>
                        </div>

                        {{-- DISI-delegado: categoria asociada (opcional, para delegados) --}}
                        <div>
                            <x-input-label for="category_id" :value="__('Categoría asociada (opcional, para delegados)')" />
                            <select id="category_id" name="category_id"
                                    class="block mt-1 w-full border-wv-border bg-wv-surface text-wv-text focus:border-wv-accent focus:ring-wv-accent rounded-md shadow-sm">
                                <option value="">— {{ __('Sin categoría') }} —</option>
                                @foreach ($categories ?? [] as $c)
                                    <option value="{{ $c->id }}" {{ (string) old('category_id') === (string) $c->id ? 'selected' : '' }}>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                            <p class="mt-1 text-xs text-wv-text-secondary">{{ __('DISI-delegado: los usuarios con rol delegado administran atletas de (su equipo, esta categoría).') }}</p>
                        </div>

                        <div class="flex items-center">
                            <input id="active" name="active" type="checkbox" value="1"
                                   {{ old('active', true) ? 'checked' : '' }}
                                   class="rounded border-wv-border bg-wv-surface text-wv-accent focus:ring-wv-accent focus:ring-offset-wv-bg">
                            <label for="active" class="ms-2 text-sm text-wv-text">
                                {{ __('Usuario activo (puede iniciar sesión)') }}
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6 gap-3">
                        <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 border border-wv-border hover:bg-wv-surface-hover text-wv-text text-sm font-medium rounded-card">{{ __('Cancelar') }}</a>
                        <x-primary-button>{{ __('Crear usuario') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
