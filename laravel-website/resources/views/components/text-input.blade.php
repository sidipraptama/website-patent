@props(['disabled' => false])

<input @disabled($disabled)
    {{ $attributes->merge(['class' => 'border-gray-300 focus:border-customBlude focus:ring-customBlue rounded-md shadow-sm']) }}>
