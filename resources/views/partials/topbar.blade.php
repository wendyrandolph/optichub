<nav class="bg-white/80 backdrop-blur supports-[backdrop-filter]:bg-white/60 ring-1 ring-ink-100">
  <div class="container flex items-center justify-between h-14">
    <a href="/home" class="flex items-center gap-2 no-underline">
      <img src="{{ asset('images/logo.svg') }}" alt="Optic Hub Logo" class="h-6 w-6">
      <span class="font-semibold text-ink-800">Optic Hub</span>
    </a>
    <div class="flex items-center gap-2">
    <form action="{{ route('logout') }}" method="POST">
          @csrf
          <!-- Use a button with type="submit" and style it like a link -->
          <button type="submit" 
                  class="nav-link text-ink-700 hover:text-optic-500 transition-colors 
                         bg-transparent border-none p-0 cursor-pointer text-base font-normal leading-normal">
            Logout
          </button>
      </form>
    </div>
  </div>
</nav>
