@php
    $user = auth()->guard('client')->user();
    $tenant = $user?->tenant; // assuming user has tenant() relationship
    $portalTheme = $portalTheme ?? [];
@endphp
<header id="main-header"
    class="flex w-full sm:max-w-4xl lg:max-w-6xl mx-auto  px-4 sm:px-6 lg:px-8 py-3 justify-between items-start content-center .bg-white\/7">
    <a href="{{ route('portal.dashboard') }}" class="flex items-center h-full gap-2"
        aria-label="{{ $portalTheme['brand_name'] ?? 'Renlo' }} home">
        @if (!empty($portalTheme['logo_light']))
            <img src="{{ $portalTheme['logo_light'] }}" alt="{{ $portalTheme['brand_name'] ?? 'Renlo' }}"
                class="h-7 w-auto">
        @else
            <span class="text-xl font-extrabold select-none text-[rgb(var(--brand-primary))]">
                {{ $portalTheme['brand_name'] ?? 'Renlo' }}
            </span>
        @endif
    </a>
    <div class="gap-3 rounded-xl px-3 py-2">
        @if (!empty($portalTheme['logo_light']))
            <img src="{{ $portalTheme['logo_light'] }}" alt="{{ $tenant->name ?? 'Workspace' }} logo"
                class="h-8 w-auto rounded-md object-contain bg-white/80 border border-border-default/70">
        @endif
        <div class="leading-tight">
            <div class="text-sm font-medium text-text-base truncate max-w-[180px]">
                {{ $tenant->name ?? 'Your workspace' }}
             </div>

         </div>
     </div>


     <!-- Right: Actions -->
     <div class="flex items-end col-span-1 gap-3">
         <!-- Notifications -->
         <button type="button"
             class="text-gray-500 hover:text-indigo-600 transition p-2 rounded-full hover:bg-gray-100"
             aria-label="Notifications">
             <i class="fas fa-bell text-lg leading-none"></i>
         </button>

         @php
             $user = auth()->user();
             $initials = $user
                 ? strtoupper(
                     mb_substr($user->first_name ?? ($user->name ?? ''), 0, 1) .
                         mb_substr($user->last_name ?? '', 0, 1),
                 )
                 : 'CP';
         @endphp

         <div class="flex items-center gap-4">
             {{-- User pill + logout --}}
             @if ($user)
                 <div class="flex items-center gap-2">
                     <div class="hidden sm:flex flex-col items-end">
                         <span class="text-xs font-medium text-text-base leading-tight">
                            {{ $user->name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) }}
                         </span>
                         <span class="text-[11px] text-text-subtle leading-tight">
                             Client
                         </span>
                     </div>
                     <div
                        class="h-8 w-8 rounded-full bg-[rgb(var(--brand-primary))] text-white text-xs font-semibold flex items-center justify-center shadow-sm">
                         {{ $initials }}
                     </div>
                     <form method="POST" action="{{ route('logout') }}">
                         @csrf
                         <button type="submit"
                             class="ml-1 inline-flex items-center rounded-md border border-border-default bg-surface-card px-2 py-1 text-[11px] font-medium text-text-subtle hover:bg-surface-accent">
                             <i class="fa-solid fa-arrow-right-from-bracket mr-1 text-[10px]"></i>
                             Logout
                         </button>
                     </form>
                 </div>
             @endif

         </div>

     </div>
 </header>
