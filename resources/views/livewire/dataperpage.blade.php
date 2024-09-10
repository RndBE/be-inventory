
<select wire:model.live='perPage' id="perPage" name="perPage" class="mt-2 block w-fit rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
    @for ($i=1;$i<=5;$i++)
    <option value="{{5*$i}}">{{5*$i}}</option>
    @endfor
</select>

