<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-h-wv text-wv-text leading-tight">{{ __('Editar usuario') }}: {{ $user->name }}</h2></x-slot>
    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-between items-center">
                <a href="{{ route('users.index') }}" class="text-sm text-wv-accent hover:text-wv-accent-hover">← {{ __('Volver al listado') }}</a>

                {{-- Boton "Iniciar sesion como" solo para admins, cuando:
                     - el usuario actual es admin y NO esta impersonando
                     - el target NO es el mismo admin (no auto-impersonar)
                     - el target NO es admin (sin escalada de privilegios)
                     Lo expone el trait Lab404\Impersonate\Models\Impersonate. --}}
                @auth
                    @if (auth()->user()->canImpersonate() && $user->canBeImpersonated() && auth()->id() !== $user->id)
                        <a href="{{ route('impersonate', $user->id) }}"
                           onclick="event.preventDefault(); SwalHelper.confirm({ title: '{{ __('Vas a iniciar sesion como') }} «{{ $user->name }}». {{ __('Tus acciones quedaran registradas. Continuar?') }}', icon: 'warning', confirmText: '{{ __('Sí, continuar') }}' }).then(ok => { if (ok) window.location.href = this.href; }); return false;"
                           class="inline-flex items-center gap-2 px-4 py-2 border border-wv-accent text-wv-accent hover:bg-wv-accent hover:text-wv-text-on-accent text-sm font-semibold rounded-card transition">
                            <span class="material-symbols-outlined text-[18px]">switch_account</span>
                            {{ __('Iniciar sesion como') }}
                        </a>
                    @endif
                @endauth
            </div>
            @include('partials._flash')

            <div class="bg-wv-surface border border-wv-border rounded-card">
                <form method="POST" action="{{ route('users.update', $user) }}" class="p-6">
                    @csrf @method('PUT')

                    <div class="space-y-4">
                        <div>
                            <x-input-label for="name" :value="__('Nombre')" />
                            <x-text-input id="name" name="name" type="text" class="block mt-1 w-full"
                                          :value="old('name', $user->name)" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Correo electrónico')" />
                            <x-text-input id="email" name="email" type="email" class="block mt-1 w-full"
                                          :value="old('email', $user->email)" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        {{-- Roles del usuario.
                             Enviamos `roles[]` como array de NOMBRES (no ids).
                             El UserController::update hace syncRoles($roles) y la
                             validacion UpdateUserRolesRequest::rules exige que cada
                             valor exista en la tabla roles por name. El original
                             af28aee ya usaba este patron (commit d24384d lo
                             reescribio con un <select name="role_id"> que no
                             encajaba con el controller -> se quitaban todos los
                             roles en silencio al guardar). --}}
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
                                                   {{ in_array($r->name, old('roles', $assigned), true) ? 'checked' : '' }}
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
                                    <option value="{{ $t->id }}" {{ (string) old('team_id', $user->team_id ?? '') === (string) $t->id ? 'selected' : '' }}>
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
                                    <option value="{{ $c->id }}" {{ (string) old('category_id', $user->category_id ?? '') === (string) $c->id ? 'selected' : '' }}>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                            <p class="mt-1 text-xs text-wv-text-secondary">{{ __('DISI-delegado: los usuarios con rol delegado administran atletas de (su equipo, esta categoría).') }}</p>
                        </div>

                        <div class="flex items-center">
                            <input id="active" name="active" type="checkbox" value="1"
                                   {{ old('active', $user->active ?? true) ? 'checked' : '' }}
                                   class="rounded border-wv-border bg-wv-surface text-wv-accent focus:ring-wv-accent focus:ring-offset-wv-bg">
                            <label for="active" class="ms-2 text-sm text-wv-text">
                                {{ __('Usuario activo (puede iniciar sesión)') }}
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6 gap-3">
                        <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 border border-wv-border hover:bg-wv-surface-hover text-wv-text text-sm font-medium rounded-card">{{ __('Cancelar') }}</a>
                        <x-primary-button>{{ __('Guardar cambios') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>