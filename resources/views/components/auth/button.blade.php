<button
  {{ $attributes->merge([
      'type' => 'submit',
      'class' => 'btn h-10 w-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90',
  ]) }}
>
  {{ $slot }}
</button>
