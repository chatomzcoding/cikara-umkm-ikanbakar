<div class="sidebar-panel">
  @switch($menu)
    @case('dashboard')
      <div
        class="flex h-full grow flex-col bg-white pl-[var(--main-sidebar-width)] dark:bg-navy-750"
        >
        <!-- Sidebar Panel Header -->
        <div
          class="flex h-18 w-full items-center justify-between pl-4 pr-1">
          <p
            class="text-base tracking-wider text-slate-800 dark:text-navy-100"
          >
            Dashboard
          </p>
          <button
            class="sidebar-close btn size-7 rounded-full p-0 text-primary hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:text-accent-light/80 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25 xl:hidden"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="size-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 19l-7-7 7-7"
              />
            </svg>
          </button>
        </div>

        <!-- Sidebar Panel Body -->
        <div
          class="nav-wrapper h-[calc(100%-4.5rem)] overflow-x-hidden pb-6"
          data-simplebar>
          <ul class="flex flex-1 flex-col px-4 font-inter">
            <li>
              <a
                href="dashboards-crm-analytics.html"
                data-default-class="text-slate-600 hover:text-slate-800 dark:text-navy-200 dark:hover:text-navy-50"
                data-active-class="font-medium text-primary dark:text-accent-light"
                class="nav-link flex py-2 text-xs-plus tracking-wide outline-hidden transition-colors duration-300 ease-in-out"
              >
                CRM Analytics
              </a>
            </li>
          </ul>
          <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>
        </div>
      </div>
      @break
    @case('chatomz')
      <div
        class="flex h-full grow flex-col bg-white pl-[var(--main-sidebar-width)] dark:bg-navy-750"
        >
        <!-- Sidebar Panel Header -->
        <div
          class="flex h-18 w-full items-center justify-between pl-4 pr-1">
          <p
            class="text-base tracking-wider text-slate-800 dark:text-navy-100"
          >
            Chatomz
          </p>
          <button
            class="sidebar-close btn size-7 rounded-full p-0 text-primary hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:text-accent-light/80 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25 xl:hidden"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="size-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 19l-7-7 7-7"
              />
            </svg>
          </button>
        </div>

        <!-- Sidebar Panel Body -->
        <div
          class="nav-wrapper h-[calc(100%-4.5rem)] overflow-x-hidden pb-6"
          data-simplebar>
            <ul class="flex flex-1 flex-col px-4 font-inter">
              <li>
                <a
                  href="{{ route('chatomz.dashboard') }}"
                  data-default-class="text-slate-600 hover:text-slate-800 dark:text-navy-200 dark:hover:text-navy-50"
                  data-active-class="font-medium text-primary dark:text-accent-light"
                  class="nav-link flex py-2 text-xs-plus tracking-wide outline-hidden transition-colors duration-300 ease-in-out"
                >
                  Dashboard
                </a>
              </li>
            </ul>
          <div class="my-2 mx-4 flex items-center space-x-3">
            <div class="h-px flex-1 bg-slate-200 dark:bg-navy-500"></div>
            <p>Chatomz</p>
            <div class="h-px flex-1 bg-slate-200 dark:bg-navy-500"></div>
          </div>
          <ul class="flex flex-1 flex-col px-4 font-inter">
            <li>
              <a
                href="{{ route('chatomz.rekening.index') }}"
                data-default-class="text-slate-600 hover:text-slate-800 dark:text-navy-200 dark:hover:text-navy-50"
                data-active-class="font-medium text-primary dark:text-accent-light"
                class="nav-link flex py-2 text-xs-plus tracking-wide outline-hidden transition-colors duration-300 ease-in-out"
              >
                Data Rekening
              </a>
            </li>
            <li>
              <a
                href="{{ route('chatomz.transaksi.index') }}"
                data-default-class="text-slate-600 hover:text-slate-800 dark:text-navy-200 dark:hover:text-navy-50"
                data-active-class="font-medium text-primary dark:text-accent-light"
                class="nav-link flex py-2 text-xs-plus tracking-wide outline-hidden transition-colors duration-300 ease-in-out"
              >
                Data Transaksi
              </a>
            </li>
            <li>
              <a
                href="{{ route('chatomz.item.index') }}"
                data-default-class="text-slate-600 hover:text-slate-800 dark:text-navy-200 dark:hover:text-navy-50"
                data-active-class="font-medium text-primary dark:text-accent-light"
                class="nav-link flex py-2 text-xs-plus tracking-wide outline-hidden transition-colors duration-300 ease-in-out"
              >
                Data Item
              </a>
            </li>
          </ul>
           <div class="my-2 mx-4 flex items-center space-x-3">
            <div class="h-px flex-1 bg-slate-200 dark:bg-navy-500"></div>
            <p>Kingdom</p>
            <div class="h-px flex-1 bg-slate-200 dark:bg-navy-500"></div>
          </div>
          <ul class="flex flex-1 flex-col px-4 font-inter">
             <li>
              <a
                href="{{ route('chatomz.orang.index') }}"
                data-default-class="text-slate-600 hover:text-slate-800 dark:text-navy-200 dark:hover:text-navy-50"
                data-active-class="font-medium text-primary dark:text-accent-light"
                class="nav-link flex py-2 text-xs-plus tracking-wide outline-hidden transition-colors duration-300 ease-in-out"
              >
                Orang
              </a>
              </li>
              <li>
                <a
                  href="{{ route('chatomz.keluarga.index') }}"
                  data-default-class="text-slate-600 hover:text-slate-800 dark:text-navy-200 dark:hover:text-navy-50"
                  data-active-class="font-medium text-primary dark:text-accent-light"
                  class="nav-link flex py-2 text-xs-plus tracking-wide outline-hidden transition-colors duration-300 ease-in-out"
                >
                  Keluarga
                </a>
              </li>
              <li>
                <a
                  href="{{ route('chatomz.grup.index') }}"
                  data-default-class="text-slate-600 hover:text-slate-800 dark:text-navy-200 dark:hover:text-navy-50"
                  data-active-class="font-medium text-primary dark:text-accent-light"
                  class="nav-link flex py-2 text-xs-plus tracking-wide outline-hidden transition-colors duration-300 ease-in-out"
                >
                  Grup
                </a>
              </li>
              <li>
                <a
                  href="{{ route('chatomz.tempat.index') }}"
                  data-default-class="text-slate-600 hover:text-slate-800 dark:text-navy-200 dark:hover:text-navy-50"
                  data-active-class="font-medium text-primary dark:text-accent-light"
                  class="nav-link flex py-2 text-xs-plus tracking-wide outline-hidden transition-colors duration-300 ease-in-out"
                >
                  Tempat
                </a>
              </li>
              <li>
                <a
                  href="{{ route('chatomz.barang.index') }}"
                  data-default-class="text-slate-600 hover:text-slate-800 dark:text-navy-200 dark:hover:text-navy-50"
                  data-active-class="font-medium text-primary dark:text-accent-light"
                  class="nav-link flex py-2 text-xs-plus tracking-wide outline-hidden transition-colors duration-300 ease-in-out"
                >
                  Barang
                </a>
              </li>
              <li>
                <a
                  href="{{ route('chatomz.catatan.index') }}"
                  data-default-class="text-slate-600 hover:text-slate-800 dark:text-navy-200 dark:hover:text-navy-50"
                  data-active-class="font-medium text-primary dark:text-accent-light"
                  class="nav-link flex py-2 text-xs-plus tracking-wide outline-hidden transition-colors duration-300 ease-in-out"
                >
                  Catatan
                </a>
              </li>
              <li>
                <a
                  href="{{ route('chatomz.linimasa.index') }}"
                  data-default-class="text-slate-600 hover:text-slate-800 dark:text-navy-200 dark:hover:text-navy-50"
                  data-active-class="font-medium text-primary dark:text-accent-light"
                  class="nav-link flex py-2 text-xs-plus tracking-wide outline-hidden transition-colors duration-300 ease-in-out"
                >
                  Linimasa
                </a>
              </li>
          </ul>
        </div>
      </div>
        @break
    @case('master')
      @include('layouts.menu-sub.master')
        @break
    @case('data')
      @include('layouts.menu-sub.data')
        @break
    @case('profil')
      <div
        class="flex h-full grow flex-col bg-white pl-[var(--main-sidebar-width)] dark:bg-navy-750"
        >
        <!-- Sidebar Panel Header -->
        <div
          class="flex h-18 w-full items-center justify-between pl-4 pr-1">
          <p
            class="text-base tracking-wider text-slate-800 dark:text-navy-100"
          >
            Profil
          </p>
          <button
            class="sidebar-close btn size-7 rounded-full p-0 text-primary hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:text-accent-light/80 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25 xl:hidden"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="size-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 19l-7-7 7-7"
              />
            </svg>
          </button>
        </div>

        <!-- Sidebar Panel Body -->
        <div
          class="nav-wrapper h-[calc(100%-4.5rem)] overflow-x-hidden pb-6"
          data-simplebar>
          <ul class="flex flex-1 flex-col px-4 font-inter">
            <li>
              <a
                href="{{ route('chatomz.rekening.index') }}"
                data-default-class="text-slate-600 hover:text-slate-800 dark:text-navy-200 dark:hover:text-navy-50"
                data-active-class="font-medium text-primary dark:text-accent-light"
                class="nav-link flex py-2 text-xs-plus tracking-wide outline-hidden transition-colors duration-300 ease-in-out"
              >
                Data Rekening
              </a>
            </li>
          </ul>
          <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>
          <ul class="flex flex-1 flex-col px-4 font-inter">
            <li>
              <a
                href="dashboards-employees.html"
                data-default-class="text-slate-600 hover:text-slate-800 dark:text-navy-200 dark:hover:text-navy-50"
                data-active-class="font-medium text-primary dark:text-accent-light"
                class="nav-link flex py-2 text-xs-plus tracking-wide outline-hidden transition-colors duration-300 ease-in-out"
              >
                Employees
              </a>
            </li>
          </ul>
          <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>
          <ul class="flex flex-1 flex-col px-4 font-inter">
            <li>
              <a
                href="dashboards-widget-ui.html"
                data-default-class="text-slate-600 hover:text-slate-800 dark:text-navy-200 dark:hover:text-navy-50"
                data-active-class="font-medium text-primary dark:text-accent-light"
                class="nav-link flex py-2 text-xs-plus tracking-wide outline-hidden transition-colors duration-300 ease-in-out"
              >
                Widget UI
              </a>
            </li>
          </ul>
        </div>
      </div>
        @break
    @default
  @endswitch
</div>