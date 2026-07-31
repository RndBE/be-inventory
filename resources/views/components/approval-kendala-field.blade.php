@php
    $fieldName = $fieldName ?? 'kendala';
    $fieldId = $fieldId ?? $fieldName;
@endphp

<div class="mt-4">
    <label for="{{ $fieldId }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
        Kendala / Catatan Proses
    </label>
    <textarea name="{{ $fieldName }}" id="{{ $fieldId }}" rows="3"
        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
        placeholder="Contoh: menunggu konfirmasi vendor, dokumen belum lengkap, atau budget masih dicek.">{{ old($fieldName, $value ?? '') }}</textarea>
</div>
