<section>
    <header class="flex items-center gap-4">
        @if ($user->avatar_url)
            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                 class="h-16 w-16 rounded-full object-cover bg-gray-100">
        @else
            <div class="h-16 w-16 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xl font-semibold">
                {{ $user->avatar_initials }}
            </div>
        @endif
        <div>
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Informacion del perfil') }}
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                {{ __('Actualiza tu nombre, correo electronico y fotografia.') }}
            </p>
        </div>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Nombre')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Correo electronico')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                          :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Tu correo electronico no esta verificado.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Haz clic aqui para reenviar el correo de verificacion.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('Se ha enviado un nuevo enlace de verificacion a tu correo.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="avatar" :value="__('Fotografia de perfil')" />
            <input id="avatar" name="avatar" type="file" accept="image/png,image/jpeg,image/webp"
                   class="block mt-1 w-full text-sm text-gray-700 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
            <x-input-error :messages="$errors->get('avatar')" class="mt-2" />
            <p class="mt-1 text-xs text-gray-500">JPG, PNG o WebP. Maximo 2 MB.</p>

            @if ($user->avatar_path)
                <div class="mt-3 flex items-center gap-3 p-3 bg-gray-50 rounded-md">
                    <img src="{{ $user->avatar_url }}" alt="" class="h-12 w-12 rounded-full object-cover bg-white p-0.5">
                    <label class="flex items-center text-sm text-red-600">
                        <input type="checkbox" name="remove_avatar" value="1"
                               {{ old('remove_avatar') ? 'checked' : '' }}
                               class="rounded border-gray-300 text-red-600 focus:ring-red-500 mr-2">
                        {{ __('Eliminar fotografia actual') }}
                    </label>
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Guardar') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition
                   x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm text-gray-600">{{ __('Guardado.') }}</p>
            @endif
            @if (session('status') === 'avatar-removed')
                <p x-data="{ show: true }" x-show="show" x-transition
                   x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm text-gray-600">{{ __('Fotografia eliminada.') }}</p>
            @endif
        </div>
    </form>
</section>
